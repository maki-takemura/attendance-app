<?php

namespace Tests\Feature\User;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserAttendanceDetailTest extends TestCase
{
    use RefreshDatabase;

    public function test_勤怠詳細画面の名前がログインユーザーの氏名になっている(): void
    {
        $user = User::factory()->create([
            'name' => 'テストユーザー',
        ]);

        $attendanceRecord = $user->attendanceRecords()->create([
            'date' => '2026-10-01',
            'clock_in' => '09:00:00',
            'clock_out' => '18:00:00',
        ]);

        $response = $this->actingAs($user)->get('/attendance/'.$attendanceRecord->id);

        $response->assertStatus(200);
        $response->assertSee(
            'name="name" value="テストユーザー"',
            false
        );
    }

    public function test_勤怠詳細画面の日付が選択した日付になっている(): void
    {
        $user = User::factory()->create();

        $attendanceRecord = $user->attendanceRecords()->create([
            'date' => '2026-10-01',
            'clock_in' => '09:00:00',
            'clock_out' => '18:00:00',
        ]);

        $response = $this->actingAs($user)->get('/attendance/'.$attendanceRecord->id);

        $response->assertStatus(200);
        $response->assertSee(
            'class="form__input form__input--date" type="text" value="2026年"',
            false
        );
        $response->assertSee(
            'name="new_date" value="10月1日"',
            false
        );
    }

    public function test_出勤退勤にて記されている時間がログインユーザーの打刻と一致している(): void
    {
        $user = User::factory()->create();

        $attendanceRecord = $user->attendanceRecords()->create([
            'date' => '2026-10-01',
            'clock_in' => '09:00:00',
            'clock_out' => '18:00:00',
        ]);

        $response = $this->actingAs($user)->get('/attendance/'.$attendanceRecord->id);

        $response->assertStatus(200);
        $response->assertSee(
            'name="new_clock_in" value="09:00"',
            false
        );
        $response->assertSee(
            'name="new_clock_out" value="18:00"',
            false
        );
    }

    public function test_休憩にて記されている時間がログインユーザーの打刻と一致している(): void
    {
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

        $response = $this->actingAs($user)->get('/attendance/'.$attendanceRecord->id);

        $response->assertStatus(200);
        $response->assertSee(
            'name="new_break_in[0]" value="12:00"',
            false
        );
        $response->assertSee(
            'name="new_break_out[0]" value="13:00"',
            false
        );
    }
}
