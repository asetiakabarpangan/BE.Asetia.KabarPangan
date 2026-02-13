<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CheckTokenExpiry
{
    public function handle(Request $request, Closure $next)
    {
        $token = $request->user()?->currentAccessToken();
        if (!$token) {
            return new JsonResponse([
                'success' => false,
                'message' => 'Token tidak ditemukan.'
            ], 401);
        }
        if ($token->expires_at && $token->expires_at->isPast()) {
            $token->delete();
            return new JsonResponse([
                'success' => false,
                'message' => 'Token expired.'
            ], 401);
        }
        return $next($request);
    }
}
