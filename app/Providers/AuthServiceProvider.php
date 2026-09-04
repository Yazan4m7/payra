<?php
namespace App\Providers;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
class AuthServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        Gate::define('manage-hr', fn ($user) => in_array($user->role, ['company_admin','hr'], true));
        Gate::define('company-admin', fn ($user) => $user->role === 'company_admin');
    }
}
