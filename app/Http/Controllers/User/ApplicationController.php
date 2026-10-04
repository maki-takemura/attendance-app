<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Services\ApplicationService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ApplicationController extends Controller
{
    /**
     * 申請詳細画面を表示する。
     */
    public function show(Request $request, int $application_id, ApplicationService $applicationService): View
    {
        $user = $request->user();
        $application = $applicationService->getApplication($user, $application_id);
        $data = $applicationService->getApplicationDetailData($application);

        return view('user.user-detail', compact('user', 'data'));
    }
}
