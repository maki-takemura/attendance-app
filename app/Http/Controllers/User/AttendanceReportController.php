<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\AttendanceRecord;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\View\View;

class AttendanceReportController extends Controller
{
    /**
     * マイ勤怠レポートを表示する。
     */
    public function index(Request $request): View
    {
        $currentMonth = now()->startOfMonth();
        $startDate = $currentMonth->copy()->subMonths(5);
        $endDate = $currentMonth->copy()->endOfMonth();

        $attendanceRecords = $request->user()
            ->attendanceRecords()
            ->with('breakRecords')
            ->whereBetween('date', [$startDate, $endDate])
            ->whereNotNull('clock_out')
            ->whereDoesntHave('breakRecords', function ($query) {
                $query->whereNull('break_out');
            })
            ->get();

        $dailyReports = $attendanceRecords->map(function (AttendanceRecord $attendanceRecord): array {
            $workMinutes = $this->calculateWorkMinutes($attendanceRecord);

            return [
                'date' => $attendanceRecord->date,
                'clock_in' => $attendanceRecord->clock_in,
                'clock_out' => $attendanceRecord->clock_out,
                'work_minutes' => $workMinutes,
                'overtime_minutes' => max($workMinutes - 480, 0),
            ];
        });

        $summary = $this->buildSummary($dailyReports);
        $monthlyTrend = $this->buildMonthlyTrend($dailyReports, $currentMonth);
        $anomalies = $this->buildAnomalies($dailyReports, $currentMonth);

        return view('reports.index', compact(
            'summary',
            'monthlyTrend',
            'anomalies'
        ));
    }

    /**
     * 1日の実労働時間を分単位で算出する。
     */
    private function calculateWorkMinutes(AttendanceRecord $attendanceRecord): int
    {
        $clockIn = Carbon::parse($attendanceRecord->clock_in);
        $clockOut = Carbon::parse($attendanceRecord->clock_out);

        $breakMinutes = $attendanceRecord->breakRecords->sum(
            function ($breakRecord): int {
                return Carbon::parse($breakRecord->break_in)
                    ->diffInMinutes(Carbon::parse($breakRecord->break_out));
            }
        );

        return $clockIn->diffInMinutes($clockOut) - $breakMinutes;
    }

    /**
     * 基本サマリーを作成する。
     */
    private function buildSummary(Collection $dailyReports): array
    {
        $totalWorkMinutes = $dailyReports->sum('work_minutes');
        $totalOvertimeMinutes = $dailyReports->sum('overtime_minutes');

        $avgWorkMinutes = $dailyReports->isEmpty()
            ? 0
            : intdiv($totalWorkMinutes, $dailyReports->count());

        return [
            'total_work_minutes' => $totalWorkMinutes,
            'total_overtime_minutes' => $totalOvertimeMinutes,
            'avg_work_minutes' => $avgWorkMinutes,
        ];
    }

    /**
     * 過去6ヶ月の月次推移を作成する。
     */
    private function buildMonthlyTrend(Collection $dailyReports, Carbon $currentMonth): Collection
    {
        $reportsByMonth = $dailyReports->groupBy(
            fn (array $report): string => $report['date']->format('Y-m')
        );

        return collect(range(5, 0))->map(
            function (int $monthsAgo) use ($reportsByMonth, $currentMonth): array {
                $month = $currentMonth->copy()->subMonths($monthsAgo);
                $monthKey = $month->format('Y-m');
                $monthlyReports = $reportsByMonth->get($monthKey, collect());

                return [
                    'month' => $month->format('Y年n月'),
                    'work_minutes' => $monthlyReports->sum('work_minutes'),
                    'overtime_minutes' => $monthlyReports->sum('overtime_minutes'),
                ];
            }
        );
    }

    /**
     * 今月の異常検知回数を作成する。
     */
    private function buildAnomalies(Collection $dailyReports, Carbon $currentMonth): array
    {
        $currentMonthReports = $dailyReports->filter(
            fn (array $report): bool => $report['date']->format('Y-m') === $currentMonth->format('Y-m')
        );

        return [
            'late_count' => $currentMonthReports->filter(
                fn (array $report): bool => $report['clock_in'] > '09:00:00'
            )->count(),

            'early_leave_count' => $currentMonthReports->filter(
                fn (array $report): bool => $report['clock_out'] < '18:00:00'
            )->count(),

            'long_work_count' => $currentMonthReports->filter(
                fn (array $report): bool => $report['work_minutes'] > 600
            )->count(),
        ];
    }
}
