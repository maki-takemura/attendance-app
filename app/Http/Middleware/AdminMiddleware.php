<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AdminMiddleware
{
    /**
     * 管理者ユーザーのみアクセスを許可する。
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->user()->admin_status) {
            abort(403);
        }

        return $next($request);
    }
}
