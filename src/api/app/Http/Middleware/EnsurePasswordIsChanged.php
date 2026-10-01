<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Blocks admin routes until a user replaces the temporary password they were
 * given on account creation or after an administrator reset.
 */
class EnsurePasswordIsChanged
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->user()?->must_change_password) {
            return response()->json([
                'message' => 'Change your temporary password to continue.',
                'code' => 'password_change_required',
            ], 403);
        }

        return $next($request);
    }
}
