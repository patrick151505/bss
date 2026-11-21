<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\Citizen;
use App\Models\CitizenUpdateRequest;
use App\Notifications\CitizenUpdateRequested;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Citizens → Detail Updates: changes residents asked for at the kiosk.
 * Approve copies them onto the citizen; reject keeps the record as it was.
 */
class CitizenUpdateRequestController extends Controller
{
    public function index(Request $request)
    {
        $status = $request->input('status', 'pending');

        $requests = CitizenUpdateRequest::with('citizen', 'kiosk', 'reviewer')
            ->when(array_key_exists($status, CitizenUpdateRequest::STATUSES), fn($q) => $q->where('status', $status))
            ->latest()
            ->paginate(20)
            ->withQueryString();

        $counts = CitizenUpdateRequest::select('status', DB::raw('count(*) as c'))->groupBy('status')->pluck('c', 'status');

        return view('citizens.updates.index', compact('requests', 'counts', 'status'));
    }

    public function approve(CitizenUpdateRequest $update)
    {
        abort_unless($update->status === 'pending', 422, 'This request was already reviewed.');
        $citizen = Citizen::withoutGlobalScopes()->findOrFail($update->citizen_id);

        // Log what really changes on the record now (it may have been edited since the request).
        $changed = [];
        foreach ($update->changes as $field => $c) {
            if (!array_key_exists($field, CitizenUpdateRequest::FIELDS)) continue;
            $now = $citizen->getAttribute($field);
            if ((string) $now !== (string) $c['to']) {
                $changed[$field] = ['from' => CitizenUpdateRequest::display($field, $now), 'to' => CitizenUpdateRequest::display($field, $c['to'])];
            }
            $citizen->setAttribute($field, $c['to'] === '' ? null : $c['to']);
        }
        if ((int) $citizen->voters !== 1) $citizen->pricinct_no = null;
        $citizen->date_last_updated = now();
        $citizen->save();

        $update->update(['status' => 'approved', 'reviewed_by' => auth()->id(), 'reviewed_at' => now()]);
        CitizenUpdateRequested::markHandled($update->id);

        ActivityLog::record('updated', 'citizen', $citizen->id,
            "{$citizen->lname}, {$citizen->fname}: details updated from a kiosk request (" . implode(', ', array_map(fn($f) => strtolower(CitizenUpdateRequest::FIELDS[$f]), array_keys($changed))) . ')',
            ['changed' => $changed, 'update_request_id' => $update->id]);

        return back()->with('success', "{$citizen->fname} {$citizen->lname}'s details were updated.");
    }

    public function reject(Request $request, CitizenUpdateRequest $update)
    {
        abort_unless($update->status === 'pending', 422, 'This request was already reviewed.');
        $data = $request->validate(['reject_reason' => 'required|string|min:3|max:500']);

        $update->update([
            'status'        => 'rejected',
            'reject_reason' => $data['reject_reason'],
            'reviewed_by'   => auth()->id(),
            'reviewed_at'   => now(),
        ]);
        CitizenUpdateRequested::markHandled($update->id);

        ActivityLog::record('rejected', 'citizen', $update->citizen_id,
            'Kiosk detail update rejected — ' . $data['reject_reason'], ['update_request_id' => $update->id]);

        return back()->with('success', 'Update request rejected. The record was not changed.');
    }
}
