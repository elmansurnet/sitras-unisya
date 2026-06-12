<?php

namespace App\Providers;

use App\Models\Alumni;
use App\Models\Employer;
use App\Models\SurveyResponse;
use App\Models\User;
use App\Observers\AlumniObserver;
use App\Observers\EmployerObserver;
use App\Observers\SurveyResponseObserver;
use App\Observers\UserObserver;
use App\Policies\AlumniPolicy;
use App\Policies\EmployerPolicy;
use App\Policies\UserPolicy;
use App\Repositories\AlumniRepository;
use App\Repositories\Contracts\AlumniRepositoryInterface;
use App\Repositories\Contracts\EmployerRepositoryInterface;
use App\Repositories\EmployerRepository;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Repository bindings
        $this->app->bind(AlumniRepositoryInterface::class, AlumniRepository::class);
        $this->app->bind(EmployerRepositoryInterface::class, EmployerRepository::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Observer registrations
        Alumni::observe(AlumniObserver::class);
        Employer::observe(EmployerObserver::class);
        SurveyResponse::observe(SurveyResponseObserver::class);
        User::observe(UserObserver::class);

        // Policy registrations
        Gate::policy(Alumni::class, AlumniPolicy::class);
        Gate::policy(Employer::class, EmployerPolicy::class);
        Gate::policy(User::class, UserPolicy::class);

        // Gate definitions for role-based access shorthand
        Gate::define('superadmin-only', function (User $user) {
            return $user->role === 'superadmin';
        });

        Gate::define('admin-or-superadmin', function (User $user) {
            return in_array($user->role, ['admin', 'superadmin']);
        });
    }
}
