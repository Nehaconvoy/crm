<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Route;
use App\Http\Middleware\RedirectIfAuthenticated;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\View;
use App\Models\RoleHasPermission;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind('files', function () {
            return new Filesystem;
        });

        $this->app->singleton(\App\Services\CreditService::class, function () {
            return new \App\Services\CreditService();
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Paginator::useBootstrapFive();

        // Middleware should generally be registered in Kernel, not here,
        // but if you need to alias it here for specific reasons:
        Route::aliasMiddleware('guest', RedirectIfAuthenticated::class);

        View::composer('*', function ($view) {
            $menus = [];

            if (Auth::check()) {
                $roleId = Auth::user()->role_id;
                $menusdata = RoleHasPermission::with('permissions')->where('role_id', $roleId)->where('read', 1)->get();

                $menus = [];

                foreach($menusdata as $menu){
                    $menus[] = $menu->permissions?->name;
                }
            }
             
            $view->with('userMenus', $menus);
        });

        require_once app_path('Helpers/helpers.php');
           
    }
}

