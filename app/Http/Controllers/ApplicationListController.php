<?php

namespace App\Http\Controllers;

use App\Services\ApplicationService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ApplicationListController extends Controller
{
    /**
     * 申請一覧画面を表示する。
     */
    public function index(Request $request, ApplicationService $applicationService): View
    {
        $user = $request->user();
        $formattedApplications = $applicationService->getFormattedApplications($user);

        return view('user.user-application-list', compact('user', 'formattedApplications'));
    }
}
