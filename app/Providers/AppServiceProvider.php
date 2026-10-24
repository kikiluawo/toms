<?php

namespace App\Providers;

use App\Models\Branch;
use App\Models\Company;
use App\Models\User;
use App\Support\UserContext;
use Illuminate\Support\Facades\Gate;
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
        Gate::define('view-company', fn (User $user, Company $company) => UserContext::for($user)->canViewCompany($company->id));

        Gate::define('act-for-company', function (User $user, Company $company, ?Branch $branch = null) {
            return UserContext::for($user)->canActOn($company->id, $branch?->id);
        });

        Gate::define('act-for-all-companies', fn (User $user) => UserContext::for($user)->isGroup());
    }
}
