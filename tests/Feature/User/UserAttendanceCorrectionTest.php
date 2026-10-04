<?php

namespace Tests\Feature\User;

use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserAttendanceCorrectionTest extends TestCase
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

    public function test_出勤時間が退勤時間より後になっている場合エラーメッセージが表示される(): void
    {
        $user = User::factory()->create();
        $attendanceRecord = $user->attendanceRecords()->create([
            'date' => '2026-10-01',
            'clock_in' => '09:00:00',
            'clock_out' => '18:00:00',
        ]);

        $response = $this->actingAs($user)->post('/attendance/'.$attendanceRecord->id, [
            'new_clock_in' => '19:00',
            'new_clock_out' => '18:00',
            'comment' => '修正申請',
        ]);

        $response->assertSessionHasErrors(['new_clock_in' => '出勤時間もしくは退勤時間が不適切な値です']);
    }

    public function test_休憩開始時間が退勤時間より後になっている場合エラーメッセージが表示される(): void
    {
        $user = User::factory()->create();
        $attendanceRecord = $user->attendanceRecords()->create([
            'date' => '2026-10-01',
            'clock_in' => '09:00:00',
            'clock_out' => '18:00:00',
        ]);

        $response = $this->actingAs($user)->post('/attendance/'.$attendanceRecord->id, [
            'new_clock_in' => '09:00',
            'new_clock_out' => '18:00',
            'new_break_in' => ['19:00'],
            'new_break_out' => ['19:30'],
            'comment' => '修正申請',
        ]);

        $response->assertSessionHasErrors(['new_break_in.0' => '休憩時間が不適切な値です']);
    }

    public function test_休憩終了時間が退勤時間より後になっている場合エラーメッセージが表示される(): void
    {
        $user = User::factory()->create();
        $attendanceRecord = $user->attendanceRecords()->create([
            'date' => '2026-10-01',
            'clock_in' => '09:00:00',
            'clock_out' => '18:00:00',
        ]);

        $response = $this->actingAs($user)->post('/attendance/'.$attendanceRecord->id, [
            'new_clock_in' => '09:00',
            'new_clock_out' => '18:00',
            'new_break_in' => ['12:00'],
            'new_break_out' => ['19:00'],
            'comment' => '修正申請',
        ]);

        $response->assertSessionHasErrors(['new_break_out.0' => '休憩時間もしくは退勤時間が不適切な値です']);
    }

    public function test_備考欄が未入力の場合のエラーメッセージが表示される(): void
    {
        $user = User::factory()->create();
        $attendanceRecord = $user->attendanceRecords()->create([
            'date' => '2026-10-01',
            'clock_in' => '09:00:00',
            'clock_out' => '18:00:00',
        ]);

        $response = $this->actingAs($user)->post('/attendance/'.$attendanceRecord->id, [
            'new_clock_in' => '09:00',
            'new_clock_out' => '18:00',
            'comment' => '',
        ]);

        $response->assertSessionHasErrors(['comment' => '備考を記入してください']);
    }

    public function test_修正申請処理が実行される(): void
    {
        $user = User::factory()->create();
        $admin = User::factory()->create(['admin_status' => true]);
        $attendanceRecord = $user->attendanceRecords()->create([
            'date' => '2026-10-01',
            'clock_in' => '09:00:00',
            'clock_out' => '18:00:00',
        ]);

        $this->actingAs($user)->post('/attendance/'.$attendanceRecord->id, [
            'new_clock_in' => '08:30',
            'new_clock_out' => '17:30',
            'comment' => '勤務時間修正',
        ]);
        $application = $attendanceRecord->applications()->first();

        $listResponse = $this->actingAs($admin)->get('/stamp_correction_request/list');

        $listResponse->assertStatus(200);
        $listResponse->assertSee('勤務時間修正');

        $detailResponse = $this->actingAs($admin)->get('/stamp_correction_request/approve/'.$application->id);

        $detailResponse->assertStatus(200);
        $detailResponse->assertSeeInOrder(['08:30', '17:30', '勤務時間修正']);
    }

    public function test_承認待ちにログインユーザーが行った申請が全て表示されていること(): void
    {
        $user = User::factory()->create();
        $attendanceRecord = $user->attendanceRecords()->create([
            'date' => '2026-10-01',
            'clock_in' => '09:00:00',
            'clock_out' => '18:00:00',
        ]);

        $this->actingAs($user)->post('/attendance/'.$attendanceRecord->id, [
            'new_clock_in' => '08:30',
            'new_clock_out' => '17:30',
            'comment' => '承認待ち申請',
        ]);

        $response = $this->actingAs($user)->get('/stamp_correction_request/list');

        $response->assertStatus(200);
        $response->assertSeeInOrder(['id="content1"', '承認待ち申請', 'id="content2"'], false);
    }

    public function test_承認済みに管理者が承認した修正申請が全て表示されている(): void
    {
        $user = User::factory()->create();
        $admin = User::factory()->create(['admin_status' => true]);
        $attendanceRecord = $user->attendanceRecords()->create(['date' => '2026-10-01', 'clock_in' => '09:00:00', 'clock_out' => '18:00:00']);

        $this->actingAs($user)->post('/attendance/'.$attendanceRecord->id, ['new_clock_in' => '08:30', 'new_clock_out' => '17:30', 'comment' => '承認済み申請']);

        $application = $attendanceRecord->applications()->first();

        $this->actingAs($admin)->post('/stamp_correction_request/approve/'.$application->id);

        $response = $this->actingAs($user)->get('/stamp_correction_request/list');
        $response->assertStatus(200);
        $response->assertSeeInOrder(['id="content2"', '承認済み申請'], false);
    }

    public function test_各申請の詳細を押下すると勤怠詳細画面に遷移する(): void
    {
        $user = User::factory()->create();
        $attendanceRecord = $user->attendanceRecords()->create(['date' => '2026-10-01', 'clock_in' => '09:00:00', 'clock_out' => '18:00:00']);

        $this->actingAs($user)->post('/attendance/'.$attendanceRecord->id, ['new_clock_in' => '08:30', 'new_clock_out' => '17:30',         'comment' => '詳細確認申請']);

        $application = $attendanceRecord->applications()->first();

        $listResponse = $this->actingAs($user)->get('/stamp_correction_request/list');

        $listResponse->assertStatus(200);
        $listResponse->assertSee('/application/'.$application->id);

        $detailResponse = $this->actingAs($user)->get('/application/'.$application->id);

        $detailResponse->assertStatus(200);
        $detailResponse->assertSeeInOrder(['08:30', '17:30', '詳細確認申請']);
    }
}
