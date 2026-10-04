<?php

namespace Tests\Feature\User;

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

        Carbon::setTestNow(Carbon::create(2026, 10, 1, 9, 0));
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_出勤ボタンが正しく機能する(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/attendance');

        $response->assertStatus(200);
        $response->assertSee(
            '<button class="attendance__button--submit--clock-in" type="submit" name="action" value="clock_in">出勤</button>',
            false
        );

        $response = $this->actingAs($user)->post('/attendance', [
            'action' => 'clock_in',
        ]);

        $response->assertRedirect('/attendance');

        $response = $this->actingAs($user)->get('/attendance');

        $response->assertSee(
            '<p class="attendance__status--item">出勤中</p>',
            false
        );
    }

    public function test_出勤は一日一回のみできる(): void
    {
        $user = User::factory()->create();

        $user->attendanceRecords()->create([
            'date' => '2026-10-01',
            'clock_in' => '09:00:00',
            'clock_out' => '18:00:00',
        ]);

        $response = $this->actingAs($user)->get('/attendance');

        $response->assertStatus(200);
        $response->assertDontSee(
            '<button class="attendance__button--submit--clock-in" type="submit" name="action" value="clock_in">出勤</button>',
            false
        );
    }

    public function test_出勤時刻が勤怠一覧画面で確認できる(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->post('/attendance', [
            'action' => 'clock_in',
        ]);

        $response = $this->actingAs($user)->get('/attendance/list');

        $response->assertStatus(200);
        $response->assertSee('10/01(木)');
        $response->assertSee('09:00');
    }
}
