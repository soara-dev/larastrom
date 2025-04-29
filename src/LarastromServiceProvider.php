<?php

namespace Soara\Larastrom;

use Illuminate\Support\ServiceProvider;
use Soara\Larastrom\Console\Commands\InstallCommand;


class LarastromServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        $this->commands([
            InstallCommand::class,
        ]);
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        // Publikasi file konfigurasi
        $this->publishes([
            __DIR__.'/../config/jwt.php' => config_path('jwt.php'),
            __DIR__.'/../config/permission.php' => config_path('permission.php'),
        ], 'config');

        // Menambahkan migration Spatie jika diperlukan
        if (class_exists('Spatie\Permission\PermissionServiceProvider')) {
            $this->publishes([
                __DIR__.'/../database/migrations' => database_path('migrations'),
            ], 'migrations');
        }
    }
}
