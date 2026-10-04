<?php

namespace Tests\Feature\User;

use App\Models\AttendanceRecord;
use App\Models\BreakRecord;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Collection;
use Tests\TestCase;

class AttendanceReportTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Carbon::setTestNow(Carbon::create(2026, 10, 15, 12, 0));
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_ゲストはレポートページにアクセスできない(): void
    {
        $response = $this->get('/attendance/report');

        $response->assertRedirect('/login');
    }

    public function test_認証ユーザーの統計情報が正しく計算される(): void
    {
        $user = User::factory()->create();

        $this->createAttendanceRecord(
            $user,
            '2026-09-15',
            '09:00:00',
            '18:00:00'
        );

        $this->createAttendanceRecord(
            $user,
            '2026-10-01',
            '09:00:00',
            '18:00:00'
        );

        $this->createAttendanceRecord(
            $user,
            '2026-10-02',
            '09:30:00',
            '20:30:00'
        );

        $this->createAttendanceRecord(
            $user,
            '2026-10-03',
            '08:00:00',
            '17:00:00'
        );

        $this->createAttendanceRecord(
            $user,
            '2026-10-04',
            '08:00:00',
            '20:30:00'
        );

        $response = $this->actingAs($user)->get('/attendance/report');

        $response->assertStatus(200);

        $response->assertViewHas('summary', [
            'total_work_minutes' => 2730,
            'total_overtime_minutes' => 330,
            'avg_work_minutes' => 546,
        ]);

        $response->assertViewHas(
            'monthlyTrend',
            function (Collection $monthlyTrend): bool {
                $september = $monthlyTrend->firstWhere('month', '2026年9月');
                $october = $monthlyTrend->firstWhere('month', '2026年10月');

                return $monthlyTrend->count() === 6
                    && $september['work_minutes'] === 480
                    && $september['overtime_minutes'] === 0
                    && $october['work_minutes'] === 2250
                    && $october['overtime_minutes'] === 330;
            }
        );

        $response->assertViewHas('anomalies', [
            'late_count' => 1,
            'early_leave_count' => 1,
            'long_work_count' => 1,
        ]);
    }

    public function test_勤怠記録がないユーザーで安全に処理される(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/attendance/report');

        $response->assertStatus(200);

        $response->assertViewHas('summary', [
            'total_work_minutes' => 0,
            'total_overtime_minutes' => 0,
            'avg_work_minutes' => 0,
        ]);

        $response->assertViewHas(
            'monthlyTrend',
            fn (Collection $monthlyTrend): bool => $monthlyTrend->count() === 6
                && $monthlyTrend->every(
                    fn (array $month): bool => $month['work_minutes'] === 0
                        && $month['overtime_minutes'] === 0
                )
        );

        $response->assertViewHas('anomalies', [
            'late_count' => 0,
            'early_leave_count' => 0,
            'long_work_count' => 0,
        ]);
    }

    /**
     * テスト用の完了済み勤怠と休憩を作成する。
     */
    private function createAttendanceRecord(
        User $user,
        string $date,
        string $clockIn,
        string $clockOut
    ): void {
        $attendanceRecord = AttendanceRecord::factory()->create([
            'user_id' => $user->id,
            'date' => $date,
            'clock_in' => $clockIn,
            'clock_out' => $clockOut,
        ]);

        BreakRecord::factory()->create([
            'attendance_record_id' => $attendanceRecord->id,
        ]);
    }
}
