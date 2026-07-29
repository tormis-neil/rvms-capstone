<?php

namespace App\Providers;

use App\Services\Fcm\FcmTransport;
use App\Services\Fcm\FcmTransportFactory;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        /*
         | The real HTTP v1 sender only when the project id, a readable
         | service-account key AND the library that reads it are all present;
         | otherwise pushes are written to the log (FR-21).
         |
         | This is what lets every notification trigger be built and tested
         | before Firebase exists: the notification row is still written and
         | still shows on the bell, only the push is simulated. It also means a
         | missing or mistyped credentials path degrades to "no push" instead of
         | throwing on every damage report.
         |
         | The decision itself lives in FcmTransportFactory so that the REASON
         | for a fallback is recorded and can be read back by
         | `php artisan rvms:fcm-doctor` — a silent fallback is what let a
         | missing vendor package masquerade as a broken queue.
         */
        $this->app->singleton(FcmTransportFactory::class);

        $this->app->singleton(
            FcmTransport::class,
            fn ($app) => $app->make(FcmTransportFactory::class)->make(),
        );
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
