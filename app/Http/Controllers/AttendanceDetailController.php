<?php

namespace App\Http\Controllers;

use App\Http\Requests\AttendanceCorrectionRequest;
use App\Services\ApplicationService;
use App\Services\AttendanceCorrectionService;
use App\Services\AttendanceService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AttendanceDetailController extends Controller
{
    /**
     * 勤怠詳細画面を表示する。
     */
    public function show(Request $request, int $id, AttendanceService $attendanceService, ApplicationService $applicationService): View
    {
        $user = $request->user();

        if ($user->admin_status) {
            $attendanceRecord = $attendanceService->getAttendanceRecordById($id);
            $user = $attendanceRecord->user;
            $application = $applicationService->getPendingApplication($attendanceRecord);

            if (! is_null($application)) {
                $data = $applicationService->getApplicationDetailData($application);

                return view('user.user-detail', compact('user', 'data'));
            }

            $attendanceRecord = $attendanceService->getAdminAttendanceDetailData($attendanceRecord);

            return view('admin.admin-detail', compact('user', 'attendanceRecord'));
        }

        $attendanceRecord = $attendanceService->getAttendanceRecord($user, $id);
        $application = $applicationService->getPendingApplication($attendanceRecord);
        $data = $attendanceService->getAttendanceDetailData($attendanceRecord, $application);

        return view('user.user-detail', compact('user', 'data'));
    }

    /**
     * 勤怠修正処理を行う。
     */
    public function update(AttendanceCorrectionRequest $request, int $id, AttendanceService $attendanceService, ApplicationService $applicationService, AttendanceCorrectionService $attendanceCorrectionService): RedirectResponse
    {
        $user = $request->user();

        if ($user->admin_status) {
            $attendanceRecord = $attendanceService->getAttendanceRecordById($id);
            $application = $applicationService->getPendingApplication($attendanceRecord);

            if (! is_null($application)) {
                abort(403);
            }

            $attendanceCorrectionService->updateAttendanceRecord($attendanceRecord, $request->validated());

            return redirect('/attendance/'.$id);
        }

        $attendanceRecord = $attendanceService->getAttendanceRecord($user, $id);
        $applicationService->createCorrectionApplication($attendanceRecord, $request->validated());

        return redirect('/attendance/'.$id);
    }
}
