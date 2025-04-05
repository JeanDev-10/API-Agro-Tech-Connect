<?php

namespace App\Http\Middleware\V1;

use App\Http\Responses\V1\ApiResponse;
use Closure;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;

class ThrottleRecoveryPasswords
{
    public function handle($request, Closure $next)
    {
        // Obtener la IP del cliente
        $clientIp = $request->ip();

        // Si no podemos obtener la IP, permitir la solicitud (con registro de advertencia)
        if (empty($clientIp)) {
            Log::warning('No se pudo determinar la IP para throttling de recuperación de contraseña');
            return $next($request);
        }

        $redisKey = "recovery_passwords:ip:{$clientIp}";

        // Obtener intentos y timestamp del primer intento
        $data = Redis::hgetall($redisKey);
        $attempts = $data['attempts'] ?? 0;
        $firstAttempt = $data['first_attempt'] ?? now()->timestamp;

        // Si ha pasado más de 1 hora, resetear el contador
        if (now()->timestamp - $firstAttempt > 3600) {
            $attempts = 0;
            $firstAttempt = now()->timestamp;
        }

        if ($attempts >= 2) {
            $retryAfter = 3600 - (now()->timestamp - $firstAttempt);
            return ApiResponse::error(
                "Has alcanzado el límite de 2 intentos por hora. Por favor, inténtalo de nuevo en ".ceil($retryAfter/60)." minutos.",
                429,
                ['retry_after' => $retryAfter]
            );
        }

        // Actualizar en Redis
        Redis::hmset($redisKey, [
            'attempts' => $attempts + 1,
            'first_attempt' => $firstAttempt,
            'email_attempted' => $request->email // Opcional: registrar email intentado
        ]);

        // Expirar la clave después de 1 hora
        Redis::expire($redisKey, 3600);

        return $next($request);
    }
}
