<?php

namespace App\Services;

use App\Models\AttendanceRecord;
use Illuminate\Support\Facades\DB;

class AdminAttendanceUpdateService
{
    public function update(AttendanceRecord $record, array $validated): void
    {
        DB::transaction(function () use ($record, $validated): void {
            $record->update([
                'clock_in' => $validated['new_clock_in'],
                'clock_out' => $validated['new_clock_out'],
                'comment' => $validated['comment'],
            ]);

            $record->breakRecords()->delete();

            $breakIns = $validated['new_break_in'] ?? [];
            $breakOuts = $validated['new_break_out'] ?? [];

            foreach ($breakIns as $index => $breakIn) {
                $breakOut = $breakOuts[$index] ?? null;

                if ($breakIn === null || $breakIn === '' || $breakOut === null || $breakOut === '') {
                    continue;
                }

                $record->breakRecords()->create([
                    'break_in' => $breakIn,
                    'break_out' => $breakOut,
                ]);
            }
        });
    }
}
