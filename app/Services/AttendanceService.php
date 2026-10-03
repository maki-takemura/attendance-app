<?php

namespace App\Services;

use App\Models\User;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Support\Collection;

class AttendanceService
{
    /**
     * 指定月の勤怠一覧表示用データを作成する。
     */
    public function getMonthlyAttendanceRecords(User $user, Carbon $date): Collection
    {
        $startOfMonth = $date->copy()->startOfMonth();
        $endOfMonth = $date->copy()->endOfMonth();

        $attendanceRecords = $user->attendanceRecords()
            ->with('breakRecords')
            ->whereBetween('date', [
                $startOfMonth->toDateString(),
                $endOfMonth->toDateString(),
            ])
            ->get()
            ->keyBy(fn ($attendanceRecord) => $attendanceRecord->date->toDateString());

        return collect(CarbonPeriod::create($startOfMonth, $endOfMonth))
            ->map(function (Carbon $currentDate) use ($attendanceRecords) {
                $attendanceRecord = $attendanceRecords->get($currentDate->toDateString());

                if (is_null($attendanceRecord)) {
                    return [
                        'id' => null,
                        'date' => $currentDate->translatedFormat('m/d(D)'),
                        'clock_in' => '',
                        'clock_out' => '',
                        'total_break_time' => '',
                        'total_time' => '',
                    ];
                }

                $totalBreakSeconds = $attendanceRecord->breakRecords
                    ->filter(fn ($breakRecord) => ! is_null($breakRecord->break_out))
                    ->sum(function ($breakRecord) {
                        return Carbon::parse($breakRecord->break_in)
                            ->diffInSeconds(Carbon::parse($breakRecord->break_out));
                    });

                $totalBreakTime = $totalBreakSeconds > 0 ? gmdate('H:i', $totalBreakSeconds) : '';
                $totalTime = '';

                if (! is_null($attendanceRecord->clock_out)) {
                    $workSeconds = Carbon::parse($attendanceRecord->clock_in)
                        ->diffInSeconds(Carbon::parse($attendanceRecord->clock_out));

                    $totalTime = gmdate('H:i', $workSeconds - $totalBreakSeconds);
                }

                return [
                    'id' => $attendanceRecord->id,
                    'date' => $currentDate->translatedFormat('m/d(D)'),
                    'clock_in' => Carbon::parse($attendanceRecord->clock_in)->format('H:i'),
                    'clock_out' => $attendanceRecord->clock_out
                        ? Carbon::parse($attendanceRecord->clock_out)->format('H:i')
                        : '',
                    'total_break_time' => $totalBreakTime,
                    'total_time' => $totalTime,
                ];
            });
    }
}
