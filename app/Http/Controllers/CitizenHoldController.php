<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
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
            'reason' => 'required|string|max:1000',
        ], [
            'reason.required' => 'Please give the reason for the hold.',
        ]);

        if ($citizen->activeHold()->exists()) {
            return back()->with('error', 'This citizen is already on hold.');
        }

        CitizenHold::create([
            'citizen_id' => $citizen->id,
            'reason'     => trim($data['reason']),
            'placed_by'  => auth()->id(),
            'placed_at'  => now(),
        ]);

        ActivityLog::record(
            'hold_placed', 'citizen', $citizen->id,
            "Placed a hold on {$citizen->full_name}",
            ['reason' => trim($data['reason'])]
        );

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

        return back()->with('success', 'Hold lifted. This citizen can be issued documents again.');
    }
}
