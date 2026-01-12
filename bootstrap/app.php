<?php

use App\Http\Middleware\IsLoggedIn;
use Illuminate\Foundation\Application;
use Illuminate\Session\TokenMismatchException;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(web: __DIR__ . '/../routes/web.php', commands: __DIR__ . '/../routes/console.php', health: '/up')
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->alias([
            'isLoggedIn' => IsLoggedIn::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        $exceptions->render(function (TokenMismatchException $e, $request) {
            // For AJAX / Fetch requests
            if ($request->expectsJson()) {
                return response()->json(
                    [
                        'message' => 'Your session has expired. Please login again.',
                    ],
                    419,
                );
            }

            // For normal form submissions
            return redirect()->route('login')->with('error', 'Your session has expired due to inactivity. Please login again.');
        });
    })
    ->create();
