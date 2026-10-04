<?php

namespace App\Services;

use App\Models\AttendanceRecord;
use Illuminate\Support\Facades\DB;

class AttendanceCorrectionService
{
    /**
     * 管理者による勤怠直接修正を正式データへ反映する。
     */
    public function updateAttendanceRecord(AttendanceRecord $attendanceRecord, array $validated): void
    {
        DB::transaction(function () use ($attendanceRecord, $validated) {
            $attendanceRecord->update([
                'clock_in' => $validated['new_clock_in'],
                'clock_out' => $validated['new_clock_out'],
                'comment' => $validated['comment'],
            ]);

            $breakRecords = $attendanceRecord->breakRecords()->orderBy('id')->get();
            $breakIns = $validated['new_break_in'] ?? [];
            $breakOuts = $validated['new_break_out'] ?? [];

            foreach ($breakIns as $index => $breakIn) {
                $breakOut = $breakOuts[$index] ?? null;

                if ($breakRecords->has($index)) {
                    $breakRecords->get($index)->update([
                        'break_in' => $breakIn,
                        'break_out' => $breakOut,
                    ]);

                    continue;
                }

                if (is_null($breakIn) && is_null($breakOut)) {
                    continue;
                }

                $attendanceRecord->breakRecords()->create([
                    'break_in' => $breakIn,
                    'break_out' => $breakOut,
                ]);
            }
        });
    }
}
