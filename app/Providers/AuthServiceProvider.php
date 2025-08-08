<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Gate;
use App\Models\User;

class AuthServiceProvider extends ServiceProvider
{


 
    public function boot(): void
    {
        Gate::define('isAdmin',      fn(User $user) => (int) $user->role_id === 1);
        Gate::define('isProponente', fn(User $user) => (int) $user->role_id === 3);
    }
}
