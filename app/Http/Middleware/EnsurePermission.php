<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsurePermission
{
    public function handle(Request $request, Closure $next, string $module): Response
    {
        $user = $request->user();

        abort_unless($user && ($module === 'admin' ? $user->isAdmin() : $user->canAccess($module)), 403);
        abort_if($request->isMethod('delete') && ! $user->canDelete($module), 403);

        return $next($request);
    }
}
