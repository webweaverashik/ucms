<?php
namespace App\Providers;

use App\Models\Student\Student;
use App\Models\Teacher\Teacher;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\DB;
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

        DB::prohibitDestructiveCommands(app()->isProduction());
    }
}
