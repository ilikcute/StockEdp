<?php

namespace App\Shared\Providers;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

class FeatureRouteServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $featuresPath = app_path('Features');

        if (! File::isDirectory($featuresPath)) {
            return;
        }

        Route::prefix('api/v1')
            ->middleware('api')
            ->group(function () use ($featuresPath): void {
                foreach (File::directories($featuresPath) as $featureDirectory) {
                    $routeFile = $featureDirectory.DIRECTORY_SEPARATOR.'Routes'.DIRECTORY_SEPARATOR.'api.php';

                    if (File::exists($routeFile)) {
                        require $routeFile;
                    }
                }
            });
    }
}
