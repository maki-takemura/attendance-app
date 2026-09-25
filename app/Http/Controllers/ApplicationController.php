<?php

namespace App\Http\Controllers;

use App\Models\Application;
use App\Services\AttendanceService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ApplicationController extends Controller
{
    public function __construct(private AttendanceService $attendanceService) {}

    public function index(Request $request): View
    {
        $user = $request->user();

        if ($user->admin_status === true) {
            $applications = Application::query()
                ->whereHas('user', fn ($query) => $query->where('admin_status', false))
                ->with(['user', 'attendanceRecord'])
                ->get();

            $applications->each(function (Application $application): void {
                $application->setRelation('AttendanceRecord', $application->attendanceRecord);
                $application->setAttribute('application_date', $application->created_at);
            });

            return view('admin.admin-application-list', compact('applications'));
        }

        $applications = $user->applications()->get();
        $formattedApplications = $applications->map(fn ($application) => [
            'id' => $application->id,
            'approval_status' => $application->approval_status,
            'date' => Carbon::parse($application->new_date)->format('Y/m/d'),
            'comment' => $application->comment,
            'application_date' => $application->created_at->format('Y/m/d'),
        ]);

        return view('user.user-application-list', compact(
            'user',
            'formattedApplications'
        ));
    }

    public function show(Request $request, int $id): View
    {
        $user = $request->user();
        $application = $user->applications()
            ->with(['proposalBreaks', 'attendanceRecord'])
            ->findOrFail($id);
        $data = $this->attendanceService->formatApplicationDetail($application);

        return view('user.user-detail', compact('user', 'data'));
    }
}
