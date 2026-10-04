<?php

namespace Tests\Feature\User;

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

        Carbon::setTestNow(Carbon::create(2026, 10, 1, 18, 0));
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_退勤ボタンが正しく機能する(): void
    {
        $user = User::factory()->create();

        $user->attendanceRecords()->create([
            'date' => '2026-10-01',
            'clock_in' => '09:00:00',
        ]);

        $response = $this->actingAs($user)->get('/attendance');

        $response->assertStatus(200);
        $response->assertSee(
            '<button class="attendance__button--submit--clock-out" type="submit" name="action" value="clock_out">退勤</button>',
            false
        );

        $response = $this->actingAs($user)->post('/attendance', [
            'action' => 'clock_out',
        ]);

        $response->assertRedirect('/attendance');

        $response = $this->actingAs($user)->get('/attendance');

        $response->assertSee(
            '<p class="attendance__status--item">退勤済</p>',
            false
        );
    }

    public function test_退勤時刻が勤怠一覧画面で確認できる(): void
    {
        $user = User::factory()->create();

        $user->attendanceRecords()->create([
            'date' => '2026-10-01',
            'clock_in' => '09:00:00',
        ]);

        $this->actingAs($user)->post('/attendance', [
            'action' => 'clock_out',
        ]);

        $response = $this->actingAs($user)->get('/attendance/list');

        $response->assertStatus(200);
        $response->assertSee('10/01(木)');
        $response->assertSee('18:00');
    }
}
