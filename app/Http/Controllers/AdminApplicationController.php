<?php

namespace App\Http\Controllers;

use App\Models\Application;
use App\Services\AdminApplicationApprovalService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class AdminApplicationController extends Controller
{
    public function __construct(private AdminApplicationApprovalService $approvalService) {}

    public function show(int $attendance_correct_request_id): View
    {
        $application = Application::query()
            ->whereHas('user', fn ($query) => $query->where('admin_status', false))
            ->with(['user', 'proposalBreaks'])
            ->findOrFail($attendance_correct_request_id);
        $user = $application->user;

        return view('admin.admin-application-detail', compact('application', 'user'));
    }

    public function approve(int $attendance_correct_request_id): RedirectResponse
    {
        $this->approvalService->approve($attendance_correct_request_id);

        return redirect('/stamp_correction_request/approve/'.$attendance_correct_request_id);
    }
}
