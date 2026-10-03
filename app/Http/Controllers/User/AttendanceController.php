<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Services\AttendanceService;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AttendanceController extends Controller
{
    /**
     * 勤怠登録画面を表示する。
     */
    public function create(Request $request): View
    {
        $user = $request->user();
        $now = now();

        $formattedDate = $now->translatedFormat('Y年n月j日(D)');
        $formattedTime = $now->format('H:i');

        return view('user.attendance-register', compact('user', 'formattedDate', 'formattedTime'));
    }

    /**
     * 勤怠打刻処理を行う。
     */
    public function store(Request $request): RedirectResponse
    {
        $user = $request->user();
        $now = now();

        switch ($request->input('action')) {
            case 'clock_in':
                if ($user->attendance_status === '勤務外') {
                    $user->attendanceRecords()->create([
                        'date' => $now->toDateString(),
                        'clock_in' => $now->format('H:i:s'),
                    ]);
                }
                break;

            case 'break_in':
                if ($user->attendance_status === '出勤中') {
                    $attendanceRecord = $user->attendanceRecords()->whereDate('date', $now->toDateString())->first();
                    $attendanceRecord?->breakRecords()->create(['break_in' => $now->format('H:i:s')]);
                }
                break;

            case 'break_out':
                if ($user->attendance_status === '休憩中') {
                    $attendanceRecord = $user->attendanceRecords()->whereDate('date', $now->toDateString())->first();
                    $breakRecord = $attendanceRecord?->breakRecords()->whereNull('break_out')->first();
                    $breakRecord?->update(['break_out' => $now->format('H:i:s')]);
                }
                break;

            case 'clock_out':
                if ($user->attendance_status === '出勤中') {
                    $attendanceRecord = $user->attendanceRecords()->whereDate('date', $now->toDateString())->first();
                    $attendanceRecord?->update(['clock_out' => $now->format('H:i:s')]);
                }
                break;
        }

        return redirect('/attendance');
    }

    /**
     * 月次勤怠一覧を表示する。
     */
    public function index(Request $request, AttendanceService $attendanceService): View
    {
        $user = $request->user();

        $date = $request->query('date')
            ? Carbon::createFromFormat('Y/m', $request->query('date'))->startOfMonth()
            : now()->startOfMonth();

        $previousMonth = $date->copy()->subMonth()->format('Y/m');
        $nextMonth = $date->copy()->addMonth()->format('Y/m');
        $formattedAttendanceRecords = $attendanceService->getMonthlyAttendanceRecords($user, $date);

        return view(
            'user.user-attendance-list',
            compact('date', 'previousMonth', 'nextMonth', 'formattedAttendanceRecords')
        );
    }
}
