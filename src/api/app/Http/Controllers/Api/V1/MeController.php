<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\MeResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * GET /api/v1/me — the signed-in user.
 *
 * Returned unwrapped (no "data" key), unlike other resources, because
 * nuxt-auth-sanctum stores this response body as the user object.
 */
class MeController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        return response()->json((new MeResource($request->user()))->resolve($request));
    }
}
