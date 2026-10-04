<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\View\View;

class AdminStaffController extends Controller
{
    /**
     * 一般ユーザーのスタッフ一覧を表示する。
     */
    public function index(): View
    {
        $users = User::where('admin_status', false)->get();

        return view('admin.staff-list', compact('users'));
    }
}
