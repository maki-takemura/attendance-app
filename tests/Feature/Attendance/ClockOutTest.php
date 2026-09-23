<?php

namespace Tests\Feature\Attendance;

use App\Models\AttendanceRecord;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ClockOutTest extends TestCase
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

    public function test_clock_out_button_updates_attendance_and_changes_status(): void
    {
        $user = User::factory()->create();
        $attendance = AttendanceRecord::factory()->for($user)->create([
            'date' => '2026-10-01',
            'clock_in' => '09:00:00',
            'clock_out' => null,
        ]);
        $this->actingAs($user);

        $this->get('/attendance')
            ->assertOk()
            ->assertSee('value="clock_out"', false);

        Carbon::setTestNow(Carbon::create(2026, 10, 1, 18, 0, 0, 'Asia/Tokyo'));
        $this->post('/attendance', ['action' => 'clock_out'])
            ->assertRedirect('/attendance');

        $this->assertDatabaseHas('attendance_records', [
            'id' => $attendance->id,
            'clock_out' => '18:00:00',
        ]);
        $response = $this->get('/attendance');
        $response->assertSeeText('退勤済');
        $response->assertSeeText('お疲れ様でした。');
    }

    public function test_clock_out_time_is_displayed_on_attendance_list(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $this->post('/attendance', ['action' => 'clock_in'])
            ->assertRedirect('/attendance');
        Carbon::setTestNow(Carbon::create(2026, 10, 1, 18, 0, 0, 'Asia/Tokyo'));
        $this->post('/attendance', ['action' => 'clock_out'])
            ->assertRedirect('/attendance');

        $this->assertDatabaseHas('attendance_records', [
            'user_id' => $user->id,
            'date' => '2026-10-01',
            'clock_in' => '09:00:00',
            'clock_out' => '18:00:00',
        ]);
        $response = $this->get('/attendance/list?date=2026-10');
        $response->assertOk();
        $response->assertSeeInOrder(['10/01(木)', '09:00', '18:00']);
    }
}
