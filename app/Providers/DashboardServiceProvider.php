<?php

namespace App\Providers;

use App\Models\LoginActivity;
use App\Models\Payment\PaymentInvoice;
use App\Models\Payment\PaymentTransaction;
use App\Models\Student\Student;
use App\Models\Student\StudentActivation;
use App\Models\Student\StudentAttendance;
use App\Observers\AttendanceObserver;
use App\Observers\LoginActivityObserver;
use App\Observers\PaymentInvoiceObserver;
use App\Observers\PaymentTransactionObserver;
use App\Observers\StudentObserver;
use App\Services\Dashboard\DashboardCacheService;
use App\Services\Dashboard\DashboardService;
use Illuminate\Support\ServiceProvider;

class DashboardServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        // Register Dashboard Service as singleton
        $this->app->singleton(DashboardService::class, function ($app) {
            return new DashboardService();
        });

        // Register Cache Service as singleton
        $this->app->singleton(DashboardCacheService::class, function ($app) {
            return new DashboardCacheService();
        });
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        // Register model observers for automatic cache invalidation
        $this->registerObservers();
    }

    /**
     * Register model observers
     */
    protected function registerObservers(): void
    {
        // Student changes
        Student::observe(StudentObserver::class);

        // Payment Invoice changes
        PaymentInvoice::observe(PaymentInvoiceObserver::class);

        // Payment Transaction changes
        PaymentTransaction::observe(PaymentTransactionObserver::class);

        // Attendance changes
        StudentAttendance::observe(AttendanceObserver::class);

        // Login Activity changes
        LoginActivity::observe(LoginActivityObserver::class);
    }
}
