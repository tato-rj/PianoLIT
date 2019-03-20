<?php

namespace App\Providers;

use Illuminate\Support\Facades\Gate;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The policy mappings for the application.
     *
     * @var array
     */
    protected $policies = [
        'App\Piece' => 'App\Policies\PiecePolicy',
        'App\Composer' => 'App\Policies\ComposerPolicy',
        'App\Tag' => 'App\Policies\TagPolicy',
        'App\Admin' => 'App\Policies\AdminPolicy',
        'App\Membership' => 'App\Policies\MembershipPolicy',
    ];

    /**
     * Register any authentication / authorization services.
     *
     * @return void
     */
    public function boot()
    {
        $this->registerPolicies();

        \Gate::before(function($user) {
            if ($user->role == 'manager')
                return true;
        });
    }
}
