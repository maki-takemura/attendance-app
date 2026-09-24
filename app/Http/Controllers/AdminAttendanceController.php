<?php

namespace App\Http\Controllers;

use App\Http\Requests\AttendanceCorrectionRequest;
use App\Models\AttendanceRecord;
use App\Models\User;
use App\Services\AdminAttendanceUpdateService;
use App\Services\AttendanceService;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AdminAttendanceController extends Controller
{
    public function __construct(
        private AttendanceService $attendanceService,
        private AdminAttendanceUpdateService $adminAttendanceUpdateService
    ) {}

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

    public function show(int $id): View
    {
        $record = AttendanceRecord::query()
            ->with(['user', 'breakRecords'])
            ->findOrFail($id);
        $user = $record->user;
        $date = Carbon::parse($record->date);
        $attendanceRecord = [
            'id' => $record->id,
            'year' => $date->format('Y年'),
            'date' => $date->format('n月j日'),
            'clock_in' => $record->clock_in === null
                ? null : Carbon::parse($record->clock_in)->format('H:i'),
            'clock_out' => $record->clock_out === null
                ? null : Carbon::parse($record->clock_out)->format('H:i'),
            'breaks' => $record->breakRecords->map(fn ($breakRecord) => [
                'break_in' => Carbon::parse($breakRecord->break_in)->format('H:i'),
                'break_out' => $breakRecord->break_out === null
                    ? null : Carbon::parse($breakRecord->break_out)->format('H:i'),
            ])->values()->all(),
            'comment' => $record->comment,
        ];

        return view('admin.admin-detail', compact('attendanceRecord', 'user'));
    }

    public function update(AttendanceCorrectionRequest $request, int $id): RedirectResponse
    {
        $record = AttendanceRecord::query()->findOrFail($id);
        $this->adminAttendanceUpdateService->update($record, $request->validated());

        return redirect('/attendance/'.$id);
    }
}
