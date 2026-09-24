<?php

namespace App\Http\Controllers;

use App\Http\Requests\AttendanceActionRequest;
use App\Http\Requests\AttendanceCorrectionRequest;
use App\Services\AttendanceService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class AttendanceController extends Controller
{
    public function __construct(
        private AttendanceService $attendanceService,
        private AdminAttendanceController $adminAttendanceController
    ) {}

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
            $this->attendanceService->determineAttendanceStatus($attendanceRecord)
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

    public function list(Request $request): View
    {
        $date = $this->attendanceService->resolveTargetMonth($request->query('date'));
        $formattedAttendanceRecords = $this->attendanceService
            ->buildMonthlyAttendanceRecords($request->user(), $date);
        $previousMonth = $date->copy()->subMonth()->format('Y-m');
        $nextMonth = $date->copy()->addMonth()->format('Y-m');

        return view('user.user-attendance-list', compact(
            'date',
            'previousMonth',
            'nextMonth',
            'formattedAttendanceRecords'
        ));
    }

    public function show(Request $request, int $id): View
    {
        if ($request->user()->admin_status === true) {
            return $this->adminAttendanceController->show($id);
        }

        $user = $request->user();
        $attendanceRecord = $user->attendanceRecords()
            ->with([
                'breakRecords',
                'applications' => fn ($query) => $query
                    ->where('approval_status', '承認待ち')
                    ->with('applicationBreaks'),
            ])
            ->findOrFail($id);
        $data = $this->attendanceService->formatAttendanceDetail($attendanceRecord);

        return view('user.user-detail', compact('user', 'data'));
    }

    public function update(AttendanceCorrectionRequest $request, int $id): RedirectResponse
    {
        if ($request->user()->admin_status === true) {
            return $this->adminAttendanceController->update($request, $id);
        }

        $user = $request->user();
        $attendanceRecord = $user->attendanceRecords()->findOrFail($id);
        $redirectPath = '/attendance/'.$attendanceRecord->id;

        if ($attendanceRecord->applications()
            ->where('approval_status', '承認待ち')
            ->exists()
        ) {
            return redirect($redirectPath);
        }

        $validated = $request->validated();

        DB::transaction(function () use ($attendanceRecord, $user, $validated): void {
            $application = $attendanceRecord->applications()->create([
                'user_id' => $user->id,
                'new_date' => $attendanceRecord->date,
                'new_clock_in' => $validated['new_clock_in'],
                'new_clock_out' => $validated['new_clock_out'],
                'comment' => $validated['comment'],
                'approval_status' => '承認待ち',
            ]);

            $breakIns = $validated['new_break_in'] ?? [];
            $breakOuts = $validated['new_break_out'] ?? [];
            $breakIndexes = array_unique(array_merge(
                array_keys($breakIns),
                array_keys($breakOuts)
            ));

            foreach ($breakIndexes as $index) {
                $breakIn = $breakIns[$index] ?? null;
                $breakOut = $breakOuts[$index] ?? null;

                if ($breakIn === null && $breakOut === null) {
                    continue;
                }

                $application->applicationBreaks()->create([
                    'break_in' => $breakIn,
                    'break_out' => $breakOut,
                ]);
            }
        });

        return redirect($redirectPath);
    }
}
