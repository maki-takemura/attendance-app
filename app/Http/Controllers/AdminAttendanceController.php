<?php

namespace App\Http\Controllers;

use App\Models\AttendanceRecord;
use App\Models\User;
use App\Services\AttendanceService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AdminAttendanceController extends Controller
{
    public function __construct(private AttendanceService $attendanceService) {}

    public function index(Request $request): View
    {
        $date = $this->attendanceService->resolveTargetDate($request->query('date'));
        $users = User::query()->get();
        $attendanceRecords = AttendanceRecord::query()
            ->with('breakRecords')
            ->where('date', $date->format('Y-m-d'))
            ->whereIn('user_id', $users->pluck('id'))
            ->get();

        $attendanceRecords->each(function (AttendanceRecord $attendanceRecord): void {
            $this->attendanceService->attachDailyTotals($attendanceRecord);
        });

        $previousDay = $date->copy()->subDay()->format('Y-m-d');
        $nextDay = $date->copy()->addDay()->format('Y-m-d');

        return view('admin.admin-attendance-list', compact(
            'date',
            'previousDay',
            'nextDay',
            'users',
            'attendanceRecords'
        ));
    }
}
