<?php

namespace App\Exceptions;

use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Http\Exceptions\PostTooLargeException;
use Illuminate\Http\Request;
use Illuminate\Session\TokenMismatchException;
use Throwable;

class Handler extends ExceptionHandler
{
    /**
     * A list of the exception types that are not reported.
     *
     * @var array<int, class-string<Throwable>>
     */
    protected $dontReport = [
        //
    ];

    /**
     * A list of the inputs that are never flashed for validation exceptions.
     *
     * @var array<int, string>
     */
    protected $dontFlash = [
        'current_password',
        'password',
        'password_confirmation',
    ];

    /**
     * Register the exception handling callbacks for the application.
     *
     * @return void
     */
    public function register()
    {
        $this->reportable(function (Throwable $e) {
            //
        });

        $this->renderable(function (PostTooLargeException $e, Request $request) {
            $message = 'La solicitud enviada es demasiado grande para el servidor. Reduce el peso total de los archivos e intenta nuevamente.';

            if ($request->expectsJson() || $request->is('api/*')) {
                return response()->json([
                    'success' => false,
                    'message' => $message,
                ], 413);
            }

            return redirect()->back()->withInput()->withErrors([
                'upload' => $message,
            ]);
        });

        $this->renderable(function (\RuntimeException $e, Request $request) {
            if (!str_starts_with((string) $e->getMessage(), '[PDF]')) {
                return null;
            }

            $message = trim((string) preg_replace('/^\[PDF\]\s*/', '', (string) $e->getMessage()));
            if ($message === '') {
                $message = 'No se pudo generar el PDF en este momento. Intenta nuevamente con un rango mas corto.';
            }

            if ($request->expectsJson() || $request->is('api/*')) {
                return response()->json([
                    'success' => false,
                    'message' => $message,
                    'code' => 'pdf_generation_failed',
                ], 422);
            }

            return redirect()->back()->with('warning', $message);
        });

        $this->renderable(function (TokenMismatchException $e, Request $request) {
            $message = 'Tu sesion vencio por seguridad. Inicia sesion nuevamente para continuar.';

            if ($request->expectsJson() || $request->is('api/*')) {
                return response()->json([
                    'success' => false,
                    'message' => $message,
                    'login_url' => route('login'),
                    'should_refresh' => true,
                ], 419);
            }

            return redirect()->guest(route('login'))->with('status', $message);
        });
    }
}
