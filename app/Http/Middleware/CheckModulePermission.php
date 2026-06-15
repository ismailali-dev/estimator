<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckModulePermission
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next, $permissionSlug): Response
    {
        $user = auth()->user();

        // Allow if user is admin
        if ($user && $user->is_admin) {
            return $next($request);
        }

        // Check if user has the given permission
        $hasPermission = $user && $user->modulePermissions()->where('slug', $permissionSlug)->exists();

        if (!$hasPermission) {
            return response()->json([
                'status' => false,
                'message' => 'Access denied: Missing permission `' . $permissionSlug . '`',
            ], 403);
        }

        return $next($request);
    }
}
