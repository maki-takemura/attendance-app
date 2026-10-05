<?php

namespace Tests\Feature\Api\V1;

use App\Models\AttendanceRecord;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AttendanceRecordAuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_未認証時に書き込み系_ap_i_で_401_が返る(): void
    {
        $user = User::factory()->create();

        $attendanceRecord = AttendanceRecord::factory()->create([
            'user_id' => $user->id,
        ]);

        $postResponse = $this->postJson('/api/v1/attendance-records', [
            'date' => '2026-10-24',
            'clock_in' => '09:00:00',
        ]);

        $putResponse = $this->putJson(
            "/api/v1/attendance-records/{$attendanceRecord->id}",
            [
                'clock_in' => '08:30:00',
            ]
        );

        $deleteResponse = $this->deleteJson(
            "/api/v1/attendance-records/{$attendanceRecord->id}"
        );

        $postResponse->assertStatus(401)
            ->assertExactJson([
                'message' => 'Unauthenticated.',
            ]);

        $putResponse->assertStatus(401)
            ->assertExactJson([
                'message' => 'Unauthenticated.',
            ]);

        $deleteResponse->assertStatus(401)
            ->assertExactJson([
                'message' => 'Unauthenticated.',
            ]);
    }

    public function test_認証済みユーザーは自分の勤怠を更新削除できる(): void
    {
        $user = User::factory()->create();

        $attendanceRecord = AttendanceRecord::factory()->create([
            'user_id' => $user->id,
            'clock_in' => '09:00:00',
            'clock_out' => '18:00:00',
        ]);

        Sanctum::actingAs($user);

        $putResponse = $this->putJson(
            "/api/v1/attendance-records/{$attendanceRecord->id}",
            [
                'clock_in' => '08:30:00',
            ]
        );

        $putResponse->assertStatus(200);

        $this->assertDatabaseHas('attendance_records', [
            'id' => $attendanceRecord->id,
            'clock_in' => '08:30:00',
        ]);

        $deleteResponse = $this->deleteJson(
            "/api/v1/attendance-records/{$attendanceRecord->id}"
        );

        $deleteResponse->assertNoContent();

        $this->assertDatabaseMissing('attendance_records', [
            'id' => $attendanceRecord->id,
        ]);
    }

    public function test_他ユーザーの勤怠を更新削除しようとすると_403_が返る(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();

        $attendanceRecord = AttendanceRecord::factory()->create([
            'user_id' => $otherUser->id,
        ]);

        Sanctum::actingAs($user);

        $putResponse = $this->putJson(
            "/api/v1/attendance-records/{$attendanceRecord->id}",
            [
                'clock_in' => '08:30:00',
            ]
        );

        $deleteResponse = $this->deleteJson(
            "/api/v1/attendance-records/{$attendanceRecord->id}"
        );

        $putResponse->assertStatus(403)
            ->assertExactJson([
                'error' => 'この操作を実行する権限がありません。',
            ]);

        $deleteResponse->assertStatus(403)
            ->assertExactJson([
                'error' => 'この操作を実行する権限がありません。',
            ]);
    }
}
