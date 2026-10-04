<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminAttendanceDetailTest extends TestCase
{
    use RefreshDatabase;

    public function test_勤怠詳細画面に表示されるデータが選択したものになっている(): void
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

        $response = $this->actingAs($admin)->get('/attendance/'.$attendanceRecord->id);

        $response->assertStatus(200);
        $response->assertSee(
            'name="name" value="テストユーザー"',
            false
        );
        $response->assertSee(
            'class="form__input form__input--date" type="text" value="2026年"',
            false
        );
        $response->assertSee(
            'name="new_date" value="10月1日"',
            false
        );
        $response->assertSeeInOrder([
            'name="new_clock_in"',
            'value="09:00"',
            'name="new_clock_out"',
            'value="18:00"',
            'name="new_break_in[0]"',
            'value="12:00"',
            'name="new_break_out[0]"',
            'value="13:00"',
        ], false);
    }

    public function test_出勤時間が退勤時間より後になっている場合エラーメッセージが表示される(): void
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

        $response = $this->actingAs($admin)->post('/attendance/'.$attendanceRecord->id, [
            'new_clock_in' => '19:00',
            'new_clock_out' => '18:00',
            'comment' => '修正',
        ]);

        $response->assertSessionHasErrors([
            'new_clock_in' => '出勤時間もしくは退勤時間が不適切な値です',
        ]);
    }

    public function test_休憩開始時間が退勤時間より後になっている場合エラーメッセージが表示される(): void
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

        $response = $this->actingAs($admin)->post('/attendance/'.$attendanceRecord->id, [
            'new_clock_in' => '09:00',
            'new_clock_out' => '18:00',
            'new_break_in' => ['19:00'],
            'new_break_out' => ['19:30'],
            'comment' => '修正',
        ]);

        $response->assertSessionHasErrors([
            'new_break_in.0' => '休憩時間が不適切な値です',
        ]);
    }

    public function test_休憩終了時間が退勤時間より後になっている場合エラーメッセージが表示される(): void
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

        $response = $this->actingAs($admin)->post('/attendance/'.$attendanceRecord->id, [
            'new_clock_in' => '09:00',
            'new_clock_out' => '18:00',
            'new_break_in' => ['12:00'],
            'new_break_out' => ['19:00'],
            'comment' => '修正',
        ]);

        $response->assertSessionHasErrors([
            'new_break_out.0' => '休憩時間もしくは退勤時間が不適切な値です',
        ]);
    }

    public function test_備考欄が未入力の場合のエラーメッセージが表示される(): void
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

        $response = $this->actingAs($admin)->post('/attendance/'.$attendanceRecord->id, [
            'new_clock_in' => '09:00',
            'new_clock_out' => '18:00',
            'comment' => '',
        ]);

        $response->assertSessionHasErrors([
            'comment' => '備考を記入してください',
        ]);
    }
}
