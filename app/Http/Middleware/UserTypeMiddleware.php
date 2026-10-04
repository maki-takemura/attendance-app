<?php

namespace App\Http\Middleware;

use App\Models\AttendanceRecord;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class UserTypeMiddleware
{
    /**
     * 共用URLへのアクセス権を確認する。
     */
    public function handle(Request $request, Closure $next): Response
    {
        $id = $request->route('id');

        if (! is_null($id)) {
            $attendanceRecord = AttendanceRecord::find($id);

            if (is_null($attendanceRecord)) {
                abort(404);
            }

            if (! $request->user()->admin_status && $attendanceRecord->user_id !== $request->user()->id) {
                abort(403);
            }
        }

        return $next($request);
    }
}
