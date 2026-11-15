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
        View::composer('*', function ($view) {
            $view->with('barangay', Setting::instance());
        });

        View::composer('layouts.shared.topbar', function ($view) {
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
