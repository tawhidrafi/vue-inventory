<?php

namespace App\Providers;

use App\Models\Category;
use App\Models\Customer;
use App\Models\Product;
use App\Models\PurchaseOrder;
use App\Models\SalesOrder;
use App\Models\Supplier;
use App\Models\User;
use App\Models\Warehouse;
use App\Observers\ActivityObserver;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // write an audit entry whenever these models change
        foreach ([
            Product::class,
            Category::class,
            Supplier::class,
            Warehouse::class,
            PurchaseOrder::class,
            Customer::class,
            SalesOrder::class,
            User::class,
        ] as $model) {
            $model::observe(ActivityObserver::class);
        }
    }
}
