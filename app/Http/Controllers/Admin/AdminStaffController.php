<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\AttendanceListService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AdminStaffController extends Controller
{
    /**
     * 一般ユーザーのスタッフ一覧を表示する。
     */
    public function index(): View
    {
        $users = User::where('admin_status', false)->get();

        return view('admin.staff-list', compact('users'));
    }

    /**
     * 指定ユーザーの月次勤怠一覧を表示する。
     */
    public function show(Request $request, int $id, AttendanceListService $attendanceListService): View
    {
        $user = User::where('admin_status', false)->findOrFail($id);

        $date = $request->query('date')
            ? Carbon::createFromFormat('Y/m', $request->query('date'))->startOfMonth()
            : now()->startOfMonth();

        $previousMonth = $date->copy()->subMonth()->format('Y/m');
        $nextMonth = $date->copy()->addMonth()->format('Y/m');
        $formattedAttendanceRecords = $attendanceListService->getMonthlyAttendanceRecords($user, $date);

        return view(
            'admin.staff-attendance-list',
            compact('user', 'date', 'previousMonth', 'nextMonth', 'formattedAttendanceRecords')
        );
    }
}
