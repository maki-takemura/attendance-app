<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminStaffTest extends TestCase
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

    public function test_管理者ユーザーが全一般ユーザーの氏名とメールアドレスを確認できる(): void
    {
        $admin = User::factory()->create([
            'name' => '管理者ユーザー',
            'email' => 'admin@example.com',
            'admin_status' => true,
        ]);

        User::factory()->create([
            'name' => 'テストユーザー1',
            'email' => 'user1@example.com',
        ]);

        User::factory()->create([
            'name' => 'テストユーザー2',
            'email' => 'user2@example.com',
        ]);

        $response = $this->actingAs($admin)->get('/admin/staff/list');

        $response->assertStatus(200);
        $response->assertSeeInOrder([
            'テストユーザー1',
            'user1@example.com',
        ]);
        $response->assertSeeInOrder([
            'テストユーザー2',
            'user2@example.com',
        ]);
        $response->assertDontSee('管理者ユーザー');
        $response->assertDontSee('admin@example.com');
    }

    public function test_ユーザーの勤怠情報が正しく表示される(): void
    {
        $admin = User::factory()->create([
            'admin_status' => true,
        ]);

        $user = User::factory()->create([
            'name' => 'テストユーザー',
        ]);

        $attendanceRecord = $user->attendanceRecords()->create([
            'date' => '2026-10-01',
            'clock_in' => '09:00:00',
            'clock_out' => '18:00:00',
        ]);

        $attendanceRecord->breakRecords()->create([
            'break_in' => '12:00:00',
            'break_out' => '13:00:00',
        ]);

        $response = $this->actingAs($admin)->get(
            '/admin/attendance/staff/'.$user->id
        );

        $response->assertStatus(200);
        $response->assertSee('テストユーザーさんの勤怠');
        $response->assertSeeInOrder([
            '10/01(木)',
            '09:00',
            '18:00',
            '1:00',
            '8:00',
        ]);
    }

    public function test_前月を押下した時に表示月の前月の情報が表示される(): void
    {
        $admin = User::factory()->create([
            'admin_status' => true,
        ]);

        $user = User::factory()->create();

        $user->attendanceRecords()->create([
            'date' => '2026-09-15',
            'clock_in' => '08:30:00',
            'clock_out' => '17:30:00',
        ]);

        $response = $this->actingAs($admin)->get(
            '/admin/attendance/staff/'.$user->id.'?date=2026/09'
        );

        $response->assertStatus(200);
        $response->assertSee(
            '<p class="current-month">2026/09</p>',
            false
        );
        $response->assertSee('09/15(火)');
        $response->assertSee('08:30');
        $response->assertSee('17:30');
    }

    public function test_翌月を押下した時に表示月の翌月の情報が表示される(): void
    {
        $admin = User::factory()->create([
            'admin_status' => true,
        ]);

        $user = User::factory()->create();

        $user->attendanceRecords()->create([
            'date' => '2026-11-15',
            'clock_in' => '10:30:00',
            'clock_out' => '19:30:00',
        ]);

        $response = $this->actingAs($admin)->get(
            '/admin/attendance/staff/'.$user->id.'?date=2026/11'
        );

        $response->assertStatus(200);
        $response->assertSee(
            '<p class="current-month">2026/11</p>',
            false
        );
        $response->assertSee('11/15(日)');
        $response->assertSee('10:30');
        $response->assertSee('19:30');
    }

    public function test_詳細を押下するとその日の勤怠詳細画面に遷移する(): void
    {
        $admin = User::factory()->create([
            'admin_status' => true,
        ]);

        $user = User::factory()->create();

        $attendanceRecord = $user->attendanceRecords()->create([
            'date' => '2026-10-01',
            'clock_in' => '09:00:00',
            'clock_out' => '18:00:00',
        ]);

        $response = $this->actingAs($admin)->get(
            '/admin/attendance/staff/'.$user->id
        );

        $response->assertStatus(200);
        $response->assertSee('/attendance/'.$attendanceRecord->id);

        $response = $this->actingAs($admin)->get(
            '/attendance/'.$attendanceRecord->id
        );

        $response->assertStatus(200);
    }
}
