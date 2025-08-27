<?php

namespace App\Http\Middleware\V1;

use App\Http\Responses\V1\ApiResponse;
use Closure;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redis;

class ThrottleVerificationEmails
{
    public function handle($request, Closure $next)
    {
            // Solo aplicar a la ruta específica
                $userId = Auth::guard('sanctum')->user()->id;
                $redisKey = "user:{$userId}:verification_emails";

                $data = Redis::hgetall($redisKey);
                $attempts = $data['attempts'] ?? 0;

                if ($attempts >= 2) {
                    return ApiResponse::error("Has superado el límite de intentos de verificación. Por favor, inténtalo más tarde.", 429);
                }

                Redis::hincrby($redisKey, 'attempts', 1);
                Redis::expire($redisKey, 3600);
            return $next($request);
    }
}
