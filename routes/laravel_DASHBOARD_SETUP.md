# UCMS Dashboard Setup Guide

## Overview

This document provides step-by-step instructions to integrate the comprehensive admin dashboard into your Laravel 12 UCMS application.

## File Structure

```
app/
├── Helpers/
│   ├── helpers.php              # (existing)
│   └── dashboard_helpers.php    # NEW: Dashboard cache helpers
├── Http/
│   └── Controllers/
│       └── DashboardController.php  # UPDATED: Full dashboard controller
├── Observers/
│   ├── StudentObserver.php          # NEW: Auto-clear cache on student changes
│   ├── PaymentObserver.php          # NEW: Auto-clear cache on payment changes
│   ├── AttendanceObserver.php       # NEW: Auto-clear cache on attendance changes
│   └── LoginActivityObserver.php    # NEW: Auto-clear cache on login activities
├── Providers/
│   └── DashboardServiceProvider.php # NEW: Service provider for dashboard
└── Services/
    └── Dashboard/
        ├── DashboardService.php      # NEW: Main dashboard business logic
        └── DashboardCacheService.php # NEW: Cache management service

resources/
└── views/
    └── dashboard/
        ├── admin/
        │   └── index.blade.php       # NEW: Admin dashboard view
        ├── manager/
        │   └── index.blade.php       # Create similar to admin (branch-restricted)
        ├── accountant/
        │   └── index.blade.php       # Create similar to manager
        └── partials/
            ├── _stats_cards.blade.php   # NEW: Stats cards partial
            ├── _tables.blade.php        # NEW: Tables partial
            └── _batch_stats.blade.php   # NEW: Batch stats partial

public/
└── js/
    └── dashboard/
        ├── main.js     # NEW: Main dashboard controller
        ├── utils.js    # NEW: Utility functions
        ├── charts.js   # NEW: Chart.js manager
        ├── tables.js   # NEW: Table renderers
        └── cache.js    # NEW: Client-side cache manager

routes/
└── dashboard.php    # NEW: Dashboard API routes
```

## Step 1: Register Service Provider

Add the service provider to `config/app.php`:

```php
'providers' => ServiceProvider::defaultProviders()->merge([
    // ... other providers
    App\Providers\DashboardServiceProvider::class,
])->toArray(),
```

Or in Laravel 11+, add to `bootstrap/providers.php`:

```php
return [
    App\Providers\AppServiceProvider::class,
    App\Providers\DashboardServiceProvider::class,
];
```

## Step 2: Register Helpers in Composer

Update `composer.json` to autoload the helper files:

```json
{
    "autoload": {
        "files": [
            "app/Helpers/helpers.php",
            "app/Helpers/dashboard_helpers.php"
        ]
    }
}
```

Then run:

```bash
composer dump-autoload
```

## Step 3: Add Dashboard Routes

In your `routes/web.php`, add:

```php
require __DIR__ . '/dashboard.php';
```

Or copy the contents of `routes/dashboard.php` into your main routes file.

## Step 4: Create Missing Observer Files

Create separate observer files for PaymentInvoice and PaymentTransaction:

### `app/Observers/PaymentInvoiceObserver.php`

```php
<?php

namespace App\Observers;

use App\Models\Payment\PaymentInvoice;

class PaymentInvoiceObserver
{
    public function created(PaymentInvoice $invoice): void
    {
        $this->clearCache($invoice);
    }

    public function updated(PaymentInvoice $invoice): void
    {
        $this->clearCache($invoice);
    }

    public function deleted(PaymentInvoice $invoice): void
    {
        $this->clearCache($invoice);
    }

    public function restored(PaymentInvoice $invoice): void
    {
        $this->clearCache($invoice);
    }

    protected function clearCache(PaymentInvoice $invoice): void
    {
        if (function_exists('clearDashboardPaymentCache')) {
            clearDashboardPaymentCache($invoice->student?->branch_id);
        }
    }
}
```

### `app/Observers/PaymentTransactionObserver.php`

```php
<?php

namespace App\Observers;

use App\Models\Payment\PaymentTransaction;

class PaymentTransactionObserver
{
    public function created(PaymentTransaction $transaction): void
    {
        $this->clearCache($transaction);
    }

    public function updated(PaymentTransaction $transaction): void
    {
        $this->clearCache($transaction);
    }

    public function deleted(PaymentTransaction $transaction): void
    {
        $this->clearCache($transaction);
    }

    public function restored(PaymentTransaction $transaction): void
    {
        $this->clearCache($transaction);
    }

    protected function clearCache(PaymentTransaction $transaction): void
    {
        if (function_exists('clearDashboardPaymentCache')) {
            clearDashboardPaymentCache($transaction->student?->branch_id);
        }
    }
}
```

## Step 5: Update Existing Helpers

Add to your existing `app/Helpers/helpers.php`:

```php
/**
 * Clear dashboard cache when clearing UCMS caches
 */
if (!function_exists('clearUCMSCaches')) {
    function clearUCMSCaches(): void
    {
        if (!auth()->check()) {
            return;
        }

        $branchId = auth()->user()->branch_id;

        // Existing cache clearing...
        Cache::forget('students_list_branch_' . $branchId);
        Cache::forget('alumni_students_list_branch_' . $branchId);
        Cache::forget('guardians_list_branch_' . $branchId);
        Cache::forget('invoices_index_branch_' . $branchId);
        Cache::forget('transactions_branch_' . $branchId);

        // Add dashboard cache clearing
        if (function_exists('clearDashboardCacheForBranch')) {
            clearDashboardCacheForBranch($branchId);
        }
    }
}
```

## Step 6: Create Manager/Accountant Dashboard Views

Create `resources/views/dashboard/manager/index.blade.php`:

```blade
@extends('layouts.app')

@section('title', 'Dashboard')

@push('page-css')
{{-- Same styles as admin --}}
@endpush

@section('content')
<div id="dashboard-app">
    {{-- Branch Info Banner (instead of tabs) --}}
    <div class="card bg-light-primary mb-5">
        <div class="card-body py-4">
            <div class="d-flex align-items-center">
                <div class="symbol symbol-50px me-4">
                    <span class="symbol-label bg-primary">
                        <i class="ki-outline ki-bank fs-2x text-white"></i>
                    </span>
                </div>
                <div>
                    <h4 class="text-primary mb-0">{{ $data['current_branch']['branch_name'] ?? 'Your Branch' }}</h4>
                    <span class="text-muted">You are viewing data for your assigned branch</span>
                </div>
            </div>
        </div>
    </div>

    {{-- Rest of the dashboard components (same as admin) --}}
    @include('dashboard.partials._stats_cards')
    {{-- ... other partials --}}
</div>
@endsection

@push('page-js')
<script>
    const DashboardConfig = {
        apiBaseUrl: '{{ url("/dashboard/api") }}',
        csrfToken: '{{ csrf_token() }}',
        currentBranch: '{{ auth()->user()->branch_id }}',
        isAdmin: false,
        initialData: @json($data),
    };
</script>
{{-- Same JS files as admin --}}
@endpush
```

## Step 7: Add StudentAttendance Model

If not already created, add the StudentAttendance model:

```php
<?php

namespace App\Models\Student;

use App\Models\Academic\Batch;
use App\Models\Academic\ClassName;
use App\Models\Branch;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class StudentAttendance extends Model
{
    use HasFactory;

    protected $fillable = [
        'branch_id',
        'student_id',
        'class_id',
        'batch_id',
        'status',
        'remarks',
        'attendance_date',
        'created_by',
    ];

    protected $casts = [
        'attendance_date' => 'date',
    ];

    public function student()
    {
        return $this->belongsTo(Student::class);
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    public function class()
    {
        return $this->belongsTo(ClassName::class, 'class_id');
    }

    public function batch()
    {
        return $this->belongsTo(Batch::class);
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
```

## Step 8: Configure Caching

For optimal performance, configure Redis as your cache driver in `.env`:

```env
CACHE_DRIVER=redis
REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379
```

If using file cache, the cache clearing will work but won't support pattern-based clearing.

## Step 9: Publish Assets

Copy the JavaScript files to your public directory:

```bash
# Create directory
mkdir -p public/js/dashboard

# Copy files
cp laravel/public/js/dashboard/* public/js/dashboard/
```

## Step 10: Clear Caches

After setup, clear all caches:

```bash
php artisan optimize:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

## Usage Examples

### In Controllers

```php
// Clear specific cache when making changes
use App\Services\Dashboard\DashboardCacheService;

public function store(Request $request)
{
    $student = Student::create($request->validated());
    
    // Cache is automatically cleared via Observer
    // Or manually:
    app(DashboardCacheService::class)->clearStudentCache($student->branch_id);
    
    return redirect()->back();
}
```

### Using Helper Functions

```php
// Clear all dashboard cache
clearDashboardCache();

// Clear for specific branch
clearDashboardCacheForBranch($branchId);

// Clear payment cache only
clearDashboardPaymentCache($branchId);

// Clear student cache only
clearDashboardStudentCache($branchId);
```

## API Endpoints

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/dashboard/api/all` | Get all dashboard data |
| GET | `/dashboard/api/stats` | Get statistics only |
| GET | `/dashboard/api/monthly-payments` | Get payment chart data |
| GET | `/dashboard/api/student-distribution` | Get student distribution |
| GET | `/dashboard/api/attendance-analytics` | Get attendance data |
| GET | `/dashboard/api/invoice-status` | Get invoice breakdown |
| GET | `/dashboard/api/recent-transactions` | Get recent transactions |
| GET | `/dashboard/api/top-employees` | Get top employees |
| GET | `/dashboard/api/top-subjects` | Get top subjects |
| GET | `/dashboard/api/login-activities` | Get login activities |
| GET | `/dashboard/api/batch-stats` | Get batch statistics |
| POST | `/dashboard/api/clear-cache` | Clear dashboard cache |

### Query Parameters

All GET endpoints accept:
- `branch_id`: Branch ID or empty for all branches (admin only)

Additional parameters:
- `months`: For monthly-payments (default: 6)
- `limit`: For tables (default: 10)
- `start_date`, `end_date`: For attendance-analytics

## Troubleshooting

### Charts not loading
- Ensure Chart.js is loaded before dashboard scripts
- Check browser console for errors

### Cache not clearing
- Verify Redis connection if using Redis
- Check if helpers are autoloaded (`composer dump-autoload`)

### Permission errors
- Ensure user has correct role (admin/manager/accountant)
- Check Spatie permission cache (`php artisan permission:cache-reset`)

### Data not updating
- Clear browser cache
- Click "Refresh" button in dashboard
- Clear server cache (`php artisan cache:clear`)

## Performance Tips

1. **Use Redis** for cache driver
2. **Index database columns** used in queries
3. **Eager load relationships** in queries
4. **Increase cache TTL** for stable data
5. **Use queue** for heavy operations

## Security Considerations

1. All API endpoints require authentication
2. Branch filtering is enforced server-side
3. Non-admin users can only view their branch
4. CSRF protection on all POST requests
5. Input validation on all parameters
