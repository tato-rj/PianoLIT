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
        'App\Topic' => 'App\Policies\TopicPolicy',
        'App\Admin' => 'App\Policies\AdminPolicy',
        'App\Payments\Membership' => 'App\Policies\MembershipPolicy',
        'App\User' => 'App\Policies\UserPolicy',
        'App\StudioPolicy' => 'App\Policies\StudioPolicy',
        'App\TutorialRequest' => 'App\Policies\TutorialRequestPolicy',
        'App\Review' => 'App\Policies\ReviewPolicy',
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
