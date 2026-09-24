<?php

namespace App\Services;

use App\Models\Application;
use App\Models\AttendanceRecord;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class AttendanceService
{
    public function determineAttendanceStatus(?AttendanceRecord $attendanceRecord): string
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

    public function resolveTargetMonth(mixed $requestedDate): Carbon
    {
        if (
            ! is_string($requestedDate)
            || preg_match('/^\d{4}-(0[1-9]|1[0-2])$/', $requestedDate) !== 1
        ) {
            return now()->startOfMonth();
        }

        return Carbon::createFromFormat('Y-m-d', $requestedDate.'-01')->startOfMonth();
    }

    public function resolveTargetDate(mixed $requestedDate): Carbon
    {
        if (! is_string($requestedDate) || preg_match('/^\d{4}-\d{2}-\d{2}$/', $requestedDate) !== 1) {
            return now()->startOfDay();
        }

        [$year, $month, $day] = array_map('intval', explode('-', $requestedDate));
        if (! checkdate($month, $day, $year)) {
            return now()->startOfDay();
        }

        return Carbon::createFromFormat('Y-m-d', $requestedDate)->startOfDay();
    }

    public function attachDailyTotals(AttendanceRecord $attendanceRecord): void
    {
        $totalBreakSeconds = $this->calculateTotalBreakSeconds($attendanceRecord);
        $attendanceRecord->setAttribute(
            'total_break_time',
            $totalBreakSeconds === null ? null : $this->formatDuration($totalBreakSeconds)
        );
        $attendanceRecord->setAttribute(
            'total_time',
            $this->calculateTotalWorkTime($attendanceRecord, $totalBreakSeconds ?? 0)
        );
    }

    public function buildMonthlyAttendanceRecords(User $user, Carbon $date): Collection
    {
        $monthStart = $date->copy()->startOfMonth();
        $monthEnd = $date->copy()->endOfMonth();
        $attendanceRecords = $user->attendanceRecords()
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
            $formattedAttendanceRecords->push(
                $this->formatAttendanceRecord($attendanceRecord, $day, $weekdays)
            );
        }

        return $formattedAttendanceRecords;
    }

    public function formatAttendanceDetail(AttendanceRecord $attendanceRecord): array
    {
        $application = $attendanceRecord->applications->first();

        return $this->formatDetail($attendanceRecord, $application);
    }

    public function formatApplicationDetail(Application $application): array
    {
        return $this->formatDetail($application->attendanceRecord, $application);
    }

    private function formatDetail(
        AttendanceRecord $attendanceRecord,
        ?Application $application
    ): array {
        $displayDate = Carbon::parse($application?->new_date ?? $attendanceRecord->date);
        $breakRecords = $application?->applicationBreaks ?? $attendanceRecord->breakRecords;

        return [
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
    }

    private function formatAttendanceRecord(
        ?AttendanceRecord $attendanceRecord,
        Carbon $day,
        array $weekdays
    ): array {
        $totalBreakSeconds = $attendanceRecord === null
            ? null
            : $this->calculateTotalBreakSeconds($attendanceRecord);

        return [
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
        ];
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
