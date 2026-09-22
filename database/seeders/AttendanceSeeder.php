<?php

namespace Database\Seeders;

use App\Models\AttendanceRecord;
use App\Models\BreakRecord;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

class AttendanceSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $currentMonth = Carbon::now()->startOfMonth();
        $user1 = User::where('email', 'user1@example.com')->firstOrFail();
        $user2 = User::where('email', 'user2@example.com')->firstOrFail();
        $user3 = User::where('email', 'user3@example.com')->firstOrFail();

        foreach (range(5, 1) as $monthsAgo) {
            $month = $currentMonth->copy()->subMonthsNoOverflow($monthsAgo);

            foreach ($this->weekdayDates($month, 15) as $date) {
                $this->createAttendance($user1, $date, '09:00:00', '18:00:00');
            }
        }

        $currentMonthPatterns = [
            ...array_fill(0, 10, ['09:00:00', '18:00:00']),
            ...array_fill(0, 3, ['09:00:00', '20:00:00']),
            ...array_fill(0, 2, ['09:30:00', '18:00:00']),
            ['09:00:00', '17:00:00'],
            ['08:00:00', '21:00:00'],
        ];

        foreach ($this->weekdayDates($currentMonth, 17) as $index => $date) {
            [$clockIn, $clockOut] = $currentMonthPatterns[$index];
            $this->createAttendance($user1, $date, $clockIn, $clockOut);
        }

        foreach ([$user2, $user3] as $user) {
            foreach (range(5, 0) as $monthsAgo) {
                $month = $currentMonth->copy()->subMonthsNoOverflow($monthsAgo);

                foreach ($this->weekdayDates($month, 15) as $date) {
                    $this->createAttendance($user, $date, '09:00:00', '18:00:00');
                }
            }
        }
    }

    /**
     * @return array<int, Carbon>
     */
    private function weekdayDates(Carbon $month, int $count): array
    {
        $dates = [];
        $date = $month->copy()->startOfMonth();

        while (count($dates) < $count) {
            if (! $date->isWeekend()) {
                $dates[] = $date->copy();
            }

            $date->addDay();
        }

        return $dates;
    }

    private function createAttendance(
        User $user,
        Carbon $date,
        string $clockIn,
        string $clockOut
    ): void {
        $attendanceRecord = AttendanceRecord::factory()->create([
            'user_id' => $user->id,
            'date' => $date->toDateString(),
            'clock_in' => $clockIn,
            'clock_out' => $clockOut,
            'comment' => null,
        ]);

        BreakRecord::factory()->create([
            'attendance_record_id' => $attendanceRecord->id,
            'break_in' => '12:00:00',
            'break_out' => '13:00:00',
        ]);
    }
}
