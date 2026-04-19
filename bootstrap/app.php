<?php

/*
|--------------------------------------------------------------------------
| Create The Application
|--------------------------------------------------------------------------
|
| The first thing we will do is create a new Laravel application instance
| which serves as the "glue" for all the components of Laravel, and is
| the IoC container for the system binding all of the various parts.
|
*/

$app = new Illuminate\Foundation\Application(
    $_ENV['APP_BASE_PATH'] ?? dirname(__DIR__)
);

/*
|--------------------------------------------------------------------------
| Bind Important Interfaces
|--------------------------------------------------------------------------
|
| Next, we need to bind some important interfaces into the container so
| we will be able to resolve them when needed. The kernels serve the
| incoming requests to this application from both the web and CLI.
|
*/

$app->singleton(
    Illuminate\Contracts\Http\Kernel::class,
    App\Http\Kernel::class
);

$app->singleton(
    Illuminate\Contracts\Console\Kernel::class,
    App\Console\Kernel::class
);

$app->singleton(
    Illuminate\Contracts\Debug\ExceptionHandler::class,
    App\Exceptions\Handler::class
);

/*
|--------------------------------------------------------------------------
| Return The Application
|--------------------------------------------------------------------------
|
| This script returns the application instance. The instance is given to
| the calling script so we can separate the building of the instances
| from the actual running of the application and sending responses.
|
*/

spl_autoload_register(static function (string $class): void {
    $basePath = dirname(__DIR__);
    $prefixes = [
        'NotificationChannels\\WebPush\\' => $basePath . '/vendor/laravel-notification-channels/webpush/src/',
        'Minishlink\\WebPush\\' => $basePath . '/vendor/minishlink/web-push/src/',
        'Base64Url\\' => $basePath . '/vendor/spomky-labs/base64url/src/',
        'SpomkyLabs\\Pki\\' => $basePath . '/vendor/spomky-labs/pki-framework/src/',
        'Jose\\Component\\' => $basePath . '/vendor/web-token/jwt-library/',
    ];

    foreach ($prefixes as $prefix => $path) {
        if (!str_starts_with($class, $prefix)) {
            continue;
        }

        $relativeClass = substr($class, strlen($prefix));
        $relativePath = str_replace('\\', '/', $relativeClass) . '.php';
        $filePath = $path . $relativePath;

        if (is_file($filePath)) {
            require_once $filePath;
        }
    }
});

return $app;
