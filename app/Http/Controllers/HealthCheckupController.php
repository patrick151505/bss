<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\Citizen;
use App\Models\HealthCheckup;
use App\Models\HealthCheckupType;
use App\Models\InventoryItem;
use App\Models\InventoryRelease;
use App\Models\InventoryReleaseItem;
use App\Models\InventoryStockIn;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

/**
 * Health check-ups (libreng check-up, walk-in consultations).
 *
 * Medicines given at a check-up come out of Inventory stock (health.dispense).
 * For a citizen this is recorded as a released Inventory release, so it also
 * shows in Inventory → Releases and the citizen's history; walk-ins without a
 * citizen record get a stock log entry only. Medicines can't be changed after
 * saving, since the stock has already moved.
 */
class HealthCheckupController extends Controller
{
    public function index(Request $request)
    {
        // Date range: Today / 1 week / 1 month, or Custom (From–To).
        [$from, $to, $period] = $this->dateRange($request);

        $q = HealthCheckup::with(['citizen:id,fname,mname,lname,suffix,bday,gender,profile', 'medicines'])
            ->search($request->search)
            ->when($request->type, fn($q, $t) => $q->where('checkup_type', $t))
            ->when($from, fn($q, $d) => $q->whereDate('checked_at', '>=', $d))
            ->when($to, fn($q, $d) => $q->whereDate('checked_at', '<=', $d))
            ->when($request->flag === 'high_bp', fn($q) => $q->whereRaw(HealthCheckup::HIGH_BP_SQL))
            ->when($request->flag === 'fever', fn($q) => $q->where('temperature', '>=', 37.5))
            ->when($request->flag === 'high_sugar', fn($q) => $q->where(fn($w) => $w
                ->where(fn($x) => $x->where('sugar_fasting', true)->where('blood_sugar', '>=', 126))
                ->orWhere(fn($x) => $x->where('sugar_fasting', false)->where('blood_sugar', '>=', 200))))
            ->when($request->flag === 'follow_up', fn($q) => $q->whereDate('follow_up_on', '>=', today()));

        $checkups = $request->flag === 'follow_up'
            ? $q->orderBy('follow_up_on')->paginate(20)->withQueryString()
            : $q->orderByDesc('checked_at')->paginate(20)->withQueryString();

        $month = [now()->startOfMonth(), now()->endOfMonth()];
        $stats = [
            'today'     => HealthCheckup::whereDate('checked_at', today())->count(),
            'month'     => HealthCheckup::whereBetween('checked_at', $month)->count(),
            'high_bp'   => HealthCheckup::whereBetween('checked_at', $month)->whereRaw(HealthCheckup::HIGH_BP_SQL)->count(),
            'follow_up' => HealthCheckup::whereBetween('follow_up_on', [today(), today()->addDays(7)])->count(),
        ];

        // Types for the filter: the saved list plus any older names still on check-ups.
        $types = HealthCheckupType::ordered()->pluck('name')
            ->merge(HealthCheckup::whereNotNull('checkup_type')->distinct()->pluck('checkup_type'))
            ->unique(fn ($n) => mb_strtolower($n))->values();

        return view('health.index', compact('checkups', 'stats', 'types', 'from', 'to', 'period'));
    }

    /**
     * period = today | week (last 7 days) | month (last 30 days) | custom.
     * A From/To with no period (e.g. from a stat card) counts as custom.
     * Returns [from, to, period] with dates as Y-m-d (or null).
     */
    private function dateRange(Request $request): array
    {
        $period = $request->input('period');
        $today  = today();

        return match ($period) {
            'today' => [$today->toDateString(), $today->toDateString(), 'today'],
            'week'  => [$today->copy()->subDays(6)->toDateString(), $today->toDateString(), 'week'],
            'month' => [$today->copy()->subDays(29)->toDateString(), $today->toDateString(), 'month'],
            default => ($request->filled('from') || $request->filled('to'))
                ? [$request->input('from') ?: null, $request->input('to') ?: null, 'custom']
                : [null, null, $period === 'custom' ? 'custom' : ''],
        };
    }

    public function create(Request $request)
    {
        $checkup = new HealthCheckup(['checked_at' => now()]);
        $patient = $request->filled('citizen')
            ? $this->patientJson(Citizen::where('is_active', 1)->find((int) $request->citizen))
            : null;

        return view('health.create', ['checkup' => $checkup, 'patient' => $patient, 'medicineItems' => $this->medicineItems(), 'checkupTypes' => $this->checkupTypes()]);
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);
        $canDispense = $request->user()->can('health.dispense');
        $rows = $canDispense ? $this->medicineRows($request->input('medicines', [])) : [];

        $rx = $this->prescriptionRows($request->input('prescriptions', []));

        $checkup = DB::transaction(function () use ($data, $rows, $rx) {
            $checkup = HealthCheckup::create($data + ['created_by' => auth()->id(), 'updated_by' => auth()->id()]);
            if ($rows) $this->dispense($checkup, $rows);
            $this->savePrescription($checkup, $rx);
            return $checkup;
        });

        $notes = array_filter([
            $rows ? count($rows) . ' medicine' . (count($rows) > 1 ? 's' : '') . ' given' : null,
            $rx ? count($rx) . ' on prescription' : null,
        ]);
        ActivityLog::record('created', 'HealthCheckup', $checkup->id, "Recorded check-up for {$checkup->patientName()}"
            . ($notes ? ' (' . implode(', ', $notes) . ')' : ''));

        if ($request->input('after') === 'another') {
            return redirect()->route('health.create')->with('success', "Check-up for {$checkup->patientName()} saved.");
        }
        return redirect()->route('health.show', $checkup)->with('success', 'Check-up saved.');
    }

    public function show(HealthCheckup $checkup)
    {
        $checkup->load(['citizen', 'medicines', 'prescriptions', 'release', 'creator']);
        // Earlier and later visits of the same patient, for trends.
        $history = $checkup->citizen_id
            ? HealthCheckup::where('citizen_id', $checkup->citizen_id)->whereKeyNot($checkup->id)
                ->orderByDesc('checked_at')->limit(10)->get()
            : collect();

        return view('health.show', compact('checkup', 'history'));
    }

    /** Printable check-up record, on the paper chosen in Settings → Print Paper. */
    public function print(HealthCheckup $checkup)
    {
        $checkup->load(['citizen.addressZone', 'medicines', 'creator']);
        $settings = \App\Models\Setting::instance();
        // null = plain built-in header
        $paper = \App\Support\Paper::resolve($settings->health_paper_template_id, $settings->health_paper_version_id);

        return view('health.print', compact('checkup', 'settings', 'paper'));
    }

    /** Printable prescription (reseta) for medicines to buy outside. */
    public function prescription(HealthCheckup $checkup)
    {
        $checkup->load(['citizen.addressZone', 'prescriptions']);
        abort_if($checkup->prescriptions->isEmpty(), 404);
        $settings = \App\Models\Setting::instance();

        return view('health.prescription', compact('checkup', 'settings'));
    }

    public function edit(HealthCheckup $checkup)
    {
        $checkup->load(['citizen', 'medicines', 'prescriptions']);
        return view('health.edit', [
            'checkup'       => $checkup,
            'patient'       => $checkup->citizen ? $this->patientJson($checkup->citizen) : null,
            'medicineItems' => collect(),
            'checkupTypes'  => $this->checkupTypes(),
        ]);
    }

    public function update(Request $request, HealthCheckup $checkup)
    {
        $data = $this->validated($request);
        $rx = $this->prescriptionRows($request->input('prescriptions', []));
        $before = $checkup->prescriptions()->count();

        DB::transaction(function () use ($checkup, $data, $rx) {
            $checkup->update($data + ['updated_by' => auth()->id()]);
            $this->savePrescription($checkup, $rx);
        });

        ActivityLog::record('updated', 'HealthCheckup', $checkup->id, "Updated check-up of {$checkup->patientName()}"
            . ($before !== count($rx) ? ' (prescription: ' . count($rx) . ' medicine' . (count($rx) === 1 ? '' : 's') . ')' : ''));

        return redirect()->route('health.show', $checkup)->with('success', 'Changes saved.');
    }

    public function destroy(HealthCheckup $checkup)
    {
        $checkup->delete();
        ActivityLog::record('deleted', 'HealthCheckup', $checkup->id, "Deleted check-up of {$checkup->patientName()} ({$checkup->checked_at->format('M d, Y')})");

        return redirect()->route('health.index')->with('success', 'Check-up deleted.');
    }

    /** Patient picker: citizens by name/contact/QR, with their last check-up. */
    public function patients(Request $request)
    {
        $term = trim((string) $request->q);
        if (mb_strlen($term) < 2) return response()->json([]);

        $citizens = Citizen::where('is_active', 1)->searchName($term)
            ->orderBy('lname')->orderBy('fname')->limit(10)->get();

        return response()->json($citizens->map(fn($c) => $this->patientJson($c))->values());
    }

    // ── Helpers ────────────────────────────────────────────────────────────

    private function patientJson(?Citizen $c): ?array
    {
        if (!$c) return null;
        $last = HealthCheckup::where('citizen_id', $c->id)->latest('checked_at')->first();
        return [
            'id'      => $c->id,
            'name'    => $c->full_name,
            'age'     => $c->age,
            'sex'     => $c->gender === null ? null : ((int) $c->gender === 1 ? 'Male' : 'Female'),
            'address' => $c->full_address,
            'qrcode'  => $c->qrcode ?? '',
            'photo'   => $c->profile_url,
            'last'    => $last ? [
                'date'   => $last->checked_at->format('M d, Y'),
                'bp'     => $last->bloodPressure(),
                'weight' => $last->weight_kg,
                'url'    => route('health.show', $last),
            ] : null,
        ];
    }

    /** Active items with stock, for the medicines picker (grouped by category in the view). */
    private function medicineItems()
    {
        if (!auth()->user()->can('health.dispense')) return collect();
        // Only items whose category is marked "Use for medicine" (Inventory → Categories).
        return InventoryItem::with('category:id,name')->where('is_active', true)->where('stock', '>', 0)
            ->whereHas('category', fn($c) => $c->medicine())
            ->orderBy('name')->get(['id', 'name', 'unit', 'stock', 'category_id']);
    }

    /** Saved check-up types for the form's Type box (active ones, in order). */
    private function checkupTypes()
    {
        return HealthCheckupType::active()->ordered()->pluck('name');
    }

    private function validated(Request $request): array
    {
        $request->validate([
            'citizen_id'    => ['nullable', 'integer', 'exists:eb_citizen,id'],
            'patient_name'  => ['nullable', 'required_without:citizen_id', 'string', 'max:255'],
            'patient_age'   => ['nullable', 'integer', 'min:0', 'max:130'],
            'patient_sex'   => ['nullable', Rule::in(['M', 'F'])],
            'checked_at'    => ['required', 'date', 'before_or_equal:' . now()->addDay()->toDateString()],
            'checkup_type'  => ['required', 'string', 'max:100'],
            'attended_by'   => ['nullable', 'string', 'max:255'],
            'bp_systolic'   => ['nullable', 'integer', 'between:50,300', 'required_with:bp_diastolic'],
            'bp_diastolic'  => ['nullable', 'integer', 'between:30,200', 'required_with:bp_systolic'],
            'pulse'         => ['nullable', 'integer', 'between:20,250'],
            'resp_rate'     => ['nullable', 'integer', 'between:5,80'],
            'temperature'   => ['nullable', 'numeric', 'between:30,45'],
            'o2_sat'        => ['nullable', 'integer', 'between:50,100'],
            'weight_kg'     => ['nullable', 'numeric', 'between:0.5,400'],
            'height_cm'     => ['nullable', 'numeric', 'between:30,250'],
            'blood_sugar'   => ['nullable', 'numeric', 'between:10,1000'],
            'complaint'     => ['nullable', 'string', 'max:5000'],
            'findings'      => ['nullable', 'string', 'max:5000'],
            'advice'        => ['nullable', 'string', 'max:5000'],
            'referred_to'   => ['nullable', 'string', 'max:255'],
            'follow_up_on'  => ['nullable', 'date'],
            'medicines'               => ['nullable', 'array'],
            'medicines.*.item_id'     => ['nullable', 'integer'],
            'medicines.*.quantity'    => ['nullable', 'integer', 'min:1', 'max:10000'],
            'medicines.*.instructions'=> ['nullable', 'string', 'max:255'],
            'prescriber_license'        => ['nullable', 'string', 'max:50'],
            'prescriber_ptr'            => ['nullable', 'string', 'max:50'],
            'prescriptions'             => ['nullable', 'array', 'max:30'],
            'prescriptions.*.medicine'  => ['nullable', 'string', 'max:255'],
            'prescriptions.*.quantity'  => ['nullable', 'string', 'max:50'],
            'prescriptions.*.sig'       => ['nullable', 'string', 'max:255'],
        ], [
            'patient_name.required_without' => 'Pick a citizen, or type the walk-in patient’s name.',
            'bp_systolic.required_with'     => 'Enter both numbers of the blood pressure (e.g. 120 / 80).',
            'bp_diastolic.required_with'    => 'Enter both numbers of the blood pressure (e.g. 120 / 80).',
            'checked_at.before_or_equal'    => 'The check-up date can’t be in the future.',
            'checkup_type.required'         => 'Choose the type of check-up, or type a new one.',
        ], [
            'bp_systolic' => 'blood pressure (upper)', 'bp_diastolic' => 'blood pressure (lower)',
            'o2_sat' => 'oxygen saturation', 'resp_rate' => 'respiratory rate', 'weight_kg' => 'weight', 'height_cm' => 'height',
            'prescriber_license' => 'licence number', 'prescriber_ptr' => 'PTR number',
            'prescriptions.*.medicine' => 'prescription medicine', 'prescriptions.*.quantity' => 'prescription quantity', 'prescriptions.*.sig' => 'how to take',
        ]);

        $data = $request->only([
            'citizen_id', 'checked_at', 'attended_by', 'prescriber_license', 'prescriber_ptr', 'bp_systolic', 'bp_diastolic', 'pulse', 'resp_rate',
            'temperature', 'o2_sat', 'weight_kg', 'height_cm', 'blood_sugar',
            'complaint', 'findings', 'advice', 'referred_to', 'follow_up_on',
        ]);
        $data['sugar_fasting'] = $request->boolean('sugar_fasting');
        // Reuse a saved type (any letter case) or add the typed one for next time.
        $data['checkup_type'] = HealthCheckupType::resolve($request->input('checkup_type'));
        // A citizen's name/age/sex come from their record; keep walk-in fields only for walk-ins.
        $walkIn = !$request->filled('citizen_id');
        $data['patient_name'] = $walkIn ? trim((string) $request->patient_name) : null;
        $data['patient_age']  = $walkIn ? $request->patient_age : null;
        $data['patient_sex']  = $walkIn ? $request->patient_sex : null;
        return $data;
    }

    /** Clean the prescription rows: rows without a medicine name are dropped. */
    private function prescriptionRows($rows): array
    {
        $out = [];
        foreach ((array) $rows as $r) {
            $name = trim((string) ($r['medicine'] ?? ''));
            if ($name === '') continue;
            $out[] = [
                'medicine' => $name,
                'quantity' => trim((string) ($r['quantity'] ?? '')) ?: null,
                'sig'      => trim((string) ($r['sig'] ?? '')) ?: null,
            ];
        }
        return $out;
    }

    /** Replace the check-up's prescription with these rows (nothing touches stock). */
    private function savePrescription(HealthCheckup $checkup, array $rows): void
    {
        $checkup->prescriptions()->delete();
        foreach ($rows as $i => $r) {
            $checkup->prescriptions()->create($r + ['sort_order' => $i]);
        }
    }

    /** Clean the submitted medicine rows; the same item twice is merged. */
    private function medicineRows(array $rows): array
    {
        $out = [];
        foreach ($rows as $r) {
            $id = (int) ($r['item_id'] ?? 0);
            $qty = (int) ($r['quantity'] ?? 0);
            if (!$id || $qty < 1) continue;
            if (isset($out[$id])) {
                $out[$id]['quantity'] += $qty;
            } else {
                $out[$id] = ['item_id' => $id, 'quantity' => $qty, 'instructions' => trim((string) ($r['instructions'] ?? '')) ?: null];
            }
        }
        return array_values($out);
    }

    /** Take the medicines out of stock. Must run inside a transaction. */
    private function dispense(HealthCheckup $checkup, array $rows): void
    {
        $items = InventoryItem::with('category')->whereIn('id', array_column($rows, 'item_id'))->lockForUpdate()->get()->keyBy('id');
        foreach ($rows as $r) {
            $item = $items[$r['item_id']] ?? null;
            // Only items from a "Use for medicine" category may be given (blocks hand-edited forms too).
            if (!$item || !$item->is_active || !$item->category?->is_medicine) {
                throw ValidationException::withMessages(['medicines' => 'One of the medicines is no longer available. Pick it again.']);
            }
            if ($item->stock < $r['quantity']) {
                throw ValidationException::withMessages(['medicines' => "Not enough {$item->name}: only {$item->stock} {$item->unit} left."]);
            }
        }

        $name = $checkup->patientName();
        $release = null;
        if ($checkup->citizen_id) {
            $release = InventoryRelease::create([
                'citizen_id'  => $checkup->citizen_id,
                'released_by' => auth()->id(),
                'status'      => 'released',
                'purpose'     => "Health check-up #{$checkup->id}",
                'approved_by' => auth()->id(),
                'approved_at' => now(),
                'released_at' => now(),
            ]);
            $checkup->update(['release_id' => $release->id]);
        }

        foreach ($rows as $r) {
            $item = $items[$r['item_id']];
            $before = $item->stock;
            $item->decrement('stock', $r['quantity']);
            if ($release) {
                InventoryReleaseItem::create(['release_id' => $release->id, 'item_id' => $item->id, 'quantity' => $r['quantity']]);
            }
            InventoryStockIn::create([
                'item_id'         => $item->id,
                'type'            => 'release',
                'quantity'        => -$r['quantity'],
                'quantity_before' => $before,
                'source'          => "Health check-up — {$name}",
                'reference'       => "Check-up #{$checkup->id}" . ($release ? " / Release #{$release->id}" : ''),
                'notes'           => $r['instructions'],
                'created_by'      => auth()->id(),
            ]);
            $checkup->medicines()->create([
                'item_id'      => $item->id,
                'name'         => $item->name,
                'unit'         => $item->unit,
                'quantity'     => $r['quantity'],
                'instructions' => $r['instructions'],
            ]);
        }
    }
}
