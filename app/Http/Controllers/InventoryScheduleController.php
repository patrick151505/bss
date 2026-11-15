<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\InventoryItem;
use App\Models\InventorySchedule;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Inventory → Schedule: a calendar of date/time reservations for items marked
 * "Can be scheduled" (ambulance, hall, vehicle…). Staff click a date to book;
 * overlapping bookings of an item can never exceed its stock.
 */
class InventoryScheduleController extends Controller
{
    /** Automatic colors when an item has no calendar color of its own (stable: by item id). */
    const PALETTE = ['#3073F1', '#0acf97', '#fa5c7c', '#f59e0b', '#8b5cf6', '#14b8a6', '#ec4899', '#64748b', '#22c55e', '#0ea5e9'];

    public static function colorFor(?InventoryItem $item, ?int $itemId = null): string
    {
        if ($item?->schedule_color) return $item->schedule_color;
        $id = $item?->id ?? $itemId ?? 0;
        return self::PALETTE[$id % count(self::PALETTE)];
    }

    private static function bufferOf(?InventoryItem $item): int
    {
        return max(0, (int) ($item?->schedule_buffer_days ?? 0));
    }

    public function index()
    {
        $items = InventoryItem::schedulable()->where('is_active', true)->orderBy('name')
            ->get(['id', 'name', 'unit', 'stock', 'schedule_color', 'schedule_buffer_days'])
            ->map(fn($i) => ['id' => $i->id, 'name' => $i->name, 'unit' => $i->unit, 'stock' => (int) $i->stock,
                             'color' => self::colorFor($i), 'buffer' => self::bufferOf($i)]);

        return view('inventory.schedule.index', compact('items'));
    }

    // Reservations in the visible calendar range (FullCalendar sends start/end).
    public function events(Request $request)
    {
        $request->validate([
            'start'   => 'required|date',
            'end'     => 'required|date',
            'item_id' => 'nullable|integer',
        ]);

        $rows = InventorySchedule::with(['item:id,name,unit,schedule_color,schedule_buffer_days', 'citizen:id,fname,mname,lname,suffix', 'creator:id,name'])
            ->where('start_at', '<', Carbon::parse($request->end))
            ->where('end_at', '>', Carbon::parse($request->start))
            ->when($request->filled('item_id'), fn($q) => $q->where('item_id', (int) $request->item_id))
            ->when(!$request->boolean('show_cancelled'), fn($q) => $q->where('status', '!=', 'cancelled'))
            ->orderBy('start_at')
            ->get();

        $events = collect();
        foreach ($rows as $s) {
            $color = self::colorFor($s->item, $s->item_id);
            $events->push([
                'id'         => $s->id,
                'title'      => ($s->quantity > 1 ? $s->quantity . '× ' : '') . ($s->item?->name ?? 'Item') . ' · ' . $s->for_name,
                'start'      => $s->start_at->toIso8601String(),
                'end'        => $s->end_at->toIso8601String(),
                'color'      => $color,
                'classNames' => ['sched-' . $s->status],
                'extendedProps' => $this->details($s),
            ]);
            // The buffer after an active reservation, shaded on the calendar.
            $buffer = self::bufferOf($s->item);
            if ($s->status === 'reserved' && $buffer > 0) {
                // Whole days (month view only draws all-day background events): from the
                // day after the reservation ends through the day it is free again.
                $freeAt  = $s->end_at->copy()->addDays($buffer);
                $bufFrom = $s->end_at->copy()->startOfDay()->addDay();
                $bufTo   = $freeAt->copy()->startOfDay()->addDay();   // exclusive end
                $events->push([
                    'id'         => 'buffer-' . $s->id,
                    'title'      => ($s->item?->name ?? 'Item') . ' buffer — free again ' . $freeAt->format('M d, Y g:i A'),
                    'start'      => $bufFrom->toDateString(),
                    'end'        => $bufTo->toDateString(),
                    'allDay'     => true,
                    'display'    => 'background',
                    'color'      => $color,
                    'classNames' => ['sched-buffer'],
                    'extendedProps' => ['buffer' => true, 'item_id' => $s->item_id],
                ]);
            }
        }

        return response()->json($events->values());
    }

    private function details(InventorySchedule $s): array
    {
        return [
            'item_id'        => $s->item_id,
            'item'           => $s->item?->name,
            'unit'           => $s->item?->unit,
            'quantity'       => $s->quantity,
            'citizen_id'     => $s->citizen_id,
            'for'            => $s->for_name,
            'requester_name' => $s->requester_name,
            'contact'        => $s->contact,
            'purpose'        => $s->purpose,
            'location'       => $s->location,
            'notes'          => $s->notes,
            'status'         => $s->status,
            'status_label'   => InventorySchedule::STATUSES[$s->status] ?? ucfirst($s->status),
            'cancel_reason'  => $s->cancel_reason,
            'when'           => $this->rangeLabel($s->start_at, $s->end_at),
            'buffer_days'    => self::bufferOf($s->item),
            'free_from'      => self::bufferOf($s->item) > 0 ? $s->end_at->copy()->addDays(self::bufferOf($s->item))->format('M d, Y g:i A') : null,
            'start_local'    => $s->start_at->format('Y-m-d\TH:i'),
            'end_local'      => $s->end_at->format('Y-m-d\TH:i'),
            'created_by'     => $s->creator?->name,
            'created_at'     => $s->created_at?->format('M d, Y g:i A'),
        ];
    }

    private function rangeLabel(Carbon $start, Carbon $end): string
    {
        return $start->isSameDay($end)
            ? $start->format('M d, Y g:i A') . ' – ' . $end->format('g:i A')
            : $start->format('M d, Y g:i A') . ' – ' . $end->format('M d, Y g:i A');
    }

    // Live check for the reservation form.
    public function availability(Request $request)
    {
        $request->validate([
            'item_id'   => 'required|integer|exists:eb_inventory_items,id',
            'start_at'  => 'required|date',
            'end_at'    => 'required|date|after:start_at',
            'except_id' => 'nullable|integer',
        ]);

        $item   = InventoryItem::findOrFail($request->item_id);
        $buffer = self::bufferOf($item);
        $busy = InventorySchedule::with('citizen:id,fname,mname,lname,suffix')
            ->overlapping($item->id, Carbon::parse($request->start_at), Carbon::parse($request->end_at), $request->integer('except_id') ?: null, $buffer)
            ->orderBy('start_at')->get();
        $reserved = (int) $busy->sum('quantity');

        return response()->json([
            'stock'     => (int) $item->stock,
            'reserved'  => $reserved,
            'available' => max(0, (int) $item->stock - $reserved),
            'buffer'    => $buffer,
            'conflicts' => $busy->map(fn($s) => [
                'when'     => $this->rangeLabel($s->start_at, $s->end_at)
                    . ($buffer > 0 ? ' (+' . $buffer . '-day buffer)' : ''),
                'for'      => $s->for_name,
                'quantity' => $s->quantity,
            ])->values(),
        ]);
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'item_id'        => 'required|integer|exists:eb_inventory_items,id',
            'quantity'       => 'required|integer|min:1|max:100000',
            'start_at'       => 'required|date',
            'end_at'         => 'required|date|after:start_at',
            'citizen_id'     => 'nullable|integer|exists:eb_citizen,id',
            'requester_name' => 'required_without:citizen_id|nullable|string|max:150',
            'contact'        => 'nullable|string|max:30',
            'purpose'        => 'required|string|max:255',
            'location'       => 'nullable|string|max:255',
            'notes'          => 'nullable|string|max:2000',
        ], [
            'requester_name.required_without' => 'Choose a citizen or type the name of who it is for.',
            'end_at.after'                    => 'The end must be after the start.',
        ]);
    }

    /**
     * Inside a transaction with the item row locked: is there room for $qty of
     * the item between $start and $end? Throws a validation error if not.
     */
    private function assertAvailable(int $itemId, int $qty, Carbon $start, Carbon $end, ?int $exceptId = null): InventoryItem
    {
        $item = InventoryItem::whereKey($itemId)->lockForUpdate()->firstOrFail();

        if (!$item->is_schedulable || !$item->is_active) {
            throw ValidationException::withMessages(['item_id' => "{$item->name} cannot be scheduled."]);
        }
        if ($item->stock < 1) {
            throw ValidationException::withMessages(['item_id' => "{$item->name} has no units in stock. Add stock to the item first."]);
        }

        $buffer   = self::bufferOf($item);
        $reserved = (int) InventorySchedule::overlapping($item->id, $start, $end, $exceptId, $buffer)->sum('quantity');
        $free     = (int) $item->stock - $reserved;
        if ($qty > $free) {
            $note = $buffer > 0 ? " (each reservation is followed by a {$buffer}-day buffer)" : '';
            throw ValidationException::withMessages(['quantity' => $free > 0
                ? "Only {$free} {$item->unit} of {$item->name} available at that time{$note}."
                : "{$item->name} is already reserved at that time{$note}."]);
        }
        return $item;
    }

    public function store(Request $request)
    {
        $data  = $this->validated($request);
        $start = Carbon::parse($data['start_at']);
        $end   = Carbon::parse($data['end_at']);
        if ($start->lt(now()->startOfDay())) {
            throw ValidationException::withMessages(['start_at' => 'Reservations cannot start on a past date.']);
        }

        $schedule = DB::transaction(function () use ($data, $start, $end) {
            $this->assertAvailable((int) $data['item_id'], (int) $data['quantity'], $start, $end);
            return InventorySchedule::create($data + [
                'start_at'   => $start,
                'end_at'     => $end,
                'status'     => 'reserved',
                'created_by' => auth()->id(),
            ]);
        });

        $schedule->load(['item', 'citizen']);
        ActivityLog::record('created', 'inventory_schedule', $schedule->id,
            "Reserved " . ($schedule->quantity > 1 ? "{$schedule->quantity}× " : '') . "{$schedule->item->name} for {$schedule->for_name}: " . $this->rangeLabel($start, $end));

        return response()->json(['success' => true, 'id' => $schedule->id]);
    }

    public function update(Request $request, InventorySchedule $schedule)
    {
        if ($schedule->status !== 'reserved') {
            throw ValidationException::withMessages(['status' => 'Only reserved bookings can be changed.']);
        }
        $data  = $this->validated($request);
        $start = Carbon::parse($data['start_at']);
        $end   = Carbon::parse($data['end_at']);
        if (!$start->equalTo($schedule->start_at) && $start->lt(now()->startOfDay())) {
            throw ValidationException::withMessages(['start_at' => 'Reservations cannot be moved to a past date.']);
        }

        DB::transaction(function () use ($schedule, $data, $start, $end) {
            $this->assertAvailable((int) $data['item_id'], (int) $data['quantity'], $start, $end, $schedule->id);
            $schedule->update($data + ['start_at' => $start, 'end_at' => $end, 'updated_by' => auth()->id()]);
        });

        $schedule->load(['item', 'citizen']);
        ActivityLog::record('updated', 'inventory_schedule', $schedule->id,
            "Changed reservation of {$schedule->item->name} for {$schedule->for_name}: " . $this->rangeLabel($start, $end));

        return response()->json(['success' => true]);
    }

    public function complete(InventorySchedule $schedule)
    {
        if ($schedule->status !== 'reserved') {
            return response()->json(['success' => false, 'message' => 'Only reserved bookings can be completed.'], 422);
        }
        $schedule->update(['status' => 'completed', 'updated_by' => auth()->id()]);
        ActivityLog::record('completed', 'inventory_schedule', $schedule->id,
            "Completed reservation of {$schedule->item?->name} for {$schedule->for_name}");

        return response()->json(['success' => true]);
    }

    public function cancel(Request $request, InventorySchedule $schedule)
    {
        $request->validate(['reason' => 'required|string|max:500'], ['reason.required' => 'Please give the reason for cancelling.']);
        if ($schedule->status !== 'reserved') {
            return response()->json(['success' => false, 'message' => 'Only reserved bookings can be cancelled.'], 422);
        }
        $schedule->update(['status' => 'cancelled', 'cancel_reason' => trim($request->reason), 'updated_by' => auth()->id()]);
        ActivityLog::record('cancelled', 'inventory_schedule', $schedule->id,
            "Cancelled reservation of {$schedule->item?->name} for {$schedule->for_name} — " . trim($request->reason));

        return response()->json(['success' => true]);
    }
}
