<?php

use App\Http\Middleware\V1\EmailVerification;
use App\Http\Middleware\V1\ForceJsonRequestHeader;
use App\Http\Middleware\V1\ThrottleVerificationEmails;
use App\Http\Responses\V1\ApiResponse;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Auth\Middleware\EnsureEmailIsVerified;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__ . '/../routes/web.php',
        api: __DIR__ . '/../routes/api.php',
        commands: __DIR__ . '/../routes/console.php',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->statefulApi();

        $middleware->alias([
            'role' => \Spatie\Permission\Middleware\RoleMiddleware::class,
            'permission' => \Spatie\Permission\Middleware\PermissionMiddleware::class,
            'role_or_permission' => \Spatie\Permission\Middleware\RoleOrPermissionMiddleware::class,
        ]);
        $middleware->append(ForceJsonRequestHeader::class);
    })
    ->withExceptions(function (Exceptions $exceptions) {
         $exceptions->render(function (AuthenticationException $e, Request $request) {
            return ApiResponse::error("No Autenticado", 401);
        });
        $exceptions->render(function (\Spatie\Permission\Exceptions\UnauthorizedException $e, $request) {
            return ApiResponse::error("No tienes permiso para acceder a este recurso", 403);
        });
        $exceptions->render(function (ThrottleRequestsException $e, $request) {
            return ApiResponse::error("Has excedido el numero máximo permitido de peticiones, intenta después",429);
        });
    })->create();
