<?php

namespace App\Services\Api\V1;

use App\Models\AttendanceRecord;
use Carbon\Carbon;

class AttendanceRecordService
{
    /**
     * 完了している休憩時間の合計を H:i 形式で返す。
     */
    public function calculateTotalBreakTime(AttendanceRecord $attendanceRecord): string
    {
        $totalBreakSeconds = $this->calculateTotalBreakSeconds($attendanceRecord);

        return $totalBreakSeconds > 0
            ? gmdate('H:i', $totalBreakSeconds)
            : '';
    }

    /**
     * 実勤務時間を H:i 形式で返す。
     */
    public function calculateTotalTime(AttendanceRecord $attendanceRecord): string
    {
        if (is_null($attendanceRecord->clock_out)) {
            return '';
        }

        $totalBreakSeconds = $this->calculateTotalBreakSeconds($attendanceRecord);

        $workSeconds = Carbon::parse($attendanceRecord->clock_in)
            ->diffInSeconds(Carbon::parse($attendanceRecord->clock_out));

        return gmdate('H:i', $workSeconds - $totalBreakSeconds);
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
}
