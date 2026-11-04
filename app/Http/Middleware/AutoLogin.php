<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Hash;
use App\User;
use Illuminate\Support\Facades\Session;

class AutoLogin
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, $next)
    {
        // 0. Check if AutoLogin is explicitly disabled for this request (e.g. Staff Login flow)
        if (Session::has('disable_auto_login')) {
            return $next($request);
        }

        // 1. Check if user is already logged in
        if (Auth::check()) {
            return $next($request);
        }

        // 2. Allow access to auth routes to prevent loops
        if ($request->is('login') || $request->is('staff-login') || $request->is('register') || $request->is('logout') || $request->is('password/*')) {
            return $next($request);
        }

        // 3. Allow guest implementation routes
        if ($request->is('/') || $request->is('welcome*') || $request->routeIs('public.*')) {
            return $next($request);
        }

        // 4. Redirect to Welcome Page instead of auto-provisioning
        if ($request->expectsJson()) {
            return response()->json(['message' => 'Silakan masuk kembali.'], 401);
        }
        return redirect()->route('guest.welcome');
    }
}
