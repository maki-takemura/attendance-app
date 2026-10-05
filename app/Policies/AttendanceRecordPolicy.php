<?php

namespace App\Policies;

use App\Models\AttendanceRecord;
use App\Models\User;

class AttendanceRecordPolicy
{
    /**
     * 勤怠情報を更新できるか判定する。
     */
    public function update(User $user, AttendanceRecord $attendanceRecord): bool
    {
        return $user->id === $attendanceRecord->user_id
            || $user->admin_status;
    }

    /**
     * 勤怠情報を削除できるか判定する。
     */
    public function delete(User $user, AttendanceRecord $attendanceRecord): bool
    {
        return $user->id === $attendanceRecord->user_id
            || $user->admin_status;
    }
}
