<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminApplicationTest extends TestCase
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

    public function test_承認待ちの修正申請が全て表示されている(): void
    {
        $admin = User::factory()->create(['admin_status' => true]);
        $user1 = User::factory()->create(['name' => 'テストユーザー1']);
        $user2 = User::factory()->create(['name' => 'テストユーザー2']);

        $attendanceRecord1 = $user1->attendanceRecords()->create([
            'date' => '2026-10-01',
            'clock_in' => '09:00:00',
            'clock_out' => '18:00:00',
        ]);
        $attendanceRecord2 = $user2->attendanceRecords()->create([
            'date' => '2026-10-01',
            'clock_in' => '10:00:00',
            'clock_out' => '19:00:00',
        ]);

        $this->actingAs($user1)->post('/attendance/'.$attendanceRecord1->id, [
            'new_clock_in' => '08:30',
            'new_clock_out' => '17:30',
            'comment' => '承認待ち申請1',
        ]);

        $this->actingAs($user2)->post('/attendance/'.$attendanceRecord2->id, [
            'new_clock_in' => '09:30',
            'new_clock_out' => '18:30',
            'comment' => '承認待ち申請2',
        ]);

        $response = $this->actingAs($admin)->get('/stamp_correction_request/list');

        $response->assertStatus(200);
        $response->assertSeeInOrder([
            'id="content1"',
            'テストユーザー1',
            '承認待ち申請1',
            'テストユーザー2',
            '承認待ち申請2',
            'id="content2"',
        ], false);
    }

    public function test_承認済みの修正申請が全て表示されている(): void
    {
        $admin = User::factory()->create(['admin_status' => true]);
        $user1 = User::factory()->create(['name' => 'テストユーザー1']);
        $user2 = User::factory()->create(['name' => 'テストユーザー2']);

        $attendanceRecord1 = $user1->attendanceRecords()->create([
            'date' => '2026-10-01',
            'clock_in' => '09:00:00',
            'clock_out' => '18:00:00',
        ]);
        $attendanceRecord2 = $user2->attendanceRecords()->create([
            'date' => '2026-10-01',
            'clock_in' => '10:00:00',
            'clock_out' => '19:00:00',
        ]);

        $this->actingAs($user1)->post('/attendance/'.$attendanceRecord1->id, [
            'new_clock_in' => '08:30',
            'new_clock_out' => '17:30',
            'comment' => '承認済み申請1',
        ]);

        $application1 = $attendanceRecord1->applications()->first();
        $this->actingAs($user2)->post('/attendance/'.$attendanceRecord2->id, [
            'new_clock_in' => '09:30',
            'new_clock_out' => '18:30',
            'comment' => '承認済み申請2',
        ]);

        $application2 = $attendanceRecord2->applications()->first();
        $this->actingAs($admin)->post('/stamp_correction_request/approve/'.$application1->id);
        $this->actingAs($admin)->post('/stamp_correction_request/approve/'.$application2->id);

        $response = $this->actingAs($admin)->get('/stamp_correction_request/list');

        $response->assertStatus(200);
        $response->assertSeeInOrder([
            'id="content2"',
            'テストユーザー1',
            '承認済み申請1',
            'テストユーザー2',
            '承認済み申請2',
        ], false);
    }

    public function test_修正申請の詳細内容が正しく表示されている(): void
    {
        $admin = User::factory()->create(['admin_status' => true]);
        $user = User::factory()->create(['name' => 'テストユーザー']);

        $attendanceRecord = $user->attendanceRecords()->create([
            'date' => '2026-10-01',
            'clock_in' => '09:00:00',
            'clock_out' => '18:00:00',
        ]);

        $this->actingAs($user)->post('/attendance/'.$attendanceRecord->id, [
            'new_clock_in' => '08:30',
            'new_clock_out' => '17:30',
            'new_break_in' => ['12:00'],
            'new_break_out' => ['13:00'],
            'comment' => '勤務時間修正',
        ]);

        $application = $attendanceRecord->applications()->first();
        $response = $this->actingAs($admin)->get('/stamp_correction_request/approve/'.$application->id);
        $response->assertStatus(200);
        $response->assertSee('name="name" value="テストユーザー"', false);
        $response->assertSee('2026年');
        $response->assertSee('10月1日');
        $response->assertSeeInOrder([
            '出勤・退勤', 'value="08:30"', 'value="17:30"', 'name="new_break_in[]"', 'value="12:00"', 'name="new_break_out[]"', 'value="13:00"', 'name="comment"', '勤務時間修正',
        ], false);
        $response->assertSee('<button class="applied-form__button--submit" type="submit">承認</button>', false);
    }

    public function test_修正申請の承認処理が正しく行われる(): void
    {
        $admin = User::factory()->create(['admin_status' => true]);
        $user = User::factory()->create();

        $attendanceRecord = $user->attendanceRecords()->create([
            'date' => '2026-10-01',
            'clock_in' => '09:00:00',
            'clock_out' => '18:00:00',
        ]);
        $attendanceRecord->breakRecords()->create([
            'break_in' => '12:00:00',
            'break_out' => '13:00:00',
        ]);

        $this->actingAs($user)->post('/attendance/'.$attendanceRecord->id, [
            'new_clock_in' => '08:30',
            'new_clock_out' => '17:30',
            'new_break_in' => ['11:30'],
            'new_break_out' => ['12:30'],
            'comment' => '承認後データ',
        ]);

        $application = $attendanceRecord->applications()->first();
        $response = $this->actingAs($admin)->post('/stamp_correction_request/approve/'.$application->id);
        $response->assertRedirect('/stamp_correction_request/approve/'.$application->id);
        $this->assertDatabaseHas('applications', [
            'id' => $application->id,
            'approval_status' => '承認済み',
        ]);
        $this->assertDatabaseHas('attendance_records', [
            'id' => $attendanceRecord->id,
            'clock_in' => '08:30',
            'clock_out' => '17:30',
            'comment' => '承認後データ',
        ]);
        $this->assertDatabaseHas('break_records', [
            'attendance_record_id' => $attendanceRecord->id,
            'break_in' => '11:30',
            'break_out' => '12:30',
        ]);

        $response = $this->actingAs($admin)->get('/stamp_correction_request/approve/'.$application->id);
        $response->assertStatus(200);
        $response->assertSee('<p class="applied-form__item">承認済み</p>', false);
    }
}
