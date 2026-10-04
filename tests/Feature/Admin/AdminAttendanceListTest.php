<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminAttendanceListTest extends TestCase
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

    public function test_その日になされた全ユーザーの勤怠情報が正確に確認できる(): void
    {
        $admin = User::factory()->create([
            'admin_status' => true,
        ]);

        $user1 = User::factory()->create([
            'name' => 'テストユーザー1',
        ]);

        $user2 = User::factory()->create([
            'name' => 'テストユーザー2',
        ]);

        $attendanceRecord1 = $user1->attendanceRecords()->create([
            'date' => '2026-10-01',
            'clock_in' => '09:00:00',
            'clock_out' => '18:00:00',
        ]);

        $attendanceRecord1->breakRecords()->create([
            'break_in' => '12:00:00',
            'break_out' => '13:00:00',
        ]);

        $attendanceRecord2 = $user2->attendanceRecords()->create([
            'date' => '2026-10-01',
            'clock_in' => '10:00:00',
            'clock_out' => '19:00:00',
        ]);

        $attendanceRecord2->breakRecords()->create([
            'break_in' => '15:00:00',
            'break_out' => '15:30:00',
        ]);

        $response = $this->actingAs($admin)->get('/admin/attendance/list');

        $response->assertStatus(200);

        $response->assertSeeInOrder([
            'テストユーザー1',
            '09:00',
            '18:00',
            '1:00',
            '8:00',
        ]);

        $response->assertSeeInOrder([
            'テストユーザー2',
            '10:00',
            '19:00',
            '0:30',
            '8:30',
        ]);
    }

    public function test_遷移した際に現在の日付が表示される(): void
    {
        $admin = User::factory()->create([
            'admin_status' => true,
        ]);

        $response = $this->actingAs($admin)->get('/admin/attendance/list');

        $response->assertStatus(200);
        $response->assertSee(
            '<p class="current-day">2026/10/01</p>',
            false
        );
    }

    public function test_前日を押下した時に前の日の勤怠情報が表示される(): void
    {
        $admin = User::factory()->create([
            'admin_status' => true,
        ]);

        $user = User::factory()->create([
            'name' => '前日ユーザー',
        ]);

        $user->attendanceRecords()->create([
            'date' => '2026-09-30',
            'clock_in' => '08:30:00',
            'clock_out' => '17:30:00',
        ]);

        $response = $this->actingAs($admin)->get(
            '/admin/attendance/list?date=2026/09/30'
        );

        $response->assertStatus(200);
        $response->assertSee(
            '<p class="current-day">2026/09/30</p>',
            false
        );
        $response->assertSee('前日ユーザー');
        $response->assertSee('08:30');
        $response->assertSee('17:30');
    }

    public function test_翌日を押下した時に次の日の勤怠情報が表示される(): void
    {
        $admin = User::factory()->create([
            'admin_status' => true,
        ]);

        $user = User::factory()->create([
            'name' => '翌日ユーザー',
        ]);

        $user->attendanceRecords()->create([
            'date' => '2026-10-02',
            'clock_in' => '10:30:00',
            'clock_out' => '19:30:00',
        ]);

        $response = $this->actingAs($admin)->get(
            '/admin/attendance/list?date=2026/10/02'
        );

        $response->assertStatus(200);
        $response->assertSee(
            '<p class="current-day">2026/10/02</p>',
            false
        );
        $response->assertSee('翌日ユーザー');
        $response->assertSee('10:30');
        $response->assertSee('19:30');
    }
}
