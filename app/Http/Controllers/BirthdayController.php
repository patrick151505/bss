<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\Citizen;
use App\Models\Tag;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class BirthdayController extends Controller
{
    /** Celebrants per page in the list. */
    public const PER_PAGE = 50;

    /** Faces shown per day in the Today / Next 7 days cards (the rest is "+N more"). */
    public const FACES_PER_DAY = 8;

    /** Fields pre-selected in the export modal (and used when none are sent). */
    public const DEFAULT_EXPORT_FIELDS = ['citizen_id', 'fullname', 'birthday', 'age', 'full_address', 'contact'];

    public function index(Request $request)
    {
        [$period, $months, $periodLabel] = $this->resolvePeriod($request->get('month'));
        // Optional day within a single month (e.g. April 20).
        $day = $this->resolveDay($request->get('day'), $months);
        if ($day) {
            $periodLabel .= ' ' . $day;
        }
        // Defaults to voters only, like the Citizens list. The filter form
        // always sends `voters` (empty string for "All"), so only a first,
        // unfiltered visit gets the default.
        $voters      = $request->has('voters') ? (string) $request->get('voters') : '1';
        $is_pwd      = (string) $request->get('is_pwd', '');
        $senior      = (string) $request->get('senior', '');
        $soloparents = (string) $request->get('is_soloparents', '');
        $tags        = $this->tagIds($request->get('tags'));

        $filters  = [$voters, $is_pwd, $senior, $soloparents, $tags];
        // Large barangays have thousands of celebrants a month: page the list, count in SQL.
        // bday_month / bday_day are indexed columns generated from bday, so
        // these queries no longer read the whole citizen table.
        $citizens = $this->buildQuery($months, ...$filters)->when($day, fn($q) => $q->where('eb_citizen.bday_day', $day))
            ->paginate(self::PER_PAGE)->withQueryString();

        // Celebrants per day of the selected month, for the day picker.
        $dayCounts = count($months) === 1
            ? $this->filteredQuery(...$filters)->where('eb_citizen.bday_month', $months[0])
                ->selectRaw('bday_day d, COUNT(*) n')->groupBy('bday_day')
                ->pluck('n', 'd')->map(fn($n) => (int) $n)->all()
            : [];

        $todayDay   = now()->day;
        $todayMonth = now()->month;
        // True when the selected month/quarter contains today.
        $isCurrentMonth = in_array($todayMonth, $months, true);

        // Celebrants per month (same filters) for the month strip.
        $monthCounts = $this->filteredQuery(...$filters)
            ->selectRaw('bday_month m, COUNT(*) n')->whereNotNull('eb_citizen.bday_month')->groupBy('bday_month')
            ->pluck('n', 'm')->map(fn($n) => (int) $n)->all();

        // Today and the next 7 days, whatever period is selected (same filters),
        // fetched in one query and split by date.
        $all       = $this->onDays(collect(range(0, 7))->map(fn($d) => now()->addDays($d))->all(), ...$filters);
        $isToday   = fn($c) => $c->bday->month === $todayMonth && $c->bday->day === $todayDay;
        $todayList = $all->filter($isToday)->values();
        $upcoming  = $all->reject($isToday)
            ->sortBy(fn($c) => $this->nextBirthday($c)->timestamp)->values();

        // Male / female / milestone counts for the period in a single pass.
        $agg = $this->filteredQuery(...$filters)->whereIn('eb_citizen.bday_month', $months)
            ->when($day, fn($q) => $q->where('eb_citizen.bday_day', $day))
            ->selectRaw('SUM(gender = 1) male, SUM(gender = 0) female, SUM(YEAR(CURDATE()) - YEAR(bday) IN ('
                . implode(',', array_keys(self::MILESTONES)) . ')) milestones')
            ->toBase()->first();

        $stats = [
            'total'      => $citizens->total(),
            'today'      => $todayList->count(),
            'upcoming'   => $upcoming->count(),
            'male'       => (int) ($agg->male ?? 0),
            'female'     => (int) ($agg->female ?? 0),
            'milestones' => (int) ($agg->milestones ?? 0),
        ];

        return view('birthdays.index', [
            'monthCounts'    => $monthCounts,
            'todayList'      => $todayList,
            'upcoming'       => $upcoming,
            'citizens'       => $citizens,
            'stats'          => $stats,
            'month'          => $period,
            'day'            => $day,
            'dayCounts'      => $dayCounts,
            'daysInMonth'    => count($months) === 1 ? self::daysIn($months[0]) : 0,
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
        $day = $this->resolveDay($request->input('day'), $months);
        if ($day) {
            $periodLabel .= ' ' . $day;
        }
        $voters      = (string) $request->input('voters', '');
        $is_pwd      = (string) $request->input('is_pwd', '');
        $senior      = (string) $request->input('senior', '');
        $soloparents = (string) $request->input('is_soloparents', '');
        $tags        = $this->tagIds($request->input('tags'));

        $query     = $this->buildQuery($months, $voters, $is_pwd, $senior, $soloparents, $tags)
            ->when($day, fn($q) => $q->where('eb_citizen.bday_day', $day));
        $format    = $request->input('format', 'csv');
        $monthName = $periodLabel;
        $filename  = 'birthdays_' . strtolower(str_replace(' ', '-', count($months) > 1 ? $period : $periodLabel)) . '_' . date('Y');

        $exportCount = $query->count();
        ActivityLog::record(
            'exported', 'export', null,
            "Exported {$exportCount} birthday celebrants ({$monthName} " . date('Y') . ") as " . strtoupper($format),
            [
                'module'       => 'birthdays',
                'format'       => $format,
                'record_count' => $exportCount,
                'month'        => $period,
                'day'          => $day,
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

    /** Days in a month, counting Feb 29 so leap-day birthdays can be picked. */
    public static function daysIn(int $month): int
    {
        return \Carbon\Carbon::create(2024, $month, 1)->daysInMonth;
    }

    /**
     * The "day" filter: a day number valid for the single selected month,
     * or null (no day filter, or a quarter is selected).
     */
    private function resolveDay($value, array $months): ?int
    {
        $day = (int) $value;
        if (count($months) !== 1 || $day < 1 || $day > self::daysIn($months[0])) {
            return null;
        }
        return $day;
    }

    /**
     * Normalise the "tags" filter to a list of positive tag ids.
     */
    private function tagIds($value): array
    {
        return array_values(array_unique(array_filter(array_map('intval', (array) $value), fn($id) => $id > 0)));
    }

    /**
     * Ages worth a mention in the list:
     *  18 — adult, can register as a voter; 60 — senior citizen;
     *  80/85/90/95/100 — cash gift under the Expanded Centenarians Act (RA 11982).
     */
    public const MILESTONES = [
        18  => 'Turns 18 — can register as a voter',
        60  => 'Turns 60 — senior citizen',
        80  => 'Turns 80 — Centenarians Act cash gift',
        85  => 'Turns 85 — Centenarians Act cash gift',
        90  => 'Turns 90 — Centenarians Act cash gift',
        95  => 'Turns 95 — Centenarians Act cash gift',
        100 => 'Turns 100 — centenarian',
    ];

    /** The age a citizen turns on their birthday this year. */
    public static function turning(Citizen $c): ?int
    {
        return $c->bday ? (int) now()->year - (int) $c->bday->year : null;
    }

    /** Milestone note for this year's birthday, or null. */
    public static function milestone(Citizen $c): ?string
    {
        $t = self::turning($c);
        return $t !== null ? (self::MILESTONES[$t] ?? null) : null;
    }

    /** The citizen's next birthday on or after today (Feb 29 falls on Feb 28 in other years). */
    private function nextBirthday(Citizen $c): \Carbon\Carbon
    {
        $year = now()->year;
        $make = fn($y) => \Carbon\Carbon::create($y, $c->bday->month, min($c->bday->day, \Carbon\Carbon::create($y, $c->bday->month, 1)->daysInMonth))->startOfDay();
        $next = $make($year);
        return $next->lt(now()->startOfDay()) ? $make($year + 1) : $next;
    }

    /** Active citizens (same filters) whose birthday falls on any of these dates. */
    private function onDays(array $dates, string $voters, string $is_pwd, string $senior, string $soloparents, array $tags = [])
    {
        return $this->filteredQuery($voters, $is_pwd, $senior, $soloparents, $tags)
            ->with(['addressZone'])
            ->select(['id', 'fname', 'mname', 'lname', 'suffix', 'bday', 'profile', 'contact', 'address'])
            ->where(function ($q) use ($dates) {
                foreach ($dates as $d) {
                    $q->orWhere(fn($w) => $w->where('eb_citizen.bday_month', $d->month)->where('eb_citizen.bday_day', $d->day));
                }
            })
            ->orderBy('lname')->get();
    }

    private function buildQuery(array $months, string $voters, string $is_pwd, string $senior, string $soloparents, array $tags = [])
    {
        return $this->filteredQuery($voters, $is_pwd, $senior, $soloparents, $tags)
            ->with(['addressZone', 'civilStatus', 'tags'])
            ->whereIn('eb_citizen.bday_month', $months)
            ->orderBy('eb_citizen.bday_month')
            ->orderBy('eb_citizen.bday_day')
            ->orderBy('lname');
    }

    /** Active citizens with the page filters applied (no period, no ordering). */
    private function filteredQuery(string $voters, string $is_pwd, string $senior, string $soloparents, array $tags = [])
    {
        return Citizen::query()
            ->where('is_active', 1)
            ->when($voters !== '', fn($q) => $q->where('voters', $voters))
            ->when($is_pwd !== '', fn($q) => $q->where('is_pwd', $is_pwd))
            ->when($senior === '1', fn($q) => $q->whereRaw('TIMESTAMPDIFF(YEAR, bday, CURDATE()) >= 60'))
            ->when($senior === '0', fn($q) => $q->whereRaw('TIMESTAMPDIFF(YEAR, bday, CURDATE()) < 60'))
            ->when($soloparents !== '', fn($q) => $q->where('is_soloparents', $soloparents))
            // Matches citizens carrying ANY of the selected tags (same as the Citizens list).
            ->when($tags, fn($q) => $q->whereHas('tags', fn($t) => $t->whereIn('eb_tags.id', $tags)));
    }

    private function buildFullAddress(Citizen $c): string
    {
        return $c->full_address;
    }
}
