<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\AttendanceListService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AdminAttendanceController extends Controller
{
    /**
     * 指定日の全ユーザーの勤怠一覧を表示する。
     */
    public function index(Request $request, AttendanceListService $attendanceListService): View
    {
        $date = $request->query('date')
            ? Carbon::createFromFormat('Y/m/d', $request->query('date'))
            : today();

        $previousDay = $date->copy()->subDay()->format('Y/m/d');
        $nextDay = $date->copy()->addDay()->format('Y/m/d');
        $attendanceRecords = $attendanceListService->getDailyAttendanceRecords($date);
        $users = $attendanceRecords->pluck('user')->unique('id')->values();

        return view(
            'admin.admin-attendance-list',
            compact('date', 'previousDay', 'nextDay', 'users', 'attendanceRecords')
        );
    }
}
