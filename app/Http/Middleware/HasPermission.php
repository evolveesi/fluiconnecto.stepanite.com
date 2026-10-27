<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;

class HasPermission
{
    /**
     * Handle an incoming request.
     *
     * @param Request $request
     * @param Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @param array $permission
     * @return Response|RedirectResponse
     */
    public function handle(Request $request, Closure $next, ...$permissions)
    {
        if (!Auth::user()->role) {
            return redirect()->back()->with('error', 'Sorry You Are Not Authorised To Access This Resource. Administrator Has Been Notified On The Breach!');
        }
        $count = true;
        foreach ($permissions as $permission) {
            if (!Auth::user()->hasPermission($permission)) {
                $count = false;
            }
        }
        if (!$count) {
            return redirect()->back()->with('error', 'Sorry You Are Not Permitted To Access This Resource. Administrator Has Been Notified On The Breach!');
        }

        return $next($request);
    }
}
