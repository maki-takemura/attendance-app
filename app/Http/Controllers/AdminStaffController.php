<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\AttendanceService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AdminStaffController extends Controller
{
    public function __construct(private AttendanceService $attendanceService) {}

    public function index(): View
    {
        $users = User::query()
            ->where('admin_status', false)
            ->get(['id', 'name', 'email']);

        return view('admin.staff-list', compact('users'));
    }

    public function showAttendance(Request $request, int $id): View
    {
        $user = User::query()->findOrFail($id);
        $date = $this->attendanceService->resolveTargetMonth($request->query('date'));
        $formattedAttendanceRecords = $this->attendanceService
            ->buildMonthlyAttendanceRecords($user, $date);
        $previousMonth = $date->copy()->subMonth()->format('Y-m');
        $nextMonth = $date->copy()->addMonth()->format('Y-m');

        return view('admin.staff-attendance-list', compact(
            'user',
            'date',
            'previousMonth',
            'nextMonth',
            'formattedAttendanceRecords'
        ));
    }
}
