<?php

namespace Database\Seeders;

use App\Models\AttendanceRecord;
use App\Models\BreakRecord;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class AttendanceSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $user1 = User::where('email', 'user1@example.com')->firstOrFail();
        $user2 = User::where('email', 'user2@example.com')->firstOrFail();
        $user3 = User::where('email', 'user3@example.com')->firstOrFail();

        $pastDates = [];

        // 過去5ヶ月：各月の平日を月初から15日取得
        for ($monthsAgo = 5; $monthsAgo >= 1; $monthsAgo--) {
            $month = now()->subMonths($monthsAgo)->startOfMonth();

            $pastDates = array_merge($pastDates, $this->getWeekdays($month, 15));
        }

        // 当月：平日を月初から17日取得
        $currentMonthDates = $this->getWeekdays(now()->startOfMonth(), 17);

        $allDates = array_merge($pastDates, $currentMonthDates);

        // user1：過去5ヶ月 75日の通常勤務
        foreach ($pastDates as $date) {
            $this->createAttendance(
                $user1,
                $date,
                '09:00:00',
                '18:00:00'
            );
        }

        // user1：当月10日の通常勤務
        foreach (array_slice($currentMonthDates, 0, 10) as $date) {
            $this->createAttendance(
                $user1,
                $date,
                '09:00:00',
                '18:00:00'
            );
        }

        // user1：当月3日の残業
        foreach (array_slice($currentMonthDates, 10, 3) as $date) {
            $this->createAttendance(
                $user1,
                $date,
                '09:00:00',
                '20:00:00'
            );
        }

        // user1：当月2日の遅刻
        foreach (array_slice($currentMonthDates, 13, 2) as $date) {
            $this->createAttendance(
                $user1,
                $date,
                '09:30:00',
                '18:00:00'
            );
        }

        // user1：当月1日の早退
        $this->createAttendance(
            $user1,
            $currentMonthDates[15],
            '09:00:00',
            '17:00:00'
        );

        // user1：当月1日の長時間労働
        $this->createAttendance(
            $user1,
            $currentMonthDates[16],
            '08:00:00',
            '21:00:00'
        );

        // user2・user3：user1と同じ92日をすべて通常勤務
        foreach ([$user2, $user3] as $user) {
            foreach ($allDates as $date) {
                $this->createAttendance(
                    $user,
                    $date,
                    '09:00:00',
                    '18:00:00'
                );
            }
        }
    }

    /**
     * 指定した月の平日を月初から指定件数取得する。
     */
    private function getWeekdays(Carbon $month, int $count): array
    {
        $dates = [];
        $date = $month->copy();

        while (count($dates) < $count && $date->month === $month->month) {
            if ($date->isWeekday()) {
                $dates[] = $date->toDateString();
            }

            $date->addDay();
        }

        return $dates;
    }

    /**
     * 勤怠と固定休憩を作成する。
     */
    private function createAttendance(User $user, string $date, string $clockIn, string $clockOut): void
    {
        $attendanceRecord = AttendanceRecord::factory()->create([
            'user_id' => $user->id,
            'date' => $date,
            'clock_in' => $clockIn,
            'clock_out' => $clockOut,
        ]);

        BreakRecord::factory()->create([
            'attendance_record_id' => $attendanceRecord->id,
            'break_in' => '12:00:00',
            'break_out' => '13:00:00',
        ]);
    }
}
