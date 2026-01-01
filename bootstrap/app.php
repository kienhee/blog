<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Khi chưa đăng nhập, chuyển về trang login; Laravel sẽ tự lưu intended URL
        $middleware->redirectGuestsTo(fn (Request $request) => $request->expectsJson() ? null : route('auth.login'));

        // Đăng ký middleware cho Spatie Permission
        $middleware->alias([
            'role' => \Spatie\Permission\Middleware\RoleMiddleware::class,
            'permission' => \Spatie\Permission\Middleware\PermissionMiddleware::class,
            'role_or_permission' => \Spatie\Permission\Middleware\RoleOrPermissionMiddleware::class,
            'prevent.guest.admin' => \App\Http\Middleware\PreventGuestAccessToAdmin::class,
        ]);
        // Trust Cloudflare proxies
        $middleware->trustProxies(
            at: '*',
            headers: Request::HEADER_X_FORWARDED_FOR |
                     Request::HEADER_X_FORWARDED_HOST |
                     Request::HEADER_X_FORWARDED_PORT |
                     Request::HEADER_X_FORWARDED_PROTO |
                     Request::HEADER_X_FORWARDED_AWS_ELB
        );
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // Xử lý lỗi 419 (CSRF token mismatch) - tự động redirect về trang login
        $exceptions->render(function (\Symfony\Component\HttpKernel\Exception\HttpException $e, Request $request) {
            if ($e->getStatusCode() === 419) {
                if ($request->expectsJson()) {
                    return response()->json([
                        'message' => 'Phiên làm việc đã hết hạn. Vui lòng làm mới trang và thử lại.',
                    ], 419);
                }
                
                // Xác định trang login dựa trên route hiện tại
                $path = $request->path();
                if (str_starts_with($path, 'admin') || str_starts_with($path, 'auth')) {
                    // Admin route - redirect về admin login
                    return redirect()->route('auth.login')
                        ->with('error', 'Phiên làm việc đã hết hạn. Vui lòng đăng nhập lại.');
                } else {
                    // Client route - redirect về client login
                    return redirect()->route('client.auth.login')
                        ->with('error', 'Phiên làm việc đã hết hạn. Vui lòng đăng nhập lại.');
                }
            }
        });
    })->create();
