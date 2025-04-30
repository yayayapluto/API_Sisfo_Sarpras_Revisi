<?php

namespace App\Http\Middleware;

use App\Custom\Formatter;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class RolePermission
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, ...$roles): Response
    {
        $currentUser = Auth::guard("sanctum")->user();

        $userRole = $currentUser->role;

        if (!in_array($userRole, $roles) || is_null($userRole)) {
            return Formatter::apiResponse(403, "You are not authorized");
        }

        $request->merge(["user" => $currentUser]);

        return $next($request);
    }
}
