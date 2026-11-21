<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\Blotter;
use App\Models\Citizen;
use App\Models\CitizenHold;
use Illuminate\Http\Request;

/**
 * Place / lift a hold on a citizen. While on hold, the citizen cannot be
 * issued documents (enforced in DocumentRequestController).
 */
class CitizenHoldController extends Controller
{
    public function store(Request $request, Citizen $citizen)
    {
        $data = $request->validate([
            'reason'     => 'required|string|max:1000',
            'blotter_id' => 'nullable|integer',
        ], [
            'reason.required' => 'Please give the reason for the hold.',
        ]);

        if ($citizen->activeHold()->exists()) {
            return back()->with('error', 'This citizen is already on hold.');
        }

        // A hold placed from a blotter case remembers the case — but only when
        // this citizen really is a party to it, so a forged id cannot attach
        // an unrelated case to someone's record.
        $blotter = null;
        if (! empty($data['blotter_id'])) {
            $blotter = Blotter::whereKey($data['blotter_id'])
                ->whereHas('parties', fn ($q) => $q->where('citizen_id', $citizen->id))
                ->first();
            if (! $blotter) {
                return back()->with('error', 'This citizen is not a party to that blotter case.');
            }
        }

        CitizenHold::create([
            'citizen_id' => $citizen->id,
            'reason'     => trim($data['reason']),
            'blotter_id' => $blotter?->id,
            'placed_by'  => auth()->id(),
            'placed_at'  => now(),
        ]);

        ActivityLog::record(
            'hold_placed', 'citizen', $citizen->id,
            "Placed a hold on {$citizen->full_name}",
            ['reason' => trim($data['reason']), 'blotter_no' => $blotter?->blotter_no]
        );

        if ($blotter) {
            ActivityLog::record(
                'hold_placed', 'blotter', $blotter->id,
                "Placed {$citizen->full_name} on hold (cannot be issued documents)",
                ['citizen_id' => $citizen->id, 'reason' => trim($data['reason'])]
            );
        }

        return back()->with('success', 'Hold placed. This citizen cannot be issued documents until the hold is lifted.');
    }

    public function destroy(Request $request, Citizen $citizen)
    {
        $data = $request->validate([
            'lift_note' => 'nullable|string|max:1000',
        ]);

        $hold = $citizen->activeHold;

        if (! $hold) {
            return back()->with('error', 'This citizen is not on hold.');
        }

        $hold->update([
            'lifted_by' => auth()->id(),
            'lifted_at' => now(),
            'lift_note' => isset($data['lift_note']) ? trim($data['lift_note']) : null,
        ]);

        ActivityLog::record(
            'hold_lifted', 'citizen', $citizen->id,
            "Lifted the hold on {$citizen->full_name}",
            ['reason' => $hold->reason, 'lift_note' => $hold->lift_note]
        );

        // Holds placed from a case also show their lifting in that case's history.
        if ($hold->blotter_id) {
            ActivityLog::record(
                'hold_lifted', 'blotter', $hold->blotter_id,
                "Lifted the hold on {$citizen->full_name}",
                ['citizen_id' => $citizen->id, 'lift_note' => $hold->lift_note]
            );
        }

        return back()->with('success', 'Hold lifted. This citizen can be issued documents again.');
    }
}
