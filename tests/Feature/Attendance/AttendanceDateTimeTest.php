<?php

namespace Tests\Feature\Attendance;

use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AttendanceDateTimeTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_current_date_and_time_are_displayed_in_the_ui_format(): void
    {
        Carbon::setTestNow(Carbon::create(2026, 10, 1, 9, 0, 0, 'Asia/Tokyo'));
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/attendance');

        $response->assertOk();
        $response->assertViewHas('formattedDate', '2026年10月1日(木)');
        $response->assertViewHas('formattedTime', '09:00');
        $response->assertSee('value="2026年10月1日(木)"', false);
        $response->assertSee('id="currentTime" value="09:00"', false);
    }
}
