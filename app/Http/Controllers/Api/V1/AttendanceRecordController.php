<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\IndexAttendanceRecordRequest;
use App\Http\Requests\Api\V1\StoreAttendanceRecordRequest;
use App\Http\Requests\Api\V1\UpdateAttendanceRecordRequest;
use App\Http\Resources\AttendanceRecordResource;
use App\Models\AttendanceRecord;
use App\Services\Api\V1\AttendanceRecordService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Symfony\Component\HttpFoundation\Response;

class AttendanceRecordController extends Controller
{
    public function __construct(
        private AttendanceRecordService $attendanceRecordService
    ) {}

    /**
     * 勤怠一覧を取得する。
     */
    public function index(IndexAttendanceRecordRequest $request): AnonymousResourceCollection
    {
        $validated = $request->validated();
        $perPage = $validated['per_page'] ?? 20;

        $attendanceRecords = AttendanceRecord::with(['user', 'breakRecords'])
            ->when(
                isset($validated['user_id']),
                fn ($query) => $query->where('user_id', $validated['user_id'])
            )
            ->when(
                isset($validated['date']),
                fn ($query) => $query->whereDate('date', $validated['date'])
            )
            ->when(
                isset($validated['month']),
                fn ($query) => $query->whereYear(
                    'date',
                    substr($validated['month'], 0, 4)
                )->whereMonth(
                    'date',
                    substr($validated['month'], 5, 2)
                )
            )
            ->latest('date')
            ->paginate($perPage);

        $attendanceRecords->getCollection()->each(function ($attendanceRecord) {
            $attendanceRecord->total_break_time
                = $this->attendanceRecordService
                    ->calculateTotalBreakTime($attendanceRecord);

            $attendanceRecord->total_time
                = $this->attendanceRecordService
                    ->calculateTotalTime($attendanceRecord);

            $attendanceRecord->unsetRelation('breakRecords');
        });

        return AttendanceRecordResource::collection($attendanceRecords);
    }

    /**
     * 勤怠詳細を取得する。
     */
    public function show(AttendanceRecord $attendanceRecord): AttendanceRecordResource
    {
        $attendanceRecord->load([
            'user',
            'breakRecords',
            'applications',
        ]);

        $this->setCalculatedTimes($attendanceRecord);

        return new AttendanceRecordResource($attendanceRecord);
    }

    /**
     * 勤怠を登録する。
     */
    public function store(StoreAttendanceRecordRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $attendanceRecord = $request->user()
            ->attendanceRecords()
            ->create($validated);

        $attendanceRecord->load([
            'user',
            'breakRecords',
        ]);

        $this->setCalculatedTimes($attendanceRecord);

        return (new AttendanceRecordResource($attendanceRecord))
            ->response()
            ->setStatusCode(201);
    }

    /**
     * 勤怠を更新する。
     */
    public function update(UpdateAttendanceRecordRequest $request, AttendanceRecord $attendanceRecord): AttendanceRecordResource
    {
        $this->authorize('update', $attendanceRecord);

        $validated = $request->validated();

        $attendanceRecord->update($validated);

        $attendanceRecord->load([
            'user',
            'breakRecords',
        ]);

        $this->setCalculatedTimes($attendanceRecord);

        return new AttendanceRecordResource($attendanceRecord);
    }

    /**
     * 勤怠を削除する。
     */
    public function destroy(AttendanceRecord $attendanceRecord): Response
    {
        $this->authorize('delete', $attendanceRecord);

        $attendanceRecord->delete();

        return response()->noContent();
    }

    /**
     * 勤務時間と休憩時間を設定する。
     */
    private function setCalculatedTimes(AttendanceRecord $attendanceRecord): void
    {
        $attendanceRecord->total_break_time
            = $this->attendanceRecordService
                ->calculateTotalBreakTime($attendanceRecord);

        $attendanceRecord->total_time
            = $this->attendanceRecordService
                ->calculateTotalTime($attendanceRecord);
    }
}
