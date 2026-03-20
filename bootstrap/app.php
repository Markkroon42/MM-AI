<?php

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->redirectGuestsTo('/login');
        $middleware->web(append: [
            \App\Http\Middleware\SetLocale::class,
        ]);
    })
    ->withSchedule(function (Schedule $schedule): void {
        // Sprint 5: Orchestration & Reporting Schedule

        // Run due scheduled tasks every minute
        $schedule->command('orchestration:run-due-tasks')
            ->everyMinute()
            ->withoutOverlapping()
            ->runInBackground();

        // Generate daily executive report at 9 AM
        $schedule->command('reports:generate-daily-executive')
            ->dailyAt('09:00')
            ->timezone('Europe/Amsterdam');

        // Generate weekly performance report every Monday at 10 AM
        $schedule->command('reports:generate-weekly-performance')
            ->weeklyOn(1, '10:00')
            ->timezone('Europe/Amsterdam');

        // Create daily KPI snapshot at 11:59 PM
        $schedule->command('kpi:snapshot')
            ->dailyAt('23:59')
            ->timezone('Europe/Amsterdam');

        // Sync Meta accounts every hour
        $schedule->command('meta:sync-accounts')
            ->hourly()
            ->withoutOverlapping();

        // Sync Meta campaigns every 30 minutes
        $schedule->command('meta:sync-campaigns')
            ->everyThirtyMinutes()
            ->withoutOverlapping();
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
