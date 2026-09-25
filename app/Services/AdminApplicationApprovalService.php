<?php

namespace App\Services;

use App\Models\Application;
use Illuminate\Support\Facades\DB;

class AdminApplicationApprovalService
{
    public function approve(int $applicationId): void
    {
        DB::transaction(function () use ($applicationId): void {
            $application = Application::query()
                ->whereHas('user', fn ($query) => $query->where('admin_status', false))
                ->with('proposalBreaks')
                ->lockForUpdate()
                ->findOrFail($applicationId);

            if ($application->approval_status !== '承認待ち') {
                return;
            }

            $attendanceRecord = $application->attendanceRecord()->firstOrFail();
            $attendanceRecord->update([
                'clock_in' => $application->new_clock_in,
                'clock_out' => $application->new_clock_out,
                'comment' => $application->comment,
            ]);

            $attendanceRecord->breakRecords()->delete();

            foreach ($application->proposalBreaks as $proposalBreak) {
                $attendanceRecord->breakRecords()->create([
                    'break_in' => $proposalBreak->break_in,
                    'break_out' => $proposalBreak->break_out,
                ]);
            }

            $application->update(['approval_status' => '承認済み']);
        });
    }
}
