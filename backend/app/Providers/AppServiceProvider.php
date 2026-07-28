<?php

namespace App\Providers;

use App\Services\Fcm\FcmHttpV1Transport;
use App\Services\Fcm\FcmTransport;
use App\Services\Fcm\LogFcmTransport;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        /*
         | The real HTTP v1 sender only when BOTH the project id and a readable
         | service-account key are configured; otherwise pushes are written to
         | the log (FR-21).
         |
         | This is what lets every notification trigger be built and tested
         | before Firebase exists: the notification row is still written and
         | still shows on the bell, only the push is simulated. It also means a
         | missing or mistyped credentials path degrades to "no push" instead of
         | throwing on every damage report.
         */
        $this->app->singleton(FcmTransport::class, function ($app) {
            $projectId = config('services.fcm.project_id');
            $credentials = config('services.fcm.credentials');

            if (blank($projectId) || blank($credentials)) {
                return new LogFcmTransport;
            }

            $path = str_starts_with($credentials, DIRECTORY_SEPARATOR)
                ? $credentials
                : base_path($credentials);

            if (! is_readable($path)) {
                return new LogFcmTransport;
            }

            return new FcmHttpV1Transport($projectId, $path);
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
