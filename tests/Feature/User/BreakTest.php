<?php

namespace Tests\Feature\User;

use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BreakTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Carbon::setTestNow(Carbon::create(2026, 10, 1, 12, 0));
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_休憩ボタンが正しく機能する(): void
    {
        $user = User::factory()->create();

        $user->attendanceRecords()->create([
            'date' => '2026-10-01',
            'clock_in' => '09:00:00',
        ]);

        $response = $this->actingAs($user)->get('/attendance');

        $response->assertStatus(200);
        $response->assertSee(
            '<button class="attendance__button--submit--break-in" type="submit" name="action" value="break_in">休憩入</button>',
            false
        );

        $response = $this->actingAs($user)->post('/attendance', [
            'action' => 'break_in',
        ]);

        $response->assertRedirect('/attendance');

        $response = $this->actingAs($user)->get('/attendance');

        $response->assertSee(
            '<p class="attendance__status--item">休憩中</p>',
            false
        );
    }

    public function test_休憩は一日に何回でもできる(): void
    {
        $user = User::factory()->create();

        $user->attendanceRecords()->create([
            'date' => '2026-10-01',
            'clock_in' => '09:00:00',
        ]);

        $this->actingAs($user)->post('/attendance', [
            'action' => 'break_in',
        ]);

        Carbon::setTestNow(Carbon::create(2026, 10, 1, 13, 0));

        $this->actingAs($user)->post('/attendance', [
            'action' => 'break_out',
        ]);

        $response = $this->actingAs($user)->get('/attendance');

        $response->assertStatus(200);
        $response->assertSee(
            '<button class="attendance__button--submit--break-in" type="submit" name="action" value="break_in">休憩入</button>',
            false
        );
    }

    public function test_休憩戻ボタンが正しく機能する(): void
    {
        $user = User::factory()->create();

        $user->attendanceRecords()->create([
            'date' => '2026-10-01',
            'clock_in' => '09:00:00',
        ]);

        $this->actingAs($user)->post('/attendance', [
            'action' => 'break_in',
        ]);

        $response = $this->actingAs($user)->get('/attendance');

        $response->assertSee(
            '<button class="attendance__button--submit--break-out" type="submit" name="action" value="break_out">休憩戻</button>',
            false
        );

        $response = $this->actingAs($user)->post('/attendance', [
            'action' => 'break_out',
        ]);

        $response->assertRedirect('/attendance');

        $response = $this->actingAs($user)->get('/attendance');

        $response->assertSee(
            '<p class="attendance__status--item">出勤中</p>',
            false
        );
    }

    public function test_休憩戻は一日に何回でもできる(): void
    {
        $user = User::factory()->create();

        $user->attendanceRecords()->create([
            'date' => '2026-10-01',
            'clock_in' => '09:00:00',
        ]);

        $this->actingAs($user)->post('/attendance', [
            'action' => 'break_in',
        ]);

        Carbon::setTestNow(Carbon::create(2026, 10, 1, 13, 0));

        $this->actingAs($user)->post('/attendance', [
            'action' => 'break_out',
        ]);

        Carbon::setTestNow(Carbon::create(2026, 10, 1, 15, 0));

        $this->actingAs($user)->post('/attendance', [
            'action' => 'break_in',
        ]);

        $response = $this->actingAs($user)->get('/attendance');

        $response->assertStatus(200);
        $response->assertSee(
            '<button class="attendance__button--submit--break-out" type="submit" name="action" value="break_out">休憩戻</button>',
            false
        );
    }

    public function test_休憩時刻が勤怠一覧画面で確認できる(): void
    {
        $user = User::factory()->create();

        $user->attendanceRecords()->create([
            'date' => '2026-10-01',
            'clock_in' => '09:00:00',
        ]);

        Carbon::setTestNow(Carbon::create(2026, 10, 1, 12, 0));

        $this->actingAs($user)->post('/attendance', [
            'action' => 'break_in',
        ]);

        Carbon::setTestNow(Carbon::create(2026, 10, 1, 13, 0));

        $this->actingAs($user)->post('/attendance', [
            'action' => 'break_out',
        ]);

        $response = $this->actingAs($user)->get('/attendance/list');

        $response->assertStatus(200);
        $response->assertSee('10/01(木)');
        $response->assertSee('1:00');
    }
}
