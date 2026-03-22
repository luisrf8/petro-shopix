<?php

namespace App\Providers;

use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use League\Flysystem\Filesystem;
use Masbug\FlysystemGoogleDrive\GoogleDriveAdapter;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        ResetPassword::createUrlUsing(function (object $notifiable, string $token) {
            return config('app.frontend_url')."/password-reset/$token?email={$notifiable->getEmailForPasswordReset()}";
        });

        View::composer('*', function ($view) {
            $view->with('authUser', auth()->user());
        });

        try {
            Storage::extend('google', function ($app, $config) {
                $client = new \Google\Client();
                $client->setAuthConfig(base_path(env('GOOGLE_DRIVE_CREDENTIALS_PATH')));
                $client->addScope(\Google\Service\Drive::DRIVE);

                $service = new \Google\Service\Drive($client);
                $adapter = new GoogleDriveAdapter($service, env('GOOGLE_DRIVE_FOLDER_ID'));

                return new \Illuminate\Filesystem\FilesystemAdapter(
                    new Filesystem($adapter, $config),
                    $adapter,
                    $config
                );
            });
        } catch (\Exception $e) {
            // Prevent app boot failure while credentials are not available yet.
        }
    }
}
