<?php

namespace App\Providers;

use App\Models\Alumni;
use App\Policies\AlumniPolicy;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * Daftar policy yang terdaftar di aplikasi.
     * Sesuai 07_SECURITY.md §3.3 — AlumniPolicy
     *
     * @var array<class-string, class-string>
     */
    protected $policies = [
        Alumni::class => AlumniPolicy::class,
    ];

    /**
     * Daftarkan services autentikasi/autorisasi.
     */
    public function boot(): void
    {
        $this->registerPolicies();
    }
}
