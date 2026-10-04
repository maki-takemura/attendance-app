<?php

namespace Tests\Feature\User;

use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserAttendanceListTest extends TestCase
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

    public function test_自分が行った勤怠情報が全て表示されている(): void
    {
        $user = User::factory()->create();

        $user->attendanceRecords()->create([
            'date' => '2026-10-01',
            'clock_in' => '09:00:00',
            'clock_out' => '18:00:00',
        ]);

        $user->attendanceRecords()->create([
            'date' => '2026-10-02',
            'clock_in' => '10:00:00',
            'clock_out' => '19:00:00',
        ]);

        $response = $this->actingAs($user)->get('/attendance/list');

        $response->assertStatus(200);
        $response->assertSee('10/01(木)');
        $response->assertSee('09:00');
        $response->assertSee('18:00');
        $response->assertSee('10/02(金)');
        $response->assertSee('10:00');
        $response->assertSee('19:00');
    }

    public function test_勤怠一覧画面に遷移した際に現在の月が表示される(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/attendance/list');

        $response->assertStatus(200);
        $response->assertSee('2026/10');
    }

    public function test_前月を押下した時に表示月の前月の情報が表示される(): void
    {
        $user = User::factory()->create();

        $user->attendanceRecords()->create([
            'date' => '2026-09-15',
            'clock_in' => '08:30:00',
            'clock_out' => '17:30:00',
        ]);

        $response = $this->actingAs($user)->get('/attendance/list?date=2026/09');

        $response->assertStatus(200);
        $response->assertSee('2026/09');
        $response->assertSee('08:30');
        $response->assertSee('17:30');
    }

    public function test_翌月を押下した時に表示月の翌月の情報が表示される(): void
    {
        $user = User::factory()->create();

        $user->attendanceRecords()->create([
            'date' => '2026-11-15',
            'clock_in' => '10:30:00',
            'clock_out' => '19:30:00',
        ]);

        $response = $this->actingAs($user)->get('/attendance/list?date=2026/11');

        $response->assertStatus(200);
        $response->assertSee('2026/11');
        $response->assertSee('10:30');
        $response->assertSee('19:30');
    }

    public function test_詳細を押下するとその日の勤怠詳細画面に遷移する(): void
    {
        $user = User::factory()->create();

        $attendanceRecord = $user->attendanceRecords()->create([
            'date' => '2026-10-01',
            'clock_in' => '09:00:00',
            'clock_out' => '18:00:00',
        ]);

        $response = $this->actingAs($user)->get('/attendance/list');

        $response->assertStatus(200);
        $response->assertSee('/attendance/'.$attendanceRecord->id);

        $response = $this->actingAs($user)->get('/attendance/'.$attendanceRecord->id);

        $response->assertStatus(200);
    }
}
