<?php
namespace App\Providers;

use App\Models\Student\Student;
use App\Models\Teacher\Teacher;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\ServiceProvider;
use Spatie\Browsershot\Browsershot;

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

        Browsershot::setNodeBinary('/home/uniqueco/.nvm/versions/node/v22.16.0/bin/node');
        Browsershot::setNpmBinary('/home/uniqueco/.nvm/versions/node/v22.16.0/bin/npm');

        // Optional for shared hosting
        Browsershot::setOption('args', ['--no-sandbox']);
    }
}
