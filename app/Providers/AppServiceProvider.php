<?php

namespace App\Providers;

use App\Models\InventoryItem;
use App\Models\Setting;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void {}

    public function boot(): void
    {
        // @feature('budget') … @endfeature — shown only when the subscription plan includes it.
        \Illuminate\Support\Facades\Blade::if('feature', fn (string $f) => \App\Support\Plan::has($f));

        View::composer('*', function ($view) {
            $view->with('barangay', Setting::instance());
        });

        View::composer('layouts.shared.topbar', function ($view) {
            $user = auth()->user();
            $hasTable = $user && \Illuminate\Support\Facades\Schema::hasTable('notifications');
            $view->with('bellNotifications', $hasTable ? $user->unreadNotifications()->latest()->limit(8)->get() : collect());
            $view->with('bellUnread', $hasTable ? $user->unreadNotifications()->count() : 0);

            if (!auth()->user()?->can('inventory_items.view')) {
                $view->with('lowStockItems', collect());
                return;
            }
            $lowStockItems = InventoryItem::active()
                ->whereRaw('stock <= low_stock_threshold')
                ->orderByRaw('stock - low_stock_threshold ASC')
                ->limit(10)
                ->get();
            $view->with('lowStockItems', $lowStockItems);
        });
    }
}
