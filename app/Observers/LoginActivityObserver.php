<?php

namespace App\Observers;

use App\Models\LoginActivity;

class LoginActivityObserver
{
    /**
     * Handle the LoginActivity "created" event.
     */
    public function created(LoginActivity $activity): void
    {
        $this->clearCache();
    }

    /**
     * Clear dashboard login activity cache
     */
    protected function clearCache(): void
    {
        if (function_exists('clearDashboardLoginCache')) {
            clearDashboardLoginCache();
        }
    }
}
