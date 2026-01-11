<?php
namespace App\Providers;

use App\Models\Student\Student;
use App\Models\Student\StudentSecondaryClass;
use App\Models\Teacher\Teacher;
use App\Observers\StudentObserver;
use App\Observers\StudentSecondaryClassObserver;
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

        // Disabling Destructive Commands e.g. migration, seeding, reset
        DB::prohibitDestructiveCommands(app()->isProduction());

        // Student Observer to log class changes
        Student::observe(StudentObserver::class);
        StudentSecondaryClass::observe(StudentSecondaryClassObserver::class);
    }
}
