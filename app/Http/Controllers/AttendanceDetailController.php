<?php

namespace App\Http\Controllers;

use App\Http\Requests\AttendanceCorrectionRequest;
use App\Services\ApplicationService;
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
        $attendanceRecord = $attendanceService->getAttendanceRecord($user, $id);
        $application = $applicationService->getPendingApplication($attendanceRecord);
        $data = $attendanceService->getAttendanceDetailData($attendanceRecord, $application);

        return view('user.user-detail', compact('user', 'data'));
    }

    /**
     * 勤怠修正処理を行う。
     */
    public function update(AttendanceCorrectionRequest $request, int $id, AttendanceService $attendanceService, ApplicationService $applicationService): RedirectResponse
    {
        $attendanceRecord = $attendanceService->getAttendanceRecord($request->user(), $id);
        $applicationService->createCorrectionApplication($attendanceRecord, $request->validated());

        return redirect('/attendance/'.$id);
    }
}
