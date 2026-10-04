<?php

namespace App\Services;

use App\Models\Application;
use App\Models\AttendanceRecord;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class ApplicationService
{
    /**
     * 対象勤怠の承認待ち申請を取得する。
     */
    public function getPendingApplication(AttendanceRecord $attendanceRecord): ?Application
    {
        return $attendanceRecord->applications()
            ->where('approval_status', '承認待ち')->latest('id')->first();
    }

    /**
     * 勤怠修正申請を登録する。
     */
    public function createCorrectionApplication(AttendanceRecord $attendanceRecord, array $validated): Application
    {
        return DB::transaction(function () use ($attendanceRecord, $validated) {
            $application = $attendanceRecord->applications()->create([
                'application_date' => now()->toDateString(),
                'new_clock_in' => $validated['new_clock_in'],
                'new_clock_out' => $validated['new_clock_out'],
                'comment' => $validated['comment'],
                'approval_status' => '承認待ち',
            ]);

            foreach ($validated['new_break_in'] ?? [] as $index => $breakIn) {
                $breakOut = $validated['new_break_out'][$index] ?? null;

                if (is_null($breakIn) && is_null($breakOut)) {
                    continue;
                }

                $application->proposalBreaks()->create([
                    'break_in' => $breakIn,
                    'break_out' => $breakOut,
                ]);
            }

            return $application;
        });
    }

    /**
     * ログインユーザー自身の申請一覧表示用データを作成する。
     */
    public function getFormattedApplications(User $user): Collection
    {
        return Application::with('attendanceRecord')
            ->whereHas('attendanceRecord', fn ($query) => $query->where('user_id', $user->id))
            ->get()
            ->map(fn ($application) => [
                'id' => $application->id,
                'approval_status' => $application->approval_status,
                'date' => $application->attendanceRecord->date->format('Y/m/d'),
                'comment' => $application->comment,
                'application_date' => $application->application_date->format('Y/m/d'),
            ]);
    }

    /**
     * ログインユーザー自身の指定申請を取得する。
     */
    public function getApplication(User $user, int $applicationId): Application
    {
        return Application::with(['attendanceRecord', 'proposalBreaks'])
            ->whereHas('attendanceRecord', fn ($query) => $query->where('user_id', $user->id))
            ->findOrFail($applicationId);
    }

    /**
     * 申請詳細表示用データを作成する。
     */
    public function getApplicationDetailData(Application $application): array
    {
        return [
            'id' => $application->attendanceRecord->id,
            'year' => $application->attendanceRecord->date->format('Y年'),
            'date' => $application->attendanceRecord->date->format('n月j日'),
            'clock_in' => Carbon::parse($application->new_clock_in)->format('H:i'),
            'clock_out' => Carbon::parse($application->new_clock_out)->format('H:i'),
            'breaks' => $application->proposalBreaks->map(fn ($proposalBreak) => [
                'break_in' => Carbon::parse($proposalBreak->break_in)->format('H:i'),
                'break_out' => $proposalBreak->break_out ? Carbon::parse($proposalBreak->break_out)->format('H:i') : '',
            ])->toArray(),
            'comment' => $application->comment,
            'application' => $application->approval_status === '承認待ち' ? $application : null,
        ];
    }

    /**
     * 管理者用の全一般ユーザー申請一覧を取得する。
     */
    public function getAdminApplications(): Collection
    {
        return Application::with('attendanceRecord.user')
            ->whereHas('attendanceRecord.user', fn ($query) => $query->where('admin_status', false))
            ->get()
            ->each(function ($application) {
                $application->setRelation('AttendanceRecord', $application->attendanceRecord);
            });
    }
}
