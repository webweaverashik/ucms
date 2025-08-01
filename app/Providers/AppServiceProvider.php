<?php
namespace App\Providers;

use App\Models\Student\Student;
use App\Models\Teacher\Teacher;
use Illuminate\Database\Console\Migrations\FreshCommand;
use Illuminate\Database\Console\Migrations\RefreshCommand;
use Illuminate\Database\Console\Migrations\ResetCommand;
use Illuminate\Database\Console\WipeCommand;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Facades\App;
use Illuminate\Support\ServiceProvider;

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
        Relation::morphMap([
            'student' => Student::class,
            'teacher' => Teacher::class,
        ]);

        if (app()->environment('production')) {
            FreshCommand::preventFromRunning();
            RefreshCommand::preventFromRunning();
            ResetCommand::preventFromRunning();
            RollbackCommand::preventFromRunning();
            WipeCommand::preventFromRunning();
        }
    }
}
