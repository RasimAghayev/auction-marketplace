<?php

namespace App\Providers;

use App\Models\Auction;
use App\Models\Bid;
use App\Models\Notification;
use App\Models\Permission;
use App\Models\PersonalAccessToken;
use App\Models\Product;
use App\Models\Role;
use App\Policies\AuctionPolicy;
use App\Policies\BidPolicy;
use App\Policies\NotificationPolicy;
use App\Policies\ProductPolicy;
use Illuminate\Auth\Middleware\Authenticate;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\PermissionRegistrar;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        Sanctum::usePersonalAccessTokenModel(PersonalAccessToken::class);
    }

    public function boot(): void
    {
        Gate::policy(Product::class, ProductPolicy::class);
        Gate::policy(Auction::class, AuctionPolicy::class);
        Gate::policy(Bid::class, BidPolicy::class);
        Gate::policy(Notification::class, NotificationPolicy::class);

        $this->app->booted(function () {
            // Ensure Spatie Permission uses our custom MongoDB models
            $registrar = app(PermissionRegistrar::class);
            $registrar->setPermissionClass(Permission::class);
            $registrar->setRoleClass(Role::class);

            Authenticate::redirectUsing(fn ($request) => $request->expectsJson() ? null : '/login');
        });
    }
}