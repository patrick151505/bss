<?php

namespace Database\Seeders;

use App\Models\InventoryCategory;
use App\Models\InventoryItem;
use App\Models\InventoryStockIn;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Starter inventory for a barangay: a few consumables that get released and
 * a few assets that get rented out and returned. Idempotent — re-running
 * updates the same items instead of duplicating them.
 */
class DemoInventorySeeder extends Seeder
{
    /** [name, icon, color] */
    private const CATEGORIES = [
        'Medical Supplies'  => ['mgc_hospital_line', 'danger'],
        'Office Supplies'   => ['mgc_document_line', 'primary'],
        'Relief Goods'      => ['mgc_package_line', 'warning'],
        'Equipment'         => ['mgc_tool_line', 'success'],
        'Event Facilities'  => ['mgc_home_3_line', 'info'],
    ];

    /** [category, name, sku, unit, stock, threshold, rentable, requires_approval] */
    private const ITEMS = [
        // Consumables — released and not returned.
        ['Medical Supplies', 'Paracetamol 500mg',      'MED-001', 'box',   80,  15, false, true],
        ['Medical Supplies', 'Face Mask (50 pcs)',     'MED-002', 'box',  120,  20, false, false],
        ['Medical Supplies', 'Alcohol 500ml',          'MED-003', 'bottle', 60, 10, false, false],
        ['Office Supplies',  'Bond Paper A4',          'OFF-001', 'ream',   45, 10, false, false],
        ['Office Supplies',  'Ballpen (Box of 12)',    'OFF-002', 'box',    30,  5, false, false],
        ['Relief Goods',     'Rice 5kg',               'REL-001', 'sack',  100, 20, false, true],
        ['Relief Goods',     'Canned Goods Assorted',  'REL-002', 'pack',  150, 30, false, false],

        // Rentable assets — lent out and expected back.
        ['Event Facilities', 'Monoblock Chair',        'RNT-001', 'pcs',   200, 40, true,  false],
        ['Event Facilities', 'Folding Table',          'RNT-002', 'pcs',    40,  8, true,  false],
        ['Event Facilities', 'Tent 3x3m',              'RNT-003', 'set',    10,  2, true,  true],
        ['Equipment',        'Sound System',           'RNT-004', 'set',     3,  1, true,  true],
        ['Equipment',        'Portable Generator',     'RNT-005', 'unit',    2,  1, true,  true],
    ];

    public function run(): void
    {
        DB::transaction(function () {
            $categories = [];

            foreach (self::CATEGORIES as $name => [$icon, $color]) {
                $categories[$name] = InventoryCategory::updateOrCreate(
                    ['slug' => Str::slug($name)],
                    ['name' => $name, 'icon' => $icon, 'color' => $color, 'is_active' => true]
                );
            }

            $created = 0;
            $updated = 0;

            foreach (self::ITEMS as [$cat, $name, $sku, $unit, $stock, $threshold, $rentable, $approval]) {
                $item = InventoryItem::where('sku', $sku)->first();

                if ($item) {
                    $item->update([
                        'category_id'         => $categories[$cat]->id,
                        'name'                => $name,
                        'unit'                => $unit,
                        'low_stock_threshold' => $threshold,
                        'is_rentable'         => $rentable,
                        'requires_approval'   => $approval,
                        'is_active'           => true,
                    ]);
                    $updated++;
                    continue;
                }

                $item = InventoryItem::create([
                    'category_id'         => $categories[$cat]->id,
                    'name'                => $name,
                    'sku'                 => $sku,
                    'unit'                => $unit,
                    'stock'               => $stock,
                    'low_stock_threshold' => $threshold,
                    'is_rentable'         => $rentable,
                    'requires_approval'   => $approval,
                    'is_active'           => true,
                ]);

                // Record the opening balance so item history is not empty.
                InventoryStockIn::create([
                    'item_id'         => $item->id,
                    'type'            => 'stock_in',
                    'quantity'        => $stock,
                    'quantity_before' => 0,
                    'source'          => 'Opening balance',
                    'reference'       => 'Initial setup',
                    'created_by'      => DB::table('users')->min('id'),
                ]);

                $created++;
            }

            $this->command?->info("Demo inventory — categories: " . count($categories) . ", items created: {$created}, updated: {$updated}.");
        });
    }
}
