<?php

declare(strict_types=1);

namespace Josh\LaravelDoctor;

use Illuminate\Support\ServiceProvider;
use Josh\LaravelDoctor\Console\DoctorCommand;

class LaravelDoctorServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/laravel-doctor.php', 'laravel-doctor');
    }

    public function boot(): void
    {
        if (! $this->app->runningInConsole()) {
            return;
        }

        $this->publishes([
            __DIR__.'/../config/laravel-doctor.php' => config_path('laravel-doctor.php'),
        ], 'laravel-doctor-config');

        $this->commands([
            DoctorCommand::class,
        ]);
    }
}
