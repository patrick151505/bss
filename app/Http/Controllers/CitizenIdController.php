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

        if ($request->date_start) {
            $query->whereDate('created_at', '>=', $request->date_start);
        }
        if ($request->date_end) {
            $query->whereDate('created_at', '<=', $request->date_end);
        }

        $perPage   = in_array($request->show, [5, 10, 20, 50, 100]) ? (int) $request->show : 10;
        $ids       = $query->paginate($perPage)->withQueryString();
        $addresses = Address::where('is_active', true)->orderBy('description')->get();

        return view('citizens.ids.index', compact('ids', 'addresses'));
    }

    public function store(Request $request)
    {
        $request->validate(['citizen_id' => 'required|integer']);

        $citizen   = Citizen::findOrFail($request->citizen_id);
        $citizenId = CitizenId::create([
            'citizen_id'   => $citizen->id,
            'generated_by' => auth()->id(),
            'valid_until'  => now()->addYears(2)->toDateString(),
        ]);

        return response()->json(['id' => $citizenId->id]);
    }

    public function print(CitizenId $citizenId)
    {
        $citizenId->load('citizen.addressZone', 'generatedBy');

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
