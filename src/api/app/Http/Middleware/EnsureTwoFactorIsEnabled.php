<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * System Administrators must turn on two-factor login before they can use
 * admin routes (ADR 0004). Other roles may turn it on but are not required to.
 */
class EnsureTwoFactorIsEnabled
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user instanceof User && $user->requiresTwoFactor() && ! $user->hasEnabledTwoFactor()) {
            return response()->json([
                'message' => 'Turn on two-factor login to continue.',
                'code' => 'two_factor_required',
            ], 403);
        }

        return $next($request);
    }
}
