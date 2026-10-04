<?php

namespace App\Services;

use App\Models\AttendanceRecord;
use App\Models\User;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Support\Collection;

class AttendanceListService
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

                return $this->formatAttendanceRecord($attendanceRecord, $currentDate);
            });
    }

    /**
     * 月次一覧用に勤怠データを整形する。
     */
    private function formatAttendanceRecord(AttendanceRecord $attendanceRecord, Carbon $currentDate): array
    {
        $totalBreakSeconds = $this->calculateTotalBreakSeconds($attendanceRecord);

        return [
            'id' => $attendanceRecord->id,
            'date' => $currentDate->translatedFormat('m/d(D)'),
            'clock_in' => Carbon::parse($attendanceRecord->clock_in)->format('H:i'),
            'clock_out' => $attendanceRecord->clock_out ? Carbon::parse($attendanceRecord->clock_out)->format('H:i') : '',
            'total_break_time' => $totalBreakSeconds > 0 ? gmdate('H:i', $totalBreakSeconds) : '',
            'total_time' => $this->calculateTotalTime($attendanceRecord, $totalBreakSeconds),
        ];
    }

    /**
     * 完了している休憩時間の合計秒数を算出する。
     */
    private function calculateTotalBreakSeconds(AttendanceRecord $attendanceRecord): int
    {
        return $attendanceRecord->breakRecords
            ->filter(fn ($breakRecord) => ! is_null($breakRecord->break_out))
            ->sum(function ($breakRecord) {
                return Carbon::parse($breakRecord->break_in)
                    ->diffInSeconds(Carbon::parse($breakRecord->break_out));
            });
    }

    /**
     * 実勤務時間を算出する。
     */
    private function calculateTotalTime(AttendanceRecord $attendanceRecord, int $totalBreakSeconds): string
    {
        if (is_null($attendanceRecord->clock_out)) {
            return '';
        }

        $workSeconds = Carbon::parse($attendanceRecord->clock_in)
            ->diffInSeconds(Carbon::parse($attendanceRecord->clock_out));

        return gmdate('H:i', $workSeconds - $totalBreakSeconds);
    }
}
