<?php

namespace Tests\Feature\Attendance;

use App\Models\AttendanceRecord;
use App\Models\BreakRecord;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AttendanceStatusTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Carbon::setTestNow(Carbon::create(2026, 10, 1, 9, 0, 0, 'Asia/Tokyo'));
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_off_duty_status_is_displayed(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/attendance');

        $response->assertOk();
        $response->assertSeeText('勤務外');
    }

    public function test_working_status_is_displayed(): void
    {
        $user = User::factory()->create();
        AttendanceRecord::factory()->for($user)->create([
            'date' => '2026-10-01',
            'clock_in' => '09:00:00',
            'clock_out' => null,
        ]);

        $response = $this->actingAs($user)->get('/attendance');

        $response->assertOk();
        $response->assertSeeText('出勤中');
    }

    public function test_on_break_status_is_displayed(): void
    {
        $user = User::factory()->create();
        $attendance = AttendanceRecord::factory()->for($user)->create([
            'date' => '2026-10-01',
            'clock_in' => '09:00:00',
            'clock_out' => null,
        ]);
        BreakRecord::factory()->for($attendance, 'attendanceRecord')->create([
            'break_in' => '12:00:00',
            'break_out' => null,
        ]);

        $response = $this->actingAs($user)->get('/attendance');

        $response->assertOk();
        $response->assertSeeText('休憩中');
    }

    public function test_clocked_out_status_is_displayed(): void
    {
        $user = User::factory()->create();
        AttendanceRecord::factory()->for($user)->create([
            'date' => '2026-10-01',
            'clock_in' => '09:00:00',
            'clock_out' => '18:00:00',
        ]);

        $response = $this->actingAs($user)->get('/attendance');

        $response->assertOk();
        $response->assertSeeText('退勤済');
    }
}
