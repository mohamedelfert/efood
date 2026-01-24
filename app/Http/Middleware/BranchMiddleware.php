<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\Auth;

class BranchMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        if (Auth::guard('branch')->check() || (Auth::guard('admin')->check() && Auth::guard('admin')->user()->branch_id)) {
            return $next($request);
        }
        return redirect()->route('branch.auth.login');
    }
}
