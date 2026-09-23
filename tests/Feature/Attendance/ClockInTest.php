<?php

namespace Tests\Feature\Attendance;

use App\Models\AttendanceRecord;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ClockInTest extends TestCase
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

    public function test_clock_in_button_creates_attendance_and_changes_status(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $this->get('/attendance')
            ->assertOk()
            ->assertSee('value="clock_in"', false);

        $this->post('/attendance', ['action' => 'clock_in'])
            ->assertRedirect('/attendance');

        $this->assertDatabaseHas('attendance_records', [
            'user_id' => $user->id,
            'date' => '2026-10-01',
            'clock_in' => '09:00:00',
            'clock_out' => null,
        ]);
        $this->get('/attendance')->assertSeeText('出勤中');
    }

    public function test_clock_in_is_available_only_once_per_day(): void
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
        $response->assertDontSee('value="clock_in"', false);
        $this->assertDatabaseCount('attendance_records', 1);
    }

    public function test_clock_in_time_is_displayed_on_attendance_list(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user)
            ->post('/attendance', ['action' => 'clock_in'])
            ->assertRedirect('/attendance');

        $this->assertDatabaseHas('attendance_records', [
            'user_id' => $user->id,
            'date' => '2026-10-01',
            'clock_in' => '09:00:00',
        ]);

        $response = $this->get('/attendance/list?date=2026-10');

        $response->assertOk();
        $response->assertSeeInOrder(['10/01(木)', '09:00']);
    }
}
