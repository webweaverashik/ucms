<?php
namespace App\Providers;

use App\Models\Student\Student;
use App\Models\Teacher\Teacher;
use Spatie\Browsershot\Browsershot;
use Illuminate\Support\ServiceProvider;
use Illuminate\Database\Eloquent\Relations\Relation;

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

        // Browsershot::setNodeBinary('/home/uniqueco/.nvm/versions/node/v20.19.2/bin/node');
        // Browsershot::setNpmBinary('/home/uniqueco/.nvm/versions/node/v20.19.2/bin/npm');
    }
}
