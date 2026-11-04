<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\Auth;

class EnsureActiveAccount
{
    public function handle($request, Closure $next)
    {
        $user = $request->user();
        if ($user) {
            $version = $request->session()->get('auth_version');
            if (!$user->is_active || ($version !== null && (int) $version !== (int) $user->auth_version)) {
                Auth::logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();
                if ($request->expectsJson()) {
                    return response()->json(['message' => 'Sesi berakhir atau akun dinonaktifkan. Silakan masuk kembali.'], 401);
                }
                return redirect()->route('login')->withErrors(['email' => 'Sesi berakhir atau akun dinonaktifkan. Silakan masuk kembali.']);
            }
            $request->session()->put('auth_version', $user->auth_version);
        }
        return $next($request);
    }
}
