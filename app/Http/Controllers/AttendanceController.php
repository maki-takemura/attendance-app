<?php

namespace App\Http\Controllers;

use App\Http\Requests\AttendanceActionRequest;
use App\Models\AttendanceRecord;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AttendanceController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();
        $now = now();
        $attendanceRecord = $user->attendanceRecords()
            ->with('breakRecords')
            ->where('date', $now->format('Y-m-d'))
            ->first();

        $user->setAttribute(
            'attendance_status',
            $this->determineAttendanceStatus($attendanceRecord)
        );

        $weekdays = ['日', '月', '火', '水', '木', '金', '土'];
        $formattedDate = $now->format('Y年n月j日').'('.$weekdays[$now->dayOfWeek].')';
        $formattedTime = $now->format('H:i');

        return view('user.attendance-register', compact(
            'user',
            'formattedDate',
            'formattedTime'
        ));
    }

    public function store(AttendanceActionRequest $request): RedirectResponse
    {
        $user = $request->user();
        $now = now();
        $attendanceRecord = $user->attendanceRecords()
            ->with(['breakRecords' => fn ($query) => $query->whereNull('break_out')])
            ->where('date', $now->format('Y-m-d'))
            ->first();
        $openBreakRecord = $attendanceRecord?->breakRecords->first();
        $action = $request->validated('action');

        if ($action === 'clock_in' && $attendanceRecord === null) {
            $user->attendanceRecords()->create([
                'date' => $now->format('Y-m-d'),
                'clock_in' => $now->format('H:i:s'),
            ]);
        }

        if (
            $action === 'break_in'
            && $attendanceRecord !== null
            && $attendanceRecord->clock_out === null
            && $openBreakRecord === null
        ) {
            $attendanceRecord->breakRecords()->create([
                'break_in' => $now->format('H:i:s'),
            ]);
        }

        if (
            $action === 'break_out'
            && $attendanceRecord !== null
            && $attendanceRecord->clock_out === null
            && $openBreakRecord !== null
        ) {
            $openBreakRecord->update([
                'break_out' => $now->format('H:i:s'),
            ]);
        }

        if (
            $action === 'clock_out'
            && $attendanceRecord !== null
            && $attendanceRecord->clock_out === null
            && $openBreakRecord === null
        ) {
            $attendanceRecord->update([
                'clock_out' => $now->format('H:i:s'),
            ]);
        }

        return redirect('/attendance');
    }

    private function determineAttendanceStatus(?AttendanceRecord $attendanceRecord): string
    {
        if ($attendanceRecord === null) {
            return '勤務外';
        }

        if ($attendanceRecord->clock_out !== null) {
            return '退勤済';
        }

        if ($attendanceRecord->breakRecords->contains(
            fn ($breakRecord) => $breakRecord->break_out === null
        )) {
            return '休憩中';
        }

        return '出勤中';
    }
}
