<?php

namespace App\Http\Controllers;

use App\Models\Address;
use App\Models\Citizen;
use App\Models\CitizenId;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class CitizenIdController extends Controller
{
    public function index(Request $request)
    {
        $query = CitizenId::with(['citizen.addressZone', 'generatedBy'])
            ->orderByDesc('created_at');

        if ($s = $request->search) {
            $query->whereHas('citizen', function ($q) use ($s) {
                $q->where('fname', 'like', "%$s%")
                  ->orWhere('lname', 'like', "%$s%")
                  ->orWhere('mname', 'like', "%$s%")
                  ->orWhere('qrcode', $s);
            });
        }

        if ($addr = $request->address) {
            $query->whereHas('citizen', fn($q) => $q->where('address', $addr));
        }

        // Date range: a preset (today/week/month) resolves to a start/end range,
        // or "custom" uses the manual date_start/date_end pickers.
        [$dateStart, $dateEnd] = $this->resolveDateRange($request);
        if ($dateStart) {
            $query->whereDate('created_at', '>=', $dateStart);
        }
        if ($dateEnd) {
            $query->whereDate('created_at', '<=', $dateEnd);
        }

        $perPage   = in_array($request->show, [5, 10, 20, 50, 100]) ? (int) $request->show : 10;
        $ids       = $query->paginate($perPage)->withQueryString();
        $addresses = Address::where('is_active', true)->orderBy('description')->get();

        // KPI stats — IDs created per period (across all IDs, not the filtered page).
        $stats = [
            'today' => CitizenId::whereDate('created_at', now()->toDateString())->count(),
            'week'  => CitizenId::whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()])->count(),
            'month' => CitizenId::whereBetween('created_at', [now()->startOfMonth(), now()->endOfMonth()])->count(),
            'total' => CitizenId::count(),
        ];

        // Live filtering: return just the results partial + stats for AJAX requests.
        if ($request->ajax()) {
            $idsView = in_array($request->view, ['list', 'grid']) ? $request->view : 'list';
            return response()->json([
                'html'  => view('citizens.ids._results', compact('ids', 'idsView'))->render(),
                'stats' => $stats,
            ]);
        }

        return view('citizens.ids.index', compact('ids', 'addresses', 'stats'));
    }

    /**
     * Resolve the date filter into a [start, end] pair of Y-m-d strings.
     * A `range` preset (today/week/month) wins; `custom` uses the manual
     * date_start/date_end fields; anything else means no date filter.
     */
    protected function resolveDateRange(Request $request): array
    {
        $today = now();

        return match ($request->get('range')) {
            'today' => [$today->toDateString(), $today->toDateString()],
            'week'  => [$today->copy()->startOfWeek()->toDateString(),  $today->copy()->endOfWeek()->toDateString()],
            'month' => [$today->copy()->startOfMonth()->toDateString(), $today->copy()->endOfMonth()->toDateString()],
            'custom' => [
                $request->date_start ?: null,
                $request->date_end   ?: null,
            ],
            // Back-compat: manual dates still work even without choosing "custom".
            default => [
                $request->date_start ?: null,
                $request->date_end   ?: null,
            ],
        };
    }

    public function store(Request $request)
    {
        $request->validate([
            'citizen_id' => 'required|integer',
        ]);

        $citizen = Citizen::findOrFail($request->citizen_id);

        // Validity is a barangay-wide setting, applied to every new ID.
        $validUntil = \App\Models\Setting::instance()->idValidUntil();

        $citizenId = CitizenId::create([
            'citizen_id'   => $citizen->id,
            'generated_by' => auth()->id(),
            'valid_until'  => $validUntil->toDateString(),
        ]);

        // Generating an ID auto-releases it — flag the citizen and log the event.
        if (! $citizen->is_id_release) {
            $citizen->update(['is_id_release' => 1]);
            \App\Models\ActivityLog::record(
                'released',
                'citizen_id',
                $citizen->id,
                "Barangay ID released for {$citizen->full_name}.",
                ['id_no' => \App\Models\Setting::instance()->formatCitizenId($citizen->id)]
            );
        }

        return response()->json(['id' => $citizenId->id]);
    }

    public function print(CitizenId $citizenId)
    {
        $citizenId->load('citizen.addressZone', 'citizen.tags', 'generatedBy');

        $tpl = \App\Models\CitizenIdTemplate::first();

        $templates = [
            'front' => $tpl,
            'back'  => $tpl,
        ];

        return view('citizens.ids.print', compact('citizenId', 'templates'));
    }

    public function uploadSignature(Request $request, CitizenId $citizenId)
    {
        $request->validate([
            'signature' => 'required|image|mimes:jpeg,png,jpg|max:2048',
        ]);

        if ($citizenId->sig_front) {
            Storage::delete($citizenId->sig_front);
        }

        $path = $request->file('signature')->store('public/citizen-signatures');
        $citizenId->update(['sig_front' => $path]);

        return response()->json([
            'url' => asset(str_replace('public/', 'storage/', $path)),
        ]);
    }

    public function removeSignature(Request $request, CitizenId $citizenId)
    {
        if ($citizenId->sig_front) {
            Storage::delete($citizenId->sig_front);
            $citizenId->update(['sig_front' => null]);
        }

        return response()->json(['ok' => true]);
    }
}
