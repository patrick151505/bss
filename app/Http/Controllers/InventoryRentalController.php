<?php

namespace App\Http\Controllers;

use App\Models\Citizen;
use App\Models\InventoryItem;
use App\Models\InventoryRental;
use App\Models\InventoryRentalItem;
use App\Models\InventoryStockIn;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Rentals — inventory lent to a citizen and expected back.
 *
 * Stock is deducted the moment items go out and restored as they come back,
 * so an item's stock always reflects what is physically on hand. Units
 * returned as lost are never added back.
 */
class InventoryRentalController extends Controller
{
    public function index(Request $request)
    {
        $filter = $request->get('status', 'active');

        if (! in_array($filter, ['active', 'scheduled', 'out', 'overdue', 'returned', 'cancelled', 'all'], true)) {
            $filter = 'active';
        }

        $rentals = InventoryRental::with(['citizen', 'items.item'])
            ->when($filter === 'active',    fn ($q) => $q->active())
            ->when($filter === 'scheduled', fn ($q) => $q->scheduled())
            ->when($filter === 'out',       fn ($q) => $q->out())
            ->when($filter === 'overdue',   fn ($q) => $q->overdue())
            ->when($filter === 'returned',  fn ($q) => $q->where('status', 'returned'))
            ->when($filter === 'cancelled', fn ($q) => $q->where('status', 'cancelled'))
            ->orderByRaw("FIELD(status,'out','scheduled','returned','cancelled')")
            ->orderBy('due_on')
            ->orderByDesc('id')
            ->paginate(20)
            ->withQueryString();

        $stats = [
            'scheduled' => InventoryRental::scheduled()->count(),
            'out'       => InventoryRental::out()->count(),
            'overdue'   => InventoryRental::overdue()->count(),
            'returned'  => InventoryRental::where('status', 'returned')->count(),
        ];

        return view('inventory.rentals.index', compact('rentals', 'stats', 'filter'));
    }

    public function create(Request $request)
    {
        // Only items explicitly flagged rentable can be lent out.
        $items = InventoryItem::active()->rentable()->with('category')->orderBy('name')->get();

        $recentCitizens = Citizen::with('addressZone')->where('is_active', 1)
            ->orderByDesc('date_last_updated')->limit(6)->get();

        $selectedCitizen = $request->filled('citizen') ? Citizen::find($request->citizen) : null;

        return view('inventory.rentals.create', compact('items', 'recentCitizens', 'selectedCitizen'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'citizen_id'         => 'required|exists:eb_citizen,id',
            'borrowed_on'        => 'required|date',
            'due_on'             => 'required|date|after_or_equal:borrowed_on',
            'purpose'            => 'nullable|string|max:255',
            'remarks'            => 'nullable|string',
            'rental_fee'         => 'nullable|numeric|min:0',
            'deposit'            => 'nullable|numeric|min:0',
            'items'              => 'required|array|min:1',
            'items.*.id'         => 'required|exists:eb_inventory_items,id',
            'items.*.qty'        => 'required|integer|min:1',
            'items.*.condition'  => 'nullable|in:good,fair,damaged',
        ], [
            'due_on.after_or_equal' => 'The due date must be on or after the date borrowed.',
            'items.required'        => 'Add at least one item to rent out.',
        ]);

        // Merge duplicate picks of the same item so the stock check below
        // sees the true total being taken out.
        $lines = [];
        foreach ($data['items'] as $row) {
            $id = (int) $row['id'];
            $lines[$id]['qty']       = ($lines[$id]['qty'] ?? 0) + (int) $row['qty'];
            $lines[$id]['condition'] = $row['condition'] ?? 'good';
        }

        $items = InventoryItem::whereIn('id', array_keys($lines))->get()->keyBy('id');

        foreach ($lines as $id => $line) {
            $item = $items->get($id);

            if (! $item->is_rentable) {
                return back()->withInput()->with('error', "\"{$item->name}\" is not marked as rentable.");
            }

            if ($item->stock < $line['qty']) {
                return back()->withInput()->with('error',
                    "Insufficient stock for {$item->name}. Available: {$item->stock} {$item->unit}.");
            }
        }

        $rental = DB::transaction(function () use ($data, $lines, $items) {
            $rental = InventoryRental::create([
                'citizen_id'  => $data['citizen_id'],
                'released_by' => auth()->id(),
                // Every rental starts as a booking; an explicit Release step
                // marks the items as actually handed over.
                'status'      => 'scheduled',
                'borrowed_on' => $data['borrowed_on'],
                'due_on'      => $data['due_on'],
                'purpose'     => $data['purpose'] ?? null,
                'remarks'     => $data['remarks'] ?? null,
                'rental_fee'  => $data['rental_fee'] ?? 0,
                'deposit'     => $data['deposit'] ?? 0,
            ]);

            $borrower = Citizen::find($data['citizen_id']);
            $name     = $borrower?->full_name ?? 'Unknown';

            foreach ($lines as $id => $line) {
                $item = $items->get($id);

                InventoryRentalItem::create([
                    'rental_id'     => $rental->id,
                    'item_id'       => $id,
                    'quantity'      => $line['qty'],
                    'condition_out' => $line['condition'],
                ]);

                $before = $item->stock;
                $item->decrement('stock', $line['qty']);

                InventoryStockIn::create([
                    'item_id'         => $id,
                    'type'            => 'rental_reserved',
                    'quantity'        => -$line['qty'],
                    'quantity_before' => $before,
                    'source'          => "Reserved for {$name}",
                    'reference'       => "Rental #{$rental->id}",
                    'notes'           => $data['purpose'] ?? null,
                    'created_by'      => auth()->id(),
                ]);
            }

            return $rental;
        });

        return redirect()->route('inventory.rentals.show', $rental)
            ->with('success', 'Rental scheduled. Stock is reserved — mark it released when the borrower collects the items.');
    }

    public function show(InventoryRental $rental)
    {
        $rental->load(['citizen', 'releasedBy', 'receivedBy', 'items.item.category']);
        return view('inventory.rentals.show', compact('rental'));
    }

    /**
     * Hand the reserved items over to the borrower. Stock was already
     * deducted when the rental was scheduled, so this only moves the state.
     */
    public function release(InventoryRental $rental)
    {
        if (! $rental->isScheduled()) {
            return back()->with('error', 'Only scheduled rentals can be released.');
        }

        $rental->update([
            'status'      => 'out',
            'released_at' => now(),
            'released_by' => auth()->id(),
        ]);

        return back()->with('success', 'Items released to the borrower.');
    }

    /**
     * Record items coming back. Accepts a partial return: each line may come
     * back in batches, and the rental only closes once every line is settled.
     */
    public function returnItems(Request $request, InventoryRental $rental)
    {
        if (! $rental->isOut()) {
            return back()->with('error', $rental->isScheduled()
                ? 'Mark this rental as released before recording a return.'
                : 'Only rentals that are still out can be returned.');
        }

        $data = $request->validate([
            'returned_on'          => 'required|date',
            'lines'                => 'required|array|min:1',
            'lines.*.id'           => 'required|exists:eb_inventory_rental_items,id',
            'lines.*.qty'          => 'required|integer|min:0',
            'lines.*.condition'    => 'nullable|in:good,fair,damaged,lost',
            'lines.*.notes'        => 'nullable|string|max:500',
        ]);

        $rental->load('items.item');
        $byId = $rental->items->keyBy('id');

        foreach ($data['lines'] as $row) {
            $line = $byId->get($row['id']);

            if (! $line) {
                return back()->with('error', 'That item does not belong to this rental.');
            }

            if ($row['qty'] > $line->outstanding()) {
                return back()->with('error',
                    "Cannot return {$row['qty']} × {$line->item->name} — only {$line->outstanding()} still out.");
            }
        }

        if (collect($data['lines'])->sum('qty') === 0) {
            return back()->with('error', 'Enter a quantity for at least one item.');
        }

        DB::transaction(function () use ($data, $rental, $byId) {
            $name = $rental->citizen?->full_name ?? 'Unknown';

            foreach ($data['lines'] as $row) {
                $qty  = (int) $row['qty'];
                $line = $byId->get($row['id']);

                if ($qty === 0 && empty($row['condition'])) {
                    continue;
                }

                $condition = $row['condition'] ?? 'good';

                $line->update([
                    'quantity_returned' => $line->quantity_returned + $qty,
                    'condition_in'      => $condition,
                    'condition_notes'   => $row['notes'] ?? $line->condition_notes,
                ]);

                // Lost units never come back on the shelf.
                if ($qty > 0 && $condition !== 'lost') {
                    $item   = $line->item;
                    $before = $item->stock;
                    $item->increment('stock', $qty);

                    InventoryStockIn::create([
                        'item_id'         => $item->id,
                        'type'            => 'rental_return',
                        'quantity'        => $qty,
                        'quantity_before' => $before,
                        'source'          => "Returned by {$name}",
                        'reference'       => "Rental #{$rental->id}",
                        'notes'           => $row['notes'] ?? null,
                        'created_by'      => auth()->id(),
                    ]);
                }
            }

            // Close the rental once nothing is outstanding. Lost units count
            // as settled — they are not coming back.
            $rental->load('items');

            $stillOut = $rental->items->sum(function ($line) {
                return $line->condition_in === 'lost'
                    ? 0
                    : max(0, $line->quantity - $line->quantity_returned);
            });

            if ($stillOut === 0) {
                $rental->update([
                    'status'      => 'returned',
                    'returned_on' => $data['returned_on'],
                    'received_by' => auth()->id(),
                ]);
            }
        });

        $rental->refresh();

        return back()->with('success', $rental->isReturned()
            ? 'All items returned. Rental closed.'
            : 'Partial return recorded. Some items are still out.');
    }

    /** Toggle fee paid / deposit refunded. */
    public function updatePayment(Request $request, InventoryRental $rental)
    {
        $request->validate([
            'fee_paid'         => 'nullable|boolean',
            'deposit_refunded' => 'nullable|boolean',
        ]);

        $rental->update([
            'fee_paid'         => $request->boolean('fee_paid'),
            'deposit_refunded' => $request->boolean('deposit_refunded'),
        ]);

        return back()->with('success', 'Payment details updated.');
    }

    /**
     * Cancel a booking or an active rental, returning every outstanding
     * unit to stock.
     */
    public function cancel(Request $request, InventoryRental $rental)
    {
        if (! $rental->isActive()) {
            return back()->with('error', 'Only scheduled or out rentals can be cancelled.');
        }

        $data = $request->validate(['remarks' => 'required|string|max:500']);

        DB::transaction(function () use ($rental, $data) {
            $rental->load('items.item');

            foreach ($rental->items as $line) {
                $qty = $line->outstanding();

                if ($qty <= 0) {
                    continue;
                }

                $item   = $line->item;
                $before = $item->stock;
                $item->increment('stock', $qty);

                InventoryStockIn::create([
                    'item_id'         => $item->id,
                    'type'            => 'rental_cancel',
                    'quantity'        => $qty,
                    'quantity_before' => $before,
                    'source'          => 'Rental cancelled',
                    'reference'       => "Rental #{$rental->id}",
                    'notes'           => $data['remarks'],
                    'created_by'      => auth()->id(),
                ]);
            }

            $rental->update(['status' => 'cancelled', 'remarks' => $data['remarks']]);
        });

        return back()->with('success', 'Rental cancelled and stock restored.');
    }
}
