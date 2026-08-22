<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AdminWebMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        if (!auth('web')->check() || !auth('web')->user()->isAdmin()) {
            return redirect('/login')->with('error', 'Unauthorized access');
        }

        return $next($request);
    }
}
