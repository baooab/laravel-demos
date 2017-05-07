<?php

namespace App\Providers;

use App\Post;
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
        'App\Model' => 'App\Policies\ModelPolicy',
    ];

    /**
     * Register any authentication / authorization services.
     *
     * @return void
     */
    public function boot()
    {
        $this->registerPolicies();
        $this->registerPostPolicies();
    }

    public function registerPostPolicies()
    {
        Gate::define('create-post', function ($user) {
            return $user->hasAccess('create-post');
        });
        Gate::define('update-post', function ($user, Post $post) {
            return $user->hasAccess('update-post') or $user->id == $post->user_id;
        });
        Gate::define('publish-post', function ($user, Post $post) {
            return $user->hasAccess('publish-post') or $user->id == $post->user_id;;
        });
        Gate::define('delete-post', function ($user, Post $post) {
            return $user->hasAccess('delete-post') or $user->id == $post->user_id;
        });
        Gate::define('see-all-drafts', function ($user) {
            return $user->inRole('editor');
        });
    }
}
