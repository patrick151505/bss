<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\Event;
use App\Models\EventWinner;
use App\Models\Setting;
use Illuminate\Http\Request;

/**
 * Raffle winners after the draw: edit prizes (one or in bulk), track
 * claiming, and export the list.
 */
class EventWinnerController extends Controller
{
    public function index(Request $request, Event $event)
    {
        $winners = $this->winnersQuery($event)->get();

        $stats = [
            'total'     => $winners->count(),
            'claimed'   => $winners->whereNotNull('claimed_at')->count(),
            'no_prize'  => $winners->filter(fn ($w) => blank($w->prize_label))->count(),
        ];

        // Prize names already used in this event — suggestions for the inputs.
        $prizeSuggestions = $winners->pluck('prize_label')->filter()->unique()->values();

        return view('events.winners', compact('event', 'winners', 'stats', 'prizeSuggestions'));
    }

    /** Edit one winner's prize (inline). */
    public function updatePrize(Request $request, Event $event, EventWinner $winner)
    {
        abort_unless($winner->eventId === $event->id, 404);

        $data = $request->validate(['prize_label' => 'nullable|string|max:200']);
        $old  = $winner->prize_label;
        $new  = filled($data['prize_label'] ?? null) ? trim($data['prize_label']) : null;

        $winner->update(['prize_label' => $new]);

        ActivityLog::record('updated', 'event', $event->id,
            "Raffle prize for {$winner->citizen?->full_name} (round #{$winner->round}) changed",
            ['from' => $old ?? '—', 'to' => $new ?? '—']
        );

        return response()->json(['success' => true, 'prize_label' => $new]);
    }

    /**
     * Bulk actions on selected winners:
     *   prize   — set the same prize on all of them (e.g. "5kg Bigas")
     *   claim   — mark prizes as claimed (now, by the current user)
     *   unclaim — clear the claim
     */
    public function bulk(Request $request, Event $event)
    {
        $data = $request->validate([
            'ids'         => 'required|array|min:1',
            'ids.*'       => 'integer',
            'action'      => 'required|in:prize,claim,unclaim',
            'prize_label' => 'nullable|required_if:action,prize|string|max:200',
        ], [
            'ids.required'            => 'Select at least one winner.',
            'prize_label.required_if' => 'Type the prize to set.',
        ]);

        // Only winners that belong to THIS event.
        $query = EventWinner::where('eventId', $event->id)->whereIn('id', $data['ids']);
        $count = (clone $query)->count();

        match ($data['action']) {
            'prize'   => $query->update(['prize_label' => trim($data['prize_label'])]),
            'claim'   => $query->whereNull('claimed_at')->update(['claimed_at' => now(), 'claimed_by' => auth()->id()]),
            'unclaim' => $query->update(['claimed_at' => null, 'claimed_by' => null]),
        };

        $label = match ($data['action']) {
            'prize'   => "Set prize \"" . trim($data['prize_label']) . "\" on {$count} raffle winner(s)",
            'claim'   => "Marked {$count} raffle prize(s) as claimed",
            'unclaim' => "Marked {$count} raffle prize(s) as unclaimed",
        };
        ActivityLog::record('updated', 'event', $event->id, $label, ['winner_ids' => $data['ids']]);

        return response()->json(['success' => true, 'message' => $label . '.', 'count' => $count]);
    }

    /** Export the winners list as CSV or PDF. */
    public function export(Request $request, Event $event)
    {
        $winners  = $this->winnersQuery($event)->get();
        $settings = Setting::instance();
        $format   = $request->input('format') === 'pdf' ? 'pdf' : 'csv';
        $filename = 'raffle_winners_' . \Illuminate\Support\Str::slug($event->title) . '_' . now()->format('Ymd');

        $fields = [
            'round'      => ['label' => 'Round',       'value' => fn ($w) => '#' . $w->round],
            'citizen_id' => ['label' => 'Citizen ID',  'value' => fn ($w) => $w->citizen ? $settings->formatCitizenId($w->citizen->id) : ''],
            'name'       => ['label' => 'Winner',      'value' => fn ($w) => $w->citizen?->full_name ?? '—'],
            'address'    => ['label' => 'Address',     'value' => fn ($w) => $w->citizen?->full_address ?: ($w->citizen?->addressZone?->description ?? '')],
            'contact'    => ['label' => 'Contact No',  'value' => fn ($w) => $w->citizen?->contact ?? ''],
            'prize'      => ['label' => 'Prize',       'value' => fn ($w) => $w->prize_label ?? ''],
            'drawn_at'   => ['label' => 'Drawn',       'value' => fn ($w) => $w->drawn_at?->format('M d, Y g:i A') ?? ''],
            'claimed'    => ['label' => 'Claimed',     'value' => fn ($w) => $w->claimed_at ? 'Yes — ' . $w->claimed_at->format('M d, Y g:i A') : 'No'],
            'signature'  => ['label' => 'Signature',   'value' => fn ($w) => ''],
        ];

        ActivityLog::record('exported', 'export', null,
            "Exported {$winners->count()} raffle winner(s) of \"{$event->title}\" as " . strtoupper($format),
            ['module' => 'events', 'event_id' => $event->id, 'format' => $format, 'record_count' => $winners->count()]
        );

        if ($format === 'pdf') {
            return \Barryvdh\DomPDF\Facade\Pdf::loadView('citizens.export_pdf', [
                'citizens' => $winners,     // the shared export view iterates these rows
                'fields'   => $fields,
                'title'    => 'Raffle Winners — ' . $event->title . ($event->event_date ? ' (' . $event->event_date->format('M d, Y') . ')' : ''),
            ])->setPaper('a4', 'landscape')->download($filename . '.pdf');
        }

        // CSV without the signature column (that one is for the printed list).
        unset($fields['signature']);

        return response()->streamDownload(function () use ($winners, $fields) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF");                          // UTF-8 BOM for Excel
            fputcsv($out, array_column($fields, 'label'));
            foreach ($winners as $w) {
                fputcsv($out, array_map(fn ($f) => ($f['value'])($w), $fields));
            }
            fclose($out);
        }, $filename . '.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    private function winnersQuery(Event $event)
    {
        return EventWinner::with(['citizen.addressZone', 'claimedBy'])
            ->where('eventId', $event->id)
            ->orderBy('round');
    }
}
