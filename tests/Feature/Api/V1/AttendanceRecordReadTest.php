<?php

namespace Tests\Feature\Api\V1;

use App\Models\Application;
use App\Models\AttendanceRecord;
use App\Models\BreakRecord;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AttendanceRecordReadTest extends TestCase
{
    use RefreshDatabase;

    public function test_ge_t_api_v1_attendance_records_で勤怠一覧が_jso_n_で取得できる(): void
    {
        $this->seed();

        $response = $this->getJson('/api/v1/attendance-records');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data',
                'meta' => [
                    'current_page',
                    'last_page',
                    'per_page',
                    'total',
                ],
            ]);
    }

    public function test_ge_t_api_v1_attendance_records_attendance_record_で勤怠詳細が_jso_n_で取得できる(): void
    {
        $user = User::factory()->create();

        $attendanceRecord = AttendanceRecord::factory()->create([
            'user_id' => $user->id,
        ]);

        BreakRecord::factory()->create([
            'attendance_record_id' => $attendanceRecord->id,
        ]);

        Application::factory()->create([
            'attendance_record_id' => $attendanceRecord->id,
        ]);

        $response = $this->getJson(
            "/api/v1/attendance-records/{$attendanceRecord->id}"
        );

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'user_id',
                    'user' => [
                        'id',
                        'name',
                    ],
                    'date',
                    'clock_in',
                    'clock_out',
                    'total_time',
                    'total_break_time',
                    'comment',
                    'breaks' => [
                        '*' => [
                            'id',
                            'break_in',
                            'break_out',
                        ],
                    ],
                    'applications' => [
                        '*' => [
                            'id',
                            'approval_status',
                            'date',
                            'comment',
                            'application_date',
                        ],
                    ],
                ],
            ]);
    }

    public function test_存在しない_i_d_では_404_とエラー_jso_n_が返る(): void
    {
        $response = $this->getJson('/api/v1/attendance-records/99999');

        $response->assertStatus(404)
            ->assertExactJson([
                'error' => '勤怠情報が見つかりませんでした。',
            ]);
    }
}
