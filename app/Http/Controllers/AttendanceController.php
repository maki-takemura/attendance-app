<?php

namespace App\Http\Controllers;

use App\Http\Requests\AttendanceActionRequest;
use App\Http\Requests\AttendanceCorrectionRequest;
use App\Models\AttendanceRecord;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
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

    public function list(Request $request): View
    {
        $date = $this->resolveTargetMonth($request->query('date'));
        $monthStart = $date->copy()->startOfMonth();
        $monthEnd = $date->copy()->endOfMonth();
        $attendanceRecords = $request->user()->attendanceRecords()
            ->with('breakRecords')
            ->whereBetween('date', [
                $monthStart->format('Y-m-d'),
                $monthEnd->format('Y-m-d'),
            ])
            ->get()
            ->keyBy('date');
        $weekdays = ['日', '月', '火', '水', '木', '金', '土'];
        $formattedAttendanceRecords = collect();

        for ($day = $monthStart->copy(); $day->lte($monthEnd); $day->addDay()) {
            $attendanceRecord = $attendanceRecords->get($day->format('Y-m-d'));
            $totalBreakSeconds = $attendanceRecord === null
                ? null
                : $this->calculateTotalBreakSeconds($attendanceRecord);

            $formattedAttendanceRecords->push([
                'id' => $attendanceRecord?->id,
                'date' => $day->format('m/d').'('.$weekdays[$day->dayOfWeek].')',
                'clock_in' => $this->formatTime($attendanceRecord?->clock_in),
                'clock_out' => $this->formatTime($attendanceRecord?->clock_out),
                'total_break_time' => $attendanceRecord !== null && $totalBreakSeconds !== null
                    ? $this->formatDuration($totalBreakSeconds)
                    : null,
                'total_time' => $this->calculateTotalWorkTime(
                    $attendanceRecord,
                    $totalBreakSeconds ?? 0
                ),
            ]);
        }

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
        $user = $request->user();
        $attendanceRecord = $user->attendanceRecords()
            ->with([
                'breakRecords',
                'applications' => fn ($query) => $query
                    ->where('approval_status', '承認待ち')
                    ->with('applicationBreaks'),
            ])
            ->findOrFail($id);
        $application = $attendanceRecord->applications->first();
        $displayDate = Carbon::parse($application?->new_date ?? $attendanceRecord->date);
        $breakRecords = $application?->applicationBreaks ?? $attendanceRecord->breakRecords;

        $data = [
            'id' => $attendanceRecord->id,
            'year' => $displayDate->format('Y年'),
            'date' => $displayDate->format('n月j日'),
            'clock_in' => $this->formatTime($application?->new_clock_in ?? $attendanceRecord->clock_in),
            'clock_out' => $this->formatTime($application?->new_clock_out ?? $attendanceRecord->clock_out),
            'breaks' => $breakRecords->map(fn ($breakRecord) => [
                'break_in' => $this->formatTime($breakRecord->break_in),
                'break_out' => $this->formatTime($breakRecord->break_out),
            ])->values()->all(),
            'comment' => $application?->comment ?? $attendanceRecord->comment,
            'application' => $application,
        ];

        return view('user.user-detail', compact('user', 'data'));
    }

    public function update(AttendanceCorrectionRequest $request, int $id): RedirectResponse
    {
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

    private function resolveTargetMonth(mixed $requestedDate): Carbon
    {
        if (
            ! is_string($requestedDate)
            || preg_match('/^\d{4}-(0[1-9]|1[0-2])$/', $requestedDate) !== 1
        ) {
            return now()->startOfMonth();
        }

        return Carbon::createFromFormat('Y-m-d', $requestedDate.'-01')->startOfMonth();
    }

    private function calculateTotalBreakSeconds(AttendanceRecord $attendanceRecord): ?int
    {
        $completedBreakRecords = $attendanceRecord->breakRecords
            ->whereNotNull('break_out');

        if ($completedBreakRecords->isEmpty()) {
            return null;
        }

        return $completedBreakRecords->sum(function ($breakRecord): int {
            return Carbon::parse($breakRecord->break_in)
                ->diffInSeconds(Carbon::parse($breakRecord->break_out));
        });
    }

    private function calculateTotalWorkTime(
        ?AttendanceRecord $attendanceRecord,
        int $totalBreakSeconds
    ): ?string {
        if ($attendanceRecord?->clock_out === null) {
            return null;
        }

        $workSeconds = Carbon::parse($attendanceRecord->clock_in)
            ->diffInSeconds(Carbon::parse($attendanceRecord->clock_out));

        return $this->formatDuration($workSeconds - $totalBreakSeconds);
    }

    private function formatTime(?string $time): ?string
    {
        return $time === null ? null : Carbon::parse($time)->format('H:i');
    }

    private function formatDuration(int $seconds): string
    {
        $hours = intdiv($seconds, 3600);
        $minutes = intdiv($seconds % 3600, 60);

        return sprintf('%02d:%02d:00', $hours, $minutes);
    }
}
