<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\HttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        api: __DIR__ . '/../routes/api.php',
        health: '/',
        apiPrefix: '/api',
    )
    ->withMiddleware(function (Middleware $middleware) {
        //
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
        // 常に JSON を返す
        $exceptions->shouldRenderJsonWhen(fn () => true);

        // 例外ごとにレスポンスの形式を定義する
        $exceptions
            ->render(fn (ValidationException $e) => new JsonResponse(['message' => 'バリデーションエラー', 'errors' => $e->errors()], $e->status))
            ->render(fn (NotFoundHttpException $e) => new JsonResponse(['message' => 'リソースが見つかりません'], 404))
            ->render(fn (HttpException $e) => new JsonResponse(['message' => $e->getMessage()], $e->getStatusCode()))
            ->render(fn (Throwable $e) => new JsonResponse(['message' => $e->getMessage()], 500));
        

    })->create();
