<?php

namespace App\Providers;

use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AuthServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        Gate::define('view_executive_dashboard', static function (?User $user): bool {
            if ($user === null) {
                return false;
            }

            if ($user->isSuperAdmin()) {
                return true;
            }

            if ($user->tenant_id === null) {
                return false;
            }

            return $user->can('view_reports');
        });
    }
}
