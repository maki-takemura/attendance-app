<?php

namespace App\Services;

use App\Models\Application;
use App\Models\AttendanceRecord;
use App\Models\User;
use Carbon\Carbon;

class AttendanceService
{
    /**
     * ログインユーザー自身の指定勤怠を取得する。
     */
    public function getAttendanceRecord(User $user, int $id): AttendanceRecord
    {
        return $user->attendanceRecords()->with('breakRecords')->findOrFail($id);
    }

    /**
     * 勤怠詳細表示用データを作成する。
     */
    public function getAttendanceDetailData(AttendanceRecord $attendanceRecord, ?Application $application): array
    {
        return [
            'id' => $attendanceRecord->id,
            'year' => $attendanceRecord->date->format('Y年'),
            'date' => $attendanceRecord->date->format('n月j日'),
            'clock_in' => Carbon::parse($attendanceRecord->clock_in)->format('H:i'),
            'clock_out' => $attendanceRecord->clock_out ? Carbon::parse($attendanceRecord->clock_out)->format('H:i') : '',
            'breaks' => $attendanceRecord->breakRecords->map(fn ($breakRecord) => [
                'break_in' => Carbon::parse($breakRecord->break_in)->format('H:i'),
                'break_out' => $breakRecord->break_out ? Carbon::parse($breakRecord->break_out)->format('H:i') : '',
            ])->toArray(),
            'comment' => $application?->comment ?? '',
            'application' => $application,
        ];
    }
}
