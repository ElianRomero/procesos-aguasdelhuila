<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Gate;
use App\Models\User;

class AuthServiceProvider extends ServiceProvider
{
    protected $policies = [
        \App\Models\Noticia::class => \App\Policies\NoticiaPolicy::class,
    ];
    public function boot(): void
    {
        Gate::define('isAdmin', fn(User $user) => (int) $user->role_id === 1);
        Gate::define('isProponente', fn(User $user) => (int) $user->role_id === 3);
    }
}
