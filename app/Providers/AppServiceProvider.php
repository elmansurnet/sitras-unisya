<?php

namespace App\Providers;

use App\Models\Alumni;
use App\Models\Employer;
use App\Observers\AlumniObserver;
use App\Observers\EmployerObserver;
use App\Policies\AlumniPolicy;
use App\Policies\EmployerPolicy;
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

        // Policy registrations
        Gate::policy(Alumni::class, AlumniPolicy::class);
        Gate::policy(Employer::class, EmployerPolicy::class);
    }
}
