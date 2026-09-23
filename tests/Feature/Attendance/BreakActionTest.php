<?php

namespace Tests\Feature\Attendance;

use App\Models\AttendanceRecord;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BreakActionTest extends TestCase
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

    public function test_break_in_button_creates_break_and_changes_status(): void
    {
        [$user, $attendance] = $this->createClockedInAttendance();
        $this->actingAs($user);

        $this->get('/attendance')->assertSee('value="break_in"', false);
        $this->postActionAt('break_in', '2026-10-01 12:00:00');

        $this->assertDatabaseHas('break_records', [
            'attendance_record_id' => $attendance->id,
            'break_in' => '12:00:00',
            'break_out' => null,
        ]);
        $this->get('/attendance')->assertSeeText('休憩中');
    }

    public function test_break_in_can_be_used_multiple_times_per_day(): void
    {
        [$user, $attendance] = $this->createClockedInAttendance();
        $this->actingAs($user);

        $this->postActionAt('break_in', '2026-10-01 12:00:00');
        $this->postActionAt('break_out', '2026-10-01 13:00:00');

        $this->assertDatabaseHas('break_records', [
            'attendance_record_id' => $attendance->id,
            'break_in' => '12:00:00',
            'break_out' => '13:00:00',
        ]);
        $this->assertDatabaseCount('break_records', 1);
        $response = $this->get('/attendance');
        $response->assertSeeText('出勤中');
        $response->assertSee('value="break_in"', false);
    }

    public function test_break_out_button_updates_break_and_changes_status(): void
    {
        [$user, $attendance] = $this->createClockedInAttendance();
        $this->actingAs($user);

        $this->postActionAt('break_in', '2026-10-01 12:00:00');
        $this->get('/attendance')->assertSee('value="break_out"', false);
        $this->postActionAt('break_out', '2026-10-01 13:00:00');

        $this->assertDatabaseHas('break_records', [
            'attendance_record_id' => $attendance->id,
            'break_in' => '12:00:00',
            'break_out' => '13:00:00',
        ]);
        $this->get('/attendance')->assertSeeText('出勤中');
    }

    public function test_break_out_can_be_used_multiple_times_per_day(): void
    {
        [$user, $attendance] = $this->createClockedInAttendance();
        $this->actingAs($user);

        $this->postActionAt('break_in', '2026-10-01 12:00:00');
        $this->postActionAt('break_out', '2026-10-01 13:00:00');
        $this->postActionAt('break_in', '2026-10-01 15:00:00');

        $this->assertDatabaseHas('break_records', [
            'attendance_record_id' => $attendance->id,
            'break_in' => '12:00:00',
            'break_out' => '13:00:00',
        ]);
        $this->assertDatabaseHas('break_records', [
            'attendance_record_id' => $attendance->id,
            'break_in' => '15:00:00',
            'break_out' => null,
        ]);
        $this->assertDatabaseCount('break_records', 2);
        $this->get('/attendance')->assertSee('value="break_out"', false);
    }

    public function test_total_break_time_is_displayed_on_attendance_list(): void
    {
        [$user, $attendance] = $this->createClockedInAttendance();
        $this->actingAs($user);

        $this->postActionAt('break_in', '2026-10-01 12:00:00');
        $this->postActionAt('break_out', '2026-10-01 13:00:00');

        $this->assertDatabaseHas('break_records', [
            'attendance_record_id' => $attendance->id,
            'break_in' => '12:00:00',
            'break_out' => '13:00:00',
        ]);
        $response = $this->get('/attendance/list?date=2026-10');
        $response->assertOk();
        $response->assertSeeInOrder(['10/01(木)', '1:00']);
    }

    private function createClockedInAttendance(): array
    {
        $user = User::factory()->create();
        $attendance = AttendanceRecord::factory()->for($user)->create([
            'date' => '2026-10-01',
            'clock_in' => '09:00:00',
            'clock_out' => null,
        ]);

        return [$user, $attendance];
    }

    private function postActionAt(string $action, string $dateTime): void
    {
        Carbon::setTestNow(Carbon::parse($dateTime, 'Asia/Tokyo'));

        $this->post('/attendance', ['action' => $action])
            ->assertRedirect('/attendance');
    }
}
