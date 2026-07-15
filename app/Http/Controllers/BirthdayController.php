<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\Citizen;
use Illuminate\Http\Request;

class BirthdayController extends Controller
{
    public function index(Request $request)
    {
        $month       = (int) $request->get('month', now()->month);
        $is_pwd      = $request->get('is_pwd', '');
        $senior      = $request->get('senior', '');
        $soloparents = $request->get('is_soloparents', '');

        $citizens = $this->buildQuery($month, $is_pwd, $senior, $soloparents)->get();

        $todayDay = now()->day;
        $isCurrentMonth = ($month === now()->month);

        $stats = [
            'total'   => $citizens->count(),
            'today'   => $isCurrentMonth
                            ? $citizens->filter(fn($c) => $c->bday && $c->bday->day === $todayDay)->count()
                            : 0,
            'male'    => $citizens->where('gender', 1)->count(),
            'female'  => $citizens->where('gender', 0)->count(),
        ];

        return view('birthdays.index', [
            'citizens'       => $citizens,
            'stats'          => $stats,
            'month'          => $month,
            'is_pwd'         => $is_pwd,
            'senior'         => $senior,
            'soloparents'    => $soloparents,
            'isCurrentMonth' => $isCurrentMonth,
            'todayDay'       => $todayDay,
        ]);
    }

    public function export(Request $request)
    {
        $month       = (int) $request->input('month', now()->month);
        $is_pwd      = $request->input('is_pwd', '');
        $senior      = $request->input('senior', '');
        $soloparents = $request->input('is_soloparents', '');

        $query     = $this->buildQuery($month, $is_pwd, $senior, $soloparents);
        $format    = $request->input('format', 'csv');
        $monthName = \Carbon\Carbon::create()->month($month)->format('F');
        $filename  = 'birthdays_' . strtolower($monthName) . '_' . date('Y');

        $exportCount = $query->count();
        ActivityLog::record(
            'exported', 'export', null,
            "Exported {$exportCount} birthday celebrants ({$monthName} " . date('Y') . ") as " . strtoupper($format),
            [
                'module'       => 'birthdays',
                'format'       => $format,
                'record_count' => $exportCount,
                'month'        => $month,
                'filters'      => array_filter(
                    ['is_pwd' => $is_pwd, 'senior' => $senior, 'is_soloparents' => $soloparents],
                    fn($v) => $v !== ''
                ),
            ]
        );

        $fields = [
            'citizen_id'   => ['label' => 'Citizen ID',   'value' => fn($c) => \App\Models\Setting::instance()->formatCitizenId($c->id)],
            'fullname'     => ['label' => 'Full Name',     'value' => fn($c) => trim($c->lname . ', ' . $c->fname . ($c->mname ? ' ' . substr($c->mname, 0, 1) . '.' : '') . ($c->suffix ? ' ' . $c->suffix : ''))],
            'birthday'     => ['label' => 'Birthday',      'value' => fn($c) => $c->bday ? $c->bday->format('F d') : ''],
            'age'          => ['label' => 'Age',           'value' => fn($c) => $c->age ?? ''],
            'full_address' => ['label' => 'Full Address',  'value' => fn($c) => $this->buildFullAddress($c)],
            'contact'      => ['label' => 'Contact No',    'value' => fn($c) => $c->contact ?? ''],
        ];

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

    private function buildQuery(int $month, string $is_pwd, string $senior, string $soloparents)
    {
        return Citizen::with(['addressZone'])
            ->whereMonth('bday', $month)
            ->where('is_active', 1)
            ->when($is_pwd !== '', fn($q) => $q->where('is_pwd', $is_pwd))
            ->when($senior === '1', fn($q) => $q->whereRaw('TIMESTAMPDIFF(YEAR, bday, CURDATE()) >= 60'))
            ->when($senior === '0', fn($q) => $q->whereRaw('TIMESTAMPDIFF(YEAR, bday, CURDATE()) < 60'))
            ->when($soloparents !== '', fn($q) => $q->where('is_soloparents', $soloparents))
            ->orderByRaw('DAY(bday)')
            ->orderBy('lname');
    }

    private function buildFullAddress(Citizen $c): string
    {
        $parts = [];

        if ($c->addressZone) {
            if ($c->addressZone->is_subd == 1) {
                if ($c->blk)    $parts[] = 'Blk ' . $c->blk;
                if ($c->lot)    $parts[] = 'Lot ' . $c->lot;
                if ($c->phase)  $parts[] = 'Ph '  . $c->phase;
                if ($c->street) $parts[] = $c->street;
            } elseif ($c->addressZone->is_subd == 2) {
                if ($c->complete_address) $parts[] = $c->complete_address;
            } else {
                if ($c->no)     $parts[] = $c->no;
                if ($c->street) $parts[] = $c->street;
            }
            $parts[] = $c->addressZone->description;
        }

        return implode(', ', array_filter($parts));
    }
}
