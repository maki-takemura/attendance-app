<?php

namespace Tests\Feature\Attendance;

use App\Models\AttendanceRecord;
use App\Models\BreakRecord;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminStaffAttendanceTest extends TestCase
{
    use RefreshDatabase;

    private Carbon $currentMonth;

    protected function setUp(): void
    {
        parent::setUp();

        $now = now('Asia/Tokyo');
        Carbon::setTestNow($now);
        $this->currentMonth = $now->copy()->startOfMonth();
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_admin_can_see_names_and_email_addresses_of_all_general_users(): void
    {
        $admin = User::factory()->admin()->create([
            'name' => '管理者太郎',
            'email' => 'admin@example.com',
        ]);
        $firstStaff = User::factory()->create([
            'name' => '一般花子',
            'email' => 'hanako@example.com',
        ]);
        $secondStaff = User::factory()->create([
            'name' => '一般次郎',
            'email' => 'jiro@example.com',
        ]);

        $response = $this->actingAs($admin)->get('/admin/staff/list');

        $response->assertOk();
        $response->assertSeeText($firstStaff->name);
        $response->assertSeeText($firstStaff->email);
        $response->assertSeeText($secondStaff->name);
        $response->assertSeeText($secondStaff->email);
        $response->assertDontSeeText($admin->name);
        $response->assertDontSeeText($admin->email);
    }

    public function test_selected_user_attendance_is_displayed_accurately(): void
    {
        $admin = User::factory()->admin()->create();
        $staff = User::factory()->create(['name' => '一般花子']);
        $otherStaff = User::factory()->create();
        $date = $this->currentMonth->copy()->addDays(4);
        $attendance = AttendanceRecord::factory()->for($staff)->create([
            'date' => $date->toDateString(),
            'clock_in' => '09:00:00',
            'clock_out' => '18:00:00',
            'comment' => '通常勤務',
        ]);
        BreakRecord::factory()->for($attendance)->create([
            'break_in' => '12:00:00',
            'break_out' => '13:00:00',
        ]);
        $secondDate = $this->currentMonth->copy()->addDays(11);
        AttendanceRecord::factory()->for($staff)->create([
            'date' => $secondDate->toDateString(),
            'clock_in' => '10:00:00',
            'clock_out' => '19:00:00',
            'comment' => '別日の勤務',
        ]);
        AttendanceRecord::factory()->for($otherStaff)->create([
            'date' => $date->toDateString(),
            'clock_in' => '07:15:00',
            'clock_out' => '21:30:00',
            'comment' => '比較用',
        ]);

        $this->actingAs($admin)
            ->get('/admin/staff/list')
            ->assertSee('href="'.url('/admin/attendance/staff/'.$staff->id).'"', false);
        $response = $this->get('/admin/attendance/staff/'.$staff->id);

        $response->assertOk();
        $response->assertSeeText($staff->name.'さんの勤怠');
        $response->assertSeeText($this->currentMonth->format('Y/m'));
        $response->assertSeeInOrder([
            $this->formattedDate($date),
            '09:00',
            '18:00',
            '1:00',
            '8:00',
            $this->formattedDate($secondDate),
            '10:00',
            '19:00',
        ]);
        $response->assertDontSee('07:15');
        $response->assertDontSee('21:30');
    }

    public function test_previous_month_attendance_is_displayed(): void
    {
        $admin = User::factory()->admin()->create();
        $staff = User::factory()->create();
        $previousMonth = $this->currentMonth->copy()->subMonthNoOverflow();
        $previousDate = $previousMonth->copy()->addDays(4);
        AttendanceRecord::factory()->for($staff)->create([
            'date' => $previousDate->toDateString(),
            'clock_in' => '08:15:00',
            'clock_out' => '17:15:00',
            'comment' => '前月勤務',
        ]);
        AttendanceRecord::factory()->for($staff)->create([
            'date' => $this->currentMonth->copy()->addDays(4)->toDateString(),
            'clock_in' => '09:45:00',
            'clock_out' => '18:45:00',
            'comment' => '当月勤務',
        ]);

        $path = '/admin/attendance/staff/'.$staff->id;
        $this->actingAs($admin)
            ->get($path)
            ->assertSee('href="?date='.$previousMonth->format('Y-m').'"', false);
        $response = $this->get($path.'?date='.$previousMonth->format('Y-m'));

        $response->assertOk();
        $response->assertSeeText($previousMonth->format('Y/m'));
        $response->assertSeeInOrder([$this->formattedDate($previousDate), '08:15', '17:15']);
        $response->assertDontSee('09:45');
        $response->assertDontSee('18:45');
    }

    public function test_next_month_attendance_is_displayed(): void
    {
        $admin = User::factory()->admin()->create();
        $staff = User::factory()->create();
        $nextMonth = $this->currentMonth->copy()->addMonthNoOverflow();
        $nextDate = $nextMonth->copy()->addDays(4);
        AttendanceRecord::factory()->for($staff)->create([
            'date' => $nextDate->toDateString(),
            'clock_in' => '10:15:00',
            'clock_out' => '19:15:00',
            'comment' => '翌月勤務',
        ]);
        AttendanceRecord::factory()->for($staff)->create([
            'date' => $this->currentMonth->copy()->addDays(4)->toDateString(),
            'clock_in' => '09:45:00',
            'clock_out' => '18:45:00',
            'comment' => '当月勤務',
        ]);

        $path = '/admin/attendance/staff/'.$staff->id;
        $this->actingAs($admin)
            ->get($path)
            ->assertSee('href="?date='.$nextMonth->format('Y-m').'"', false);
        $response = $this->get($path.'?date='.$nextMonth->format('Y-m'));

        $response->assertOk();
        $response->assertSeeText($nextMonth->format('Y/m'));
        $response->assertSeeInOrder([$this->formattedDate($nextDate), '10:15', '19:15']);
        $response->assertDontSee('09:45');
        $response->assertDontSee('18:45');
    }

    public function test_detail_link_opens_selected_day_attendance_detail(): void
    {
        $admin = User::factory()->admin()->create();
        $staff = User::factory()->create(['name' => '一般花子']);
        $date = $this->currentMonth->copy()->addDays(4);
        $attendance = AttendanceRecord::factory()->for($staff)->create([
            'date' => $date->toDateString(),
            'clock_in' => '09:00:00',
            'clock_out' => '18:00:00',
            'comment' => '通常勤務',
        ]);

        $this->actingAs($admin)
            ->get('/admin/attendance/staff/'.$staff->id)
            ->assertSee('href="'.url('/attendance/'.$attendance->id).'"', false);
        $response = $this->get('/attendance/'.$attendance->id);

        $response->assertOk();
        $response->assertViewIs('admin.admin-detail');
        $response->assertSee('value="'.$staff->name.'"', false);
        $response->assertSee('value="'.$date->format('Y年').'"', false);
        $response->assertSee('value="'.$date->format('n月j日').'"', false);
    }

    private function formattedDate(Carbon $date): string
    {
        $weekdays = ['日', '月', '火', '水', '木', '金', '土'];

        return $date->format('m/d').'('.$weekdays[$date->dayOfWeek].')';
    }
}
