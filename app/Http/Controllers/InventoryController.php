<?php

namespace App\Http\Controllers;

use App\Models\Citizen;
use App\Models\InventoryCategory;
use App\Models\InventoryItem;
use App\Models\InventoryRelease;
use App\Models\InventoryReleaseItem;
use App\Models\InventoryStockIn;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class InventoryController extends Controller
{
    // -------------------------------------------------------------------------
    // Categories
    // -------------------------------------------------------------------------

    public function categoriesIndex()
    {
        $categories = InventoryCategory::withCount('items')->orderBy('name')->get();
        return view('inventory.categories.index', compact('categories'));
    }

    public function categoriesCreate()
    {
        return view('inventory.categories.create');
    }

    public function categoriesStore(Request $request)
    {
        $request->validate([
            'name'  => 'required|string|max:255|unique:eb_inventory_categories,name',
            'icon'  => 'nullable|string|max:255',
            'color' => 'nullable|string|max:50',
        ]);

        InventoryCategory::create([
            'name'        => $request->name,
            'slug'        => Str::slug($request->name),
            'description' => $request->description,
            'icon'        => $request->icon  ?: 'mgc_box_line',
            'color'       => $request->color ?: 'primary',
            'is_active'   => $request->boolean('is_active', true),
            'is_medicine' => $request->boolean('is_medicine'),
        ]);

        return redirect()->route('inventory.categories.index')->with('success', 'Category created.');
    }

    public function categoriesEdit(InventoryCategory $category)
    {
        return view('inventory.categories.edit', compact('category'));
    }

    public function categoriesUpdate(Request $request, InventoryCategory $category)
    {
        $request->validate([
            'name'  => 'required|string|max:255|unique:eb_inventory_categories,name,' . $category->id,
            'icon'  => 'nullable|string|max:255',
            'color' => 'nullable|string|max:50',
        ]);

        $category->update([
            'name'        => $request->name,
            'slug'        => Str::slug($request->name),
            'description' => $request->description,
            'icon'        => $request->icon  ?: 'mgc_box_line',
            'color'       => $request->color ?: 'primary',
            'is_active'   => $request->boolean('is_active', true),
            'is_medicine' => $request->boolean('is_medicine'),
        ]);

        return redirect()->route('inventory.categories.index')->with('success', 'Category updated.');
    }

    public function categoriesDestroy(InventoryCategory $category)
    {
        $count = InventoryItem::where('category_id', $category->id)->count();
        if ($count > 0) {
            return back()->with('error', "\"{$category->name}\" still has {$count} item(s). Move them to another category first.");
        }
        $category->delete();
        return back()->with('success', 'Category deleted.');
    }

    // -------------------------------------------------------------------------
    // Items
    // -------------------------------------------------------------------------

    public function itemsIndex(Request $request)
    {
        $query = InventoryItem::with('category');

        if ($request->filled('category')) {
            $query->where('category_id', $request->category);
        }
        if ($request->filled('search')) {
            $query->where('name', 'like', '%' . $request->search . '%');
        }

        $items      = $query->orderBy('name')->paginate(20)->appends($request->query());
        $categories = InventoryCategory::active()->orderBy('name')->get();

        return view('inventory.items.index', compact('items', 'categories'));
    }

    public function itemsCreate()
    {
        $categories = InventoryCategory::active()->orderBy('name')->get();
        return view('inventory.items.create', compact('categories'));
    }

    public function itemsStore(Request $request)
    {
        $request->validate([
            'category_id'         => 'required|exists:eb_inventory_categories,id',
            'name'                => 'required|string|max:255',
            'sku'                 => 'nullable|string|max:100|unique:eb_inventory_items,sku',
            'image'               => 'nullable|image|max:2048',
            'unit'                => 'required|string|max:50',
            'low_stock_threshold' => 'required|integer|min:0',
            'schedule_color'       => ['nullable', 'regex:/^#[0-9a-fA-F]{6}$/'],
            'schedule_buffer_days' => 'nullable|integer|min:0|max:365',
        ], [
            'schedule_color.regex' => 'Pick a valid calendar color.',
        ]);

        $imagePath = $request->hasFile('image')
            ? $request->file('image')->store('inventory/items', 'public')
            : null;

        InventoryItem::create([
            'category_id'         => $request->category_id,
            'name'                => $request->name,
            'sku'                 => $request->sku ?: null,
            'image'               => $imagePath,
            'unit'                => $request->unit,
            'description'         => $request->description,
            'stock'               => 0,
            'low_stock_threshold' => $request->low_stock_threshold,
            'requires_approval'   => $request->boolean('requires_approval'),
            'is_rentable'         => $request->boolean('is_rentable'),
            'is_schedulable'      => $request->boolean('is_schedulable'),
            'schedule_color'       => $request->filled('schedule_color') ? strtolower($request->schedule_color) : null,
            'schedule_buffer_days' => $request->filled('schedule_buffer_days') ? (int) $request->schedule_buffer_days : 3,
            'is_active'           => $request->boolean('is_active', true),
        ]);

        return redirect()->route('inventory.items.index')->with('success', 'Item created.');
    }

    public function itemsEdit(InventoryItem $item)
    {
        // The manage page holds both the details form (edit) and stock-in /
        // adjustment (stock); either permission opens it.
        abort_unless(auth()->user()->canAny(['inventory_items.edit', 'inventory_items.stock']), 403);

        $categories = InventoryCategory::active()->orderBy('name')->get();
        $stockIns   = $item->stockIns()->with('createdBy')->latest()->get();
        return view('inventory.items.edit', compact('item', 'categories', 'stockIns'));
    }

    public function itemsUpdate(Request $request, InventoryItem $item)
    {
        $request->validate([
            'category_id'         => 'required|exists:eb_inventory_categories,id',
            'name'                => 'required|string|max:255',
            'sku'                 => 'nullable|string|max:100|unique:eb_inventory_items,sku,' . $item->id,
            'image'               => 'nullable|image|max:2048',
            'unit'                => 'required|string|max:50',
            'low_stock_threshold' => 'required|integer|min:0',
            'schedule_color'       => ['nullable', 'regex:/^#[0-9a-fA-F]{6}$/'],
            'schedule_buffer_days' => 'nullable|integer|min:0|max:365',
        ], [
            'schedule_color.regex' => 'Pick a valid calendar color.',
        ]);

        $data = [
            'category_id'         => $request->category_id,
            'name'                => $request->name,
            'sku'                 => $request->sku ?: null,
            'unit'                => $request->unit,
            'description'         => $request->description,
            'low_stock_threshold' => $request->low_stock_threshold,
            'requires_approval'   => $request->boolean('requires_approval'),
            'is_rentable'         => $request->boolean('is_rentable'),
            'is_schedulable'      => $request->boolean('is_schedulable'),
            'schedule_color'       => $request->filled('schedule_color') ? strtolower($request->schedule_color) : null,
            'schedule_buffer_days' => $request->filled('schedule_buffer_days') ? (int) $request->schedule_buffer_days : 3,
            'is_active'           => $request->boolean('is_active', true),
        ];

        if ($request->hasFile('image')) {
            if ($item->image) Storage::disk('public')->delete($item->image);
            $data['image'] = $request->file('image')->store('inventory/items', 'public');
        }

        $item->update($data);

        return redirect()->route('inventory.items.edit', $item)->with('success', 'Item updated.');
    }

    /**
     * Delete an item that was never used. Items that appear in a release, rental or
     * reservation keep their history — deactivate them instead (untick Active).
     */
    public function itemsDestroy(InventoryItem $item)
    {
        $uses = [
            'release'     => $item->releaseItems()->count(),
            'rental'      => \App\Models\InventoryRentalItem::where('item_id', $item->id)->count(),
            'reservation' => \App\Models\InventorySchedule::where('item_id', $item->id)->count(),
        ];
        $used = collect($uses)->filter()->map(fn($n, $what) => $n . ' ' . $what . ($n > 1 ? 's' : ''));
        if ($used->isNotEmpty()) {
            return back()->with('error', "\"{$item->name}\" can't be deleted — it is used in " . $used->implode(', ')
                . '. Untick "Active" on the item instead to hide it.');
        }

        DB::transaction(function () use ($item) {
            $item->stockIns()->delete();
            if ($item->image) Storage::disk('public')->delete($item->image);
            $item->delete();
        });

        return redirect()->route('inventory.items.index')->with('success', "Item \"{$item->name}\" deleted.");
    }

    public function itemsDeleteImage(InventoryItem $item)
    {
        if ($item->image) {
            Storage::disk('public')->delete($item->image);
            $item->update(['image' => null]);
        }
        return back()->with('success', 'Image removed.');
    }

    public function itemsStockIn(Request $request, InventoryItem $item)
    {
        $request->validate([
            'quantity'    => 'required|integer|min:1',
            'source'      => 'nullable|string|max:255',
            'reference'   => 'nullable|string|max:255',
            'expiry_date' => 'nullable|date',
            'notes'       => 'nullable|string',
        ]);

        DB::transaction(function () use ($request, $item) {
            InventoryStockIn::create([
                'item_id'     => $item->id,
                'quantity'    => $request->quantity,
                'source'      => $request->source,
                'reference'   => $request->reference,
                'expiry_date' => $request->expiry_date,
                'notes'       => $request->notes,
                'created_by'  => auth()->id(),
            ]);
            $item->increment('stock', $request->quantity);
        });

        return back()->with('success', $request->quantity . ' ' . $item->unit . ' added to stock.');
    }

    public function itemsAdjust(Request $request, InventoryItem $item)
    {
        $request->validate([
            'actual_qty' => 'required|integer|min:0',
            'notes'      => 'required|string|max:500',
        ]);

        $before = $item->stock;
        $after  = $request->actual_qty;
        $diff   = $after - $before;

        if ($diff === 0) {
            return back()->with('success', 'No change — stock already matches actual count.');
        }

        DB::transaction(function () use ($item, $before, $after, $diff, $request) {
            InventoryStockIn::create([
                'item_id'         => $item->id,
                'type'            => 'adjustment',
                'quantity'        => $diff,
                'quantity_before' => $before,
                'notes'           => $request->notes,
                'created_by'      => auth()->id(),
            ]);
            $item->update(['stock' => $after]);
        });

        $direction = $diff > 0 ? '+' . $diff : $diff;
        return back()->with('success', "Stock adjusted from {$before} to {$after} ({$direction} {$item->unit}).");
    }

    // -------------------------------------------------------------------------
    // Releases
    // -------------------------------------------------------------------------

    public function releasesIndex(Request $request)
    {
        $query = InventoryRelease::with(['citizen', 'releasedBy'])->latest();

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('search')) {
            $search = $request->search;
            $query->whereHas('citizen', fn($q) => $q->searchName($search));
        }

        $releases = $query->paginate(20)->appends($request->query());

        $stats = [
            'pending'  => InventoryRelease::where('status', 'pending')->count(),
            'approved' => InventoryRelease::where('status', 'approved')->count(),
            'released' => InventoryRelease::where('status', 'released')->count(),
            'rejected' => InventoryRelease::where('status', 'rejected')->count(),
        ];

        return view('inventory.releases.index', compact('releases', 'stats'));
    }

    public function releasesCreate(Request $request)
    {
        $items = InventoryItem::active()->with('category')->orderBy('name')->get();
        $recentCitizens = Citizen::with('addressZone')->where('is_active', 1)
            ->orderByDesc('date_last_updated')->limit(6)->get();
        $selectedCitizen = $request->filled('citizen')
            ? Citizen::find($request->citizen)
            : null;

        return view('inventory.releases.create', compact('items', 'recentCitizens', 'selectedCitizen'));
    }

    public function releasesStore(Request $request)
    {
        $request->validate([
            'citizen_id'   => 'required|exists:eb_citizen,id',
            'purpose'      => 'nullable|string',
            'items'        => 'required|array|min:1',
            'items.*.id'   => 'required|exists:eb_inventory_items,id',
            'items.*.qty'  => 'required|integer|min:1',
        ]);

        DB::transaction(function () use ($request) {
            $release = InventoryRelease::create([
                'citizen_id'  => $request->citizen_id,
                'released_by' => auth()->id(),
                'status'      => 'pending',
                'purpose'     => $request->purpose,
            ]);

            foreach ($request->items as $row) {
                InventoryReleaseItem::create([
                    'release_id' => $release->id,
                    'item_id'    => $row['id'],
                    'quantity'   => $row['qty'],
                ]);
            }
        });

        return redirect()->route('inventory.releases.index')->with('success', 'Release request created.');
    }

    public function releasesShow(InventoryRelease $release)
    {
        $release->load(['citizen', 'releasedBy', 'approvedBy', 'items.item.category']);
        return view('inventory.releases.show', compact('release'));
    }

    public function releasesApprove(InventoryRelease $release)
    {
        if (!$release->isPending()) {
            return back()->with('error', 'Only pending releases can be approved.');
        }

        $release->update([
            'status'      => 'approved',
            'approved_by' => auth()->id(),
            'approved_at' => now(),
        ]);

        return back()->with('success', 'Release approved.');
    }

    public function releasesRelease(InventoryRelease $release)
    {
        if (!$release->isApproved()) {
            return back()->with('error', 'Only approved releases can be released.');
        }

        // Check stock availability
        foreach ($release->items as $ri) {
            if ($ri->item->stock < $ri->quantity) {
                return back()->with('error', "Insufficient stock for {$ri->item->name}. Available: {$ri->item->stock} {$ri->item->unit}.");
            }
        }

        DB::transaction(function () use ($release) {
            $citizen = $release->citizen;
            $citizenName = $citizen ? trim("{$citizen->fname} {$citizen->lname}") : 'Unknown';

            foreach ($release->items as $ri) {
                $before = $ri->item->stock;
                $ri->item->decrement('stock', $ri->quantity);

                InventoryStockIn::create([
                    'item_id'         => $ri->item->id,
                    'type'            => 'release',
                    'quantity'        => -$ri->quantity,
                    'quantity_before' => $before,
                    'source'          => "Released to {$citizenName}",
                    'reference'       => "Release #{$release->id}",
                    'notes'           => $release->purpose,
                    'created_by'      => auth()->id(),
                ]);
            }

            $release->update([
                'status'      => 'released',
                'released_at' => now(),
            ]);
        });

        return back()->with('success', 'Items released to citizen successfully.');
    }

    public function releasesReject(Request $request, InventoryRelease $release)
    {
        $request->validate(['remarks' => 'required|string']);

        $release->update([
            'status'  => 'rejected',
            'remarks' => $request->remarks,
        ]);

        return back()->with('success', 'Release rejected.');
    }

    public function releasesPrint(Request $request, InventoryRelease $release)
    {
        $release->load(['citizen', 'releasedBy', 'approvedBy', 'items.item.category']);
        $settings     = \App\Models\Setting::instance();
        // Letterhead paper chosen in Settings → Print Paper (null = plain header).
        $paper        = \App\Support\Paper::resolve($settings->inventory_paper_template_id, $settings->inventory_paper_version_id);
        return view('inventory.releases.print', compact('release', 'settings', 'paper'));
    }
}
