<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\Citizen;
use App\Models\Tag;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class BirthdayController extends Controller
{
    /** Fields pre-selected in the export modal (and used when none are sent). */
    public const DEFAULT_EXPORT_FIELDS = ['citizen_id', 'fullname', 'birthday', 'age', 'full_address', 'contact'];

    public function index(Request $request)
    {
        [$period, $months, $periodLabel] = $this->resolvePeriod($request->get('month'));
        // Defaults to voters only, like the Citizens list. The filter form
        // always sends `voters` (empty string for "All"), so only a first,
        // unfiltered visit gets the default.
        $voters      = $request->has('voters') ? (string) $request->get('voters') : '1';
        $is_pwd      = (string) $request->get('is_pwd', '');
        $senior      = (string) $request->get('senior', '');
        $soloparents = (string) $request->get('is_soloparents', '');
        $tags        = $this->tagIds($request->get('tags'));

        $citizens = $this->buildQuery($months, $voters, $is_pwd, $senior, $soloparents, $tags)->get();

        $todayDay   = now()->day;
        $todayMonth = now()->month;
        // True when the selected month/quarter contains today.
        $isCurrentMonth = in_array($todayMonth, $months, true);

        $stats = [
            'total'   => $citizens->count(),
            'today'   => $isCurrentMonth
                            ? $citizens->filter(fn($c) => $c->bday && $c->bday->month === $todayMonth && $c->bday->day === $todayDay)->count()
                            : 0,
            'male'    => $citizens->where('gender', 1)->count(),
            'female'  => $citizens->where('gender', 0)->count(),
        ];

        return view('birthdays.index', [
            'citizens'       => $citizens,
            'stats'          => $stats,
            'month'          => $period,
            'periodLabel'    => $periodLabel,
            'isQuarter'      => count($months) > 1,
            'voters'         => $voters,
            'is_pwd'         => $is_pwd,
            'senior'         => $senior,
            'soloparents'    => $soloparents,
            'tags'           => $tags,
            'allTags'        => Tag::orderBy('name')->get(),
            'isCurrentMonth' => $isCurrentMonth,
            'todayDay'       => $todayDay,
            'todayMonth'     => $todayMonth,
            'exportFields'   => array_map(fn($f) => $f['label'], $this->exportFields()),
            'defaultExportFields' => self::DEFAULT_EXPORT_FIELDS,
        ]);
    }

    public function export(Request $request)
    {
        [$period, $months, $periodLabel] = $this->resolvePeriod($request->input('month'));
        $voters      = (string) $request->input('voters', '');
        $is_pwd      = (string) $request->input('is_pwd', '');
        $senior      = (string) $request->input('senior', '');
        $soloparents = (string) $request->input('is_soloparents', '');
        $tags        = $this->tagIds($request->input('tags'));

        $query     = $this->buildQuery($months, $voters, $is_pwd, $senior, $soloparents, $tags);
        $format    = $request->input('format', 'csv');
        $monthName = $periodLabel;
        $filename  = 'birthdays_' . strtolower(count($months) > 1 ? $period : $periodLabel) . '_' . date('Y');

        $exportCount = $query->count();
        ActivityLog::record(
            'exported', 'export', null,
            "Exported {$exportCount} birthday celebrants ({$monthName} " . date('Y') . ") as " . strtoupper($format),
            [
                'module'       => 'birthdays',
                'format'       => $format,
                'record_count' => $exportCount,
                'month'        => $period,
                'filters'      => array_filter(
                    ['voters' => $voters, 'is_pwd' => $is_pwd, 'senior' => $senior, 'is_soloparents' => $soloparents, 'tags' => $tags ? Tag::whereIn('id', $tags)->pluck('name')->implode(', ') : ''],
                    fn($v) => $v !== ''
                ),
            ]
        );

        // Only whitelisted keys are honoured; an empty selection falls back to
        // the default set so the export never comes out blank.
        $allFields = $this->exportFields();
        $fields    = array_intersect_key($allFields, array_flip((array) $request->input('fields', [])));
        if (empty($fields)) {
            $fields = array_intersect_key($allFields, array_flip(self::DEFAULT_EXPORT_FIELDS));
        }

        if ($format === 'pdf') {
            $citizens = $query->get();
            $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('citizens.export_pdf', [
                'citizens' => $citizens,
                'fields'   => $fields,
                'title'    => 'Birthday Celebrants — ' . $monthName . ' ' . date('Y'),
            ])->setPaper('a4', 'landscape');

            return $pdf->download($filename . '.pdf');
        }

        return response()->streamDownload(function () use ($query, $fields) {
            $handle = fopen('php://output', 'w');
            fwrite($handle, "\xEF\xBB\xBF");
            fputcsv($handle, array_column($fields, 'label'));

            $query->chunk(200, function ($citizens) use ($handle, $fields) {
                foreach ($citizens as $c) {
                    $row = [];
                    foreach ($fields as $fieldDef) {
                        $row[] = ($fieldDef['value'])($c);
                    }
                    fputcsv($handle, $row);
                }
            });

            fclose($handle);
        }, $filename . '.csv', [
            'Content-Type'  => 'text/csv; charset=UTF-8',
            'Cache-Control' => 'no-cache, no-store, must-revalidate',
            'Pragma'        => 'no-cache',
        ]);
    }

    /**
     * Every column the export can include, in display order.
     */
    private function exportFields(): array
    {
        return [
            'citizen_id'     => ['label' => 'Citizen ID',     'value' => fn($c) => \App\Models\Setting::instance()->formatCitizenId($c->id)],
            'fullname'       => ['label' => 'Full Name',      'value' => fn($c) => trim($c->lname . ', ' . $c->fname . ($c->mname ? ' ' . substr($c->mname, 0, 1) . '.' : '') . ($c->suffix ? ' ' . $c->suffix : ''))],
            'birthday'       => ['label' => 'Birthday',       'value' => fn($c) => $c->bday ? $c->bday->format('F d') : ''],
            'birth_year'     => ['label' => 'Birth Year',     'value' => fn($c) => $c->bday ? $c->bday->format('Y') : ''],
            'age'            => ['label' => 'Age',            'value' => fn($c) => $c->age ?? ''],
            'gender'         => ['label' => 'Gender',         'value' => fn($c) => $c->gender == 1 ? 'Male' : 'Female'],
            'civil_status'   => ['label' => 'Civil Status',   'value' => fn($c) => $c->civilStatus->description ?? ''],
            'occupation'     => ['label' => 'Occupation',     'value' => fn($c) => $c->occupation ?? ''],
            'zone'           => ['label' => 'Zone / Area',    'value' => fn($c) => $c->addressZone->description ?? ''],
            'full_address'   => ['label' => 'Full Address',   'value' => fn($c) => $this->buildFullAddress($c)],
            'contact'        => ['label' => 'Contact No',     'value' => fn($c) => $c->contact ?? ''],
            'email'          => ['label' => 'Email',          'value' => fn($c) => $c->email ?? ''],
            'voters'         => ['label' => 'Voter',          'value' => fn($c) => $c->voters ? 'Yes' : 'No'],
            'pricinct_no'    => ['label' => 'Precinct No',    'value' => fn($c) => $c->pricinct_no ?? ''],
            'senior'         => ['label' => 'Senior',         'value' => fn($c) => ($c->age ?? 0) >= 60 ? 'Yes' : 'No'],
            'is_pwd'         => ['label' => 'PWD',            'value' => fn($c) => $c->is_pwd ? 'Yes' : 'No'],
            'is_soloparents' => ['label' => 'Solo Parent',    'value' => fn($c) => $c->is_soloparents ? 'Yes' : 'No'],
            'tags'           => ['label' => 'Tags',           'value' => fn($c) => $c->tags->pluck('name')->implode(', ')],
            'ic_fullname'    => ['label' => 'Emergency Contact Name', 'value' => fn($c) => $c->ic_fullname ?? ''],
            'ic_contact'     => ['label' => 'Emergency Contact No',   'value' => fn($c) => $c->ic_contact ?? ''],
        ];
    }

    /**
     * Turn the "month" filter into [period key, months[], label].
     * Accepts a month number ("1"–"12") or a quarter ("q1"–"q4").
     * Anything else falls back to the current month.
     */
    private function resolvePeriod($value): array
    {
        $value = strtolower((string) $value);

        if (preg_match('/^q([1-4])$/', $value, $m)) {
            $q      = (int) $m[1];
            $months = range($q * 3 - 2, $q * 3);
            $label  = 'Q' . $q . ' (' . \Carbon\Carbon::create(null, $months[0], 1)->format('M')
                    . '–' . \Carbon\Carbon::create(null, $months[2], 1)->format('M') . ')';

            return ['q' . $q, $months, $label];
        }

        $month = (int) $value;
        if ($month < 1 || $month > 12) {
            $month = now()->month;
        }

        return [(string) $month, [$month], \Carbon\Carbon::create(null, $month, 1)->format('F')];
    }

    /**
     * Normalise the "tags" filter to a list of positive tag ids.
     */
    private function tagIds($value): array
    {
        return array_values(array_unique(array_filter(array_map('intval', (array) $value), fn($id) => $id > 0)));
    }

    private function buildQuery(array $months, string $voters, string $is_pwd, string $senior, string $soloparents, array $tags = [])
    {
        return Citizen::with(['addressZone', 'civilStatus', 'tags'])
            ->whereIn(DB::raw('MONTH(bday)'), $months)
            ->where('is_active', 1)
            ->when($voters !== '', fn($q) => $q->where('voters', $voters))
            ->when($is_pwd !== '', fn($q) => $q->where('is_pwd', $is_pwd))
            ->when($senior === '1', fn($q) => $q->whereRaw('TIMESTAMPDIFF(YEAR, bday, CURDATE()) >= 60'))
            ->when($senior === '0', fn($q) => $q->whereRaw('TIMESTAMPDIFF(YEAR, bday, CURDATE()) < 60'))
            ->when($soloparents !== '', fn($q) => $q->where('is_soloparents', $soloparents))
            // Matches citizens carrying ANY of the selected tags (same as the Citizens list).
            ->when($tags, fn($q) => $q->whereHas('tags', fn($t) => $t->whereIn('eb_tags.id', $tags)))
            ->orderByRaw('MONTH(bday)')
            ->orderByRaw('DAY(bday)')
            ->orderBy('lname');
    }

    private function buildFullAddress(Citizen $c): string
    {
        return $c->full_address;
    }
}
