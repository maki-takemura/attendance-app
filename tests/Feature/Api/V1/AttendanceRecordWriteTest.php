<?php

namespace Tests\Feature\Api\V1;

use App\Models\AttendanceRecord;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AttendanceRecordWriteTest extends TestCase
{
    use RefreshDatabase;

    public function test_pos_t_api_v1_attendance_records_で勤怠が作成される(): void
    {
        $user = User::factory()->create();

        Sanctum::actingAs($user);

        $response = $this->postJson('/api/v1/attendance-records', [
            'date' => '2026-10-24',
            'clock_in' => '09:00:00',
            'clock_out' => '18:00:00',
            'comment' => 'APIテスト',
        ]);

        $response->assertStatus(201);

        $this->assertDatabaseHas('attendance_records', [
            'user_id' => $user->id,
            'date' => '2026-10-24 00:00:00',
            'clock_in' => '09:00:00',
            'clock_out' => '18:00:00',
            'comment' => 'APIテスト',
        ]);
    }

    public function test_バリデーションエラー時に_422_と日本語エラーメッセージが返る(): void
    {
        $user = User::factory()->create();

        Sanctum::actingAs($user);

        $response = $this->postJson('/api/v1/attendance-records', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors([
                'date',
                'clock_in',
            ])
            ->assertJsonPath('errors.date.0', '勤怠日は必須です。')
            ->assertJsonPath('errors.clock_in.0', '出勤時刻は必須です。');
    }

    public function test_pu_t_api_v1_attendance_records_attendance_record_で勤怠が更新される(): void
    {
        $user = User::factory()->create();

        $attendanceRecord = AttendanceRecord::factory()->create([
            'user_id' => $user->id,
            'date' => '2026-10-23',
            'clock_in' => '09:00:00',
            'clock_out' => '18:00:00',
            'comment' => '更新前',
        ]);

        Sanctum::actingAs($user);

        $response = $this->putJson(
            "/api/v1/attendance-records/{$attendanceRecord->id}",
            [
                'clock_in' => '08:30:00',
                'comment' => '更新後',
            ]
        );

        $response->assertStatus(200);

        $this->assertDatabaseHas('attendance_records', [
            'id' => $attendanceRecord->id,
            'clock_in' => '08:30:00',
            'clock_out' => '18:00:00',
            'comment' => '更新後',
        ]);

        $response = $this->putJson('/api/v1/attendance-records/99999', [
            'clock_in' => '08:30:00',
        ]);

        $response->assertStatus(404)
            ->assertExactJson([
                'error' => '勤怠情報が見つかりませんでした。',
            ]);
    }

    public function test_delet_e_api_v1_attendance_records_attendance_record_で勤怠が削除される(): void
    {
        $user = User::factory()->create();

        $attendanceRecord = AttendanceRecord::factory()->create([
            'user_id' => $user->id,
        ]);

        Sanctum::actingAs($user);

        $response = $this->deleteJson(
            "/api/v1/attendance-records/{$attendanceRecord->id}"
        );

        $response->assertNoContent();

        $this->assertDatabaseMissing('attendance_records', [
            'id' => $attendanceRecord->id,
        ]);

        $response = $this->deleteJson('/api/v1/attendance-records/99999');

        $response->assertStatus(404)
            ->assertExactJson([
                'error' => '勤怠情報が見つかりませんでした。',
            ]);
    }
}
