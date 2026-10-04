<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\ApplicationService;
use App\Services\AttendanceCorrectionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class AdminApplicationController extends Controller
{
    /**
     * 修正申請承認画面を表示する。
     */
    public function show(int $attendance_correct_request_id, ApplicationService $applicationService): View
    {
        $application = $applicationService->getAdminApplication($attendance_correct_request_id);
        $user = $application->attendanceRecord->user;

        return view('admin.admin-application-detail', compact('user', 'application'));
    }

    /**
     * 修正申請を承認し、正式な勤怠情報へ反映する。
     */
    public function approve(
        int $attendance_correct_request_id,
        ApplicationService $applicationService,
        AttendanceCorrectionService $attendanceCorrectionService
    ): RedirectResponse {
        $application = $applicationService->getAdminApplication($attendance_correct_request_id);
        $attendanceCorrectionService->approveApplication($application);

        return redirect('/stamp_correction_request/approve/'.$application->id);
    }
}
