<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\Address;
use App\Models\ApprovalStatus;
use App\Models\Citizen;
use App\Models\CivilStatus;
use App\Models\Family;
use App\Models\Household;
use App\Models\Relation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CitizenController extends Controller
{
    public function index(Request $request)
    {
        $filters = $request->only([
            'search', 'gender', 'civil_status', 'address',
            'approval_status', 'age_min', 'age_max',
            'voters', 'is_pwd', 'is_soloparents', 'senior',
            'household_search', 'hh_role', 'hh_assigned', 'tags', 'income_class',
        ]);

        // The list defaults to voters only. A request that has been through
        // the filter form always carries `voters` (even as an empty string
        // for "All"), so only a first, unfiltered visit gets the default.
        if (! $request->has('voters')) {
            $filters['voters'] = '1';
        }

        $query = Citizen::with(['civilStatus', 'addressZone', 'approvalStatus', 'family.household', 'tags', 'activeHold'])
            // The list only ever shows active citizens; there is no filter for
            // this, so archived records never surface here.
            ->where('is_active', 1)
            ->when($filters['search'] ?? null, fn($q, $search) => $q->searchName($search))
            ->when($filters['gender'] ?? null, fn($q, $v) => $q->where('gender', $v))
            ->when($filters['civil_status'] ?? null, fn($q, $v) => $q->where('civil_status', $v))
            ->when($filters['address'] ?? null, fn($q, $v) => $q->where('address', $v))
            ->when($filters['approval_status'] ?? null, fn($q, $v) => $q->where('approval_status', $v))
            // "Non-Voter" is the value 0, which a truthy check would drop,
            // so test for presence instead.
            ->when(($filters['voters'] ?? '') !== '', fn($q) => $q->where('voters', $filters['voters']))
            ->when($filters['is_pwd'] ?? null, fn($q, $v) => $q->where('is_pwd', $v))
            ->when($filters['is_soloparents'] ?? null, fn($q, $v) => $q->where('is_soloparents', $v))
            ->when($filters['age_min'] ?? null, function ($q, $v) {
                $q->whereRaw('TIMESTAMPDIFF(YEAR, bday, CURDATE()) >= ?', [$v]);
            })
            ->when($filters['age_max'] ?? null, function ($q, $v) {
                $q->whereRaw('TIMESTAMPDIFF(YEAR, bday, CURDATE()) <= ?', [$v]);
            })
            ->when(($filters['senior'] ?? null) !== null && $filters['senior'] !== '', function ($q) use ($filters) {
                if ($filters['senior'] == '1') {
                    $q->whereRaw('TIMESTAMPDIFF(YEAR, bday, CURDATE()) >= 60');
                } else {
                    $q->whereRaw('TIMESTAMPDIFF(YEAR, bday, CURDATE()) < 60');
                }
            })
            ->when(($filters['hh_assigned'] ?? '') !== '', function ($q) use ($filters) {
                if ($filters['hh_assigned'] == '1') {
                    $q->whereNotNull('familyId');
                } else {
                    $q->whereNull('familyId');
                }
            })
            ->when(($filters['hh_role'] ?? '') !== '', fn($q) => $q->where('isHead', $filters['hh_role']))
            // Optional income classification (only when switched on and allowed)
            ->when(($filters['income_class'] ?? '') !== '' && \App\Support\IncomeClass::visibleTo(auth()->user()),
                fn($q) => \App\Support\IncomeClass::applyCitizenFilter($q, $filters['income_class']))
            ->when(!empty($filters['tags']), function ($q) use ($filters) {
                $tagIds = array_filter((array) $filters['tags']);
                if ($tagIds) {
                    $q->whereHas('tags', fn($q) => $q->whereIn('eb_tags.id', $tagIds));
                }
            })
            ->when(($filters['household_search'] ?? '') !== '', function ($q) use ($filters) {
                $term = $filters['household_search'];
                $q->whereHas('family.household', function ($q) use ($term) {
                    $q->where('no', 'like', "%$term%")
                      ->orWhere('phaseStreet', 'like', "%$term%")
                      ->orWhere('completeAdress', 'like', "%$term%");
                });
            })
            ->when(true, function ($q) use ($request) {
                // Only whitelisted columns are sortable, so the value can go
                // straight into orderBy without risking injection.
                $sortable = ['name', 'date_created', 'date_last_updated'];
                $sort     = $request->get('sort');
                $dir      = strtolower($request->get('dir')) === 'asc' ? 'asc' : 'desc';

                // "name" is a virtual column: surname, then first name.
                if ($sort === 'name') {
                    $q->orderBy('lname', $dir)->orderBy('fname', $dir);
                    return;
                }

                if (in_array($sort, $sortable, true)) {
                    $q->orderBy($sort, $dir);
                    return;
                }

                $q->orderBy('lname')->orderBy('fname');
            });

        $perPage = (int) $request->get('per_page', 15);
        $citizens = $query->paginate(in_array($perPage, [15, 25, 50, 100]) ? $perPage : 15)
            ->withQueryString();

        $sort = in_array($request->get('sort'), ['name', 'date_created', 'date_last_updated'], true)
            ? $request->get('sort')
            : null;
        $dir  = strtolower($request->get('dir')) === 'asc' ? 'asc' : 'desc';

        if ($request->ajax()) {
            return response()->json([
                'html'  => view('citizens._table', compact('citizens', 'sort', 'dir'))->render(),
                'total' => $citizens->total(),
            ]);
        }

        return view('citizens.index', [
            'citizens'        => $citizens,
            'filters'         => $filters,
            'sort'            => $sort,
            'dir'             => $dir,
            'civilStatuses'   => CivilStatus::orderBy('description')->get(),
            'addresses'       => Address::where('is_active', true)->orderBy('description')->get(),
            'approvalStatuses'=> ApprovalStatus::orderBy('id')->get(),
            'allTags'         => \App\Models\Tag::orderBy('name')->get(),
        ]);
    }

    public function export(Request $request)
    {
        $filters = $request->only([
            'search', 'gender', 'civil_status', 'address',
            'approval_status', 'age_min', 'age_max',
            'voters', 'is_pwd', 'is_soloparents', 'senior', 'income_class',
        ]);

        $query = Citizen::with(['civilStatus', 'addressZone', 'approvalStatus'])
            // Mirrors the list: exports cover active citizens only.
            ->where('is_active', 1)
            ->when($filters['search'] ?? null, fn($q, $search) => $q->searchName($search))
            ->when($filters['gender'] ?? null, fn($q, $v) => $q->where('gender', $v))
            ->when($filters['civil_status'] ?? null, fn($q, $v) => $q->where('civil_status', $v))
            ->when($filters['address'] ?? null, fn($q, $v) => $q->where('address', $v))
            ->when($filters['approval_status'] ?? null, fn($q, $v) => $q->where('approval_status', $v))
            ->when($filters['voters'] ?? null, fn($q, $v) => $q->where('voters', $v))
            ->when($filters['is_pwd'] ?? null, fn($q, $v) => $q->where('is_pwd', $v))
            ->when($filters['is_soloparents'] ?? null, fn($q, $v) => $q->where('is_soloparents', $v))
            ->when($filters['age_min'] ?? null, function ($q, $v) {
                $q->whereRaw('TIMESTAMPDIFF(YEAR, bday, CURDATE()) >= ?', [$v]);
            })
            ->when($filters['age_max'] ?? null, function ($q, $v) {
                $q->whereRaw('TIMESTAMPDIFF(YEAR, bday, CURDATE()) <= ?', [$v]);
            })
            ->when(($filters['senior'] ?? null) !== null && $filters['senior'] !== '', function ($q) use ($filters) {
                if ($filters['senior'] == '1') {
                    $q->whereRaw('TIMESTAMPDIFF(YEAR, bday, CURDATE()) >= 60');
                } else {
                    $q->whereRaw('TIMESTAMPDIFF(YEAR, bday, CURDATE()) < 60');
                }
            })
            ->when(($filters['income_class'] ?? '') !== '' && \App\Support\IncomeClass::visibleTo(auth()->user()),
                fn($q) => \App\Support\IncomeClass::applyCitizenFilter($q, $filters['income_class']))
            ->orderBy('lname')
            ->orderBy('fname');

        $format      = $request->input('format', 'csv');
        $selectedKeys = $request->input('fields', []);

        $allFields = [
            'citizen_id'        => ['label' => 'Citizen ID',        'value' => fn($c) => \App\Models\Setting::instance()->formatCitizenId($c->id)],
            'lname'             => ['label' => 'Last Name',          'value' => fn($c) => $c->lname ?? ''],
            'fname'             => ['label' => 'First Name',         'value' => fn($c) => $c->fname ?? ''],
            'mname'             => ['label' => 'Middle Name',        'value' => fn($c) => $c->mname ?? ''],
            'suffix'            => ['label' => 'Suffix',             'value' => fn($c) => $c->suffix ?? ''],
            'gender'            => ['label' => 'Gender',             'value' => fn($c) => $c->gender == 1 ? 'Male' : 'Female'],
            'civil_status'      => ['label' => 'Civil Status',       'value' => fn($c) => $c->civilStatus->description ?? ''],
            'bday'              => ['label' => 'Birthday',           'value' => fn($c) => $c->bday ? $c->bday->format('Y-m-d') : ''],
            'age'               => ['label' => 'Age',                'value' => fn($c) => $c->age ?? ''],
            'bplace'            => ['label' => 'Place of Birth',     'value' => fn($c) => $c->bplace ?? ''],
            'citizenship'       => ['label' => 'Citizenship',        'value' => fn($c) => $c->citizenship ?? ''],
            'occupation'        => ['label' => 'Occupation',         'value' => fn($c) => $c->occupation ?? ''],
            'zone'              => ['label' => 'Zone / Area',        'value' => fn($c) => $c->addressZone->description ?? ''],
            'full_address'      => ['label' => 'Full Address',       'value' => fn($c) => $this->buildFullAddress($c)],
            'year_stay'         => ['label' => 'Year of Stay',       'value' => fn($c) => $c->year_stay ? $c->year_stay->format('Y-m') : ''],
            'owner_status'      => ['label' => 'Owner Status',       'value' => fn($c) => $c->owner_status == 1 ? 'Owner' : 'Renter / Others'],
            'contact'           => ['label' => 'Contact No',         'value' => fn($c) => $c->contact ?? ''],
            'email'             => ['label' => 'Email',              'value' => fn($c) => $c->email ?? ''],
            'approval_status'   => ['label' => 'Approval Status',    'value' => fn($c) => $c->approvalStatus->description ?? ''],
            'is_active'         => ['label' => 'Active',             'value' => fn($c) => $c->is_active ? 'Active' : 'Inactive'],
            'is_pwd'            => ['label' => 'PWD',                'value' => fn($c) => $c->is_pwd ? 'Yes' : 'No'],
            'is_soloparents'    => ['label' => 'Solo Parent',        'value' => fn($c) => $c->is_soloparents ? 'Yes' : 'No'],
            'voters'            => ['label' => 'Voter',              'value' => fn($c) => $c->voters ? 'Yes' : 'No'],
            'pricinct_no'       => ['label' => 'Precinct No',        'value' => fn($c) => $c->pricinct_no ?? ''],
            'is_id_release'     => ['label' => 'ID Released',        'value' => fn($c) => $c->is_id_release ? 'Yes' : 'No'],
            'is_verify'         => ['label' => 'Verified',           'value' => fn($c) => $c->is_verify ? 'Yes' : 'No'],
            'ic_fullname'       => ['label' => 'ICE Full Name',      'value' => fn($c) => $c->ic_fullname ?? ''],
            'ic_relationship'   => ['label' => 'ICE Relationship',   'value' => fn($c) => $c->ic_relationship ?? ''],
            'ic_contact'        => ['label' => 'ICE Contact',        'value' => fn($c) => $c->ic_contact ?? ''],
            'ic_email'          => ['label' => 'ICE Email',          'value' => fn($c) => $c->ic_email ?? ''],
            'ic_address'        => ['label' => 'ICE Address',        'value' => fn($c) => $c->ic_address ?? ''],
            'note'              => ['label' => 'Notes',              'value' => fn($c) => $c->note ?? ''],
            'date_created'      => ['label' => 'Date Created',       'value' => fn($c) => $c->date_created ? $c->date_created->format('Y-m-d') : ''],
            'date_last_updated' => ['label' => 'Date Last Updated',  'value' => fn($c) => $c->date_last_updated ? $c->date_last_updated->format('Y-m-d') : ''],
        ];

        $orderedFields = [];
        foreach ($selectedKeys as $key) {
            if (isset($allFields[$key])) {
                $orderedFields[$key] = $allFields[$key];
            }
        }

        if (empty($orderedFields)) {
            foreach (['citizen_id', 'lname', 'fname', 'gender', 'zone', 'contact', 'approval_status'] as $key) {
                $orderedFields[$key] = $allFields[$key];
            }
        }

        $filename    = 'citizens_' . date('Ymd_His');
        $exportCount = $query->count();

        $activeFilters = array_filter($filters, fn($v) => $v !== '' && $v !== null);

        ActivityLog::record(
            'exported', 'export', null,
            "Exported {$exportCount} citizen records as " . strtoupper($format),
            [
                'module'          => 'citizens',
                'format'          => $format,
                'record_count'    => $exportCount,
                'fields_selected' => count($orderedFields),
                'filters'         => $activeFilters,
            ]
        );

        if ($format === 'pdf') {
            $citizens = $query->get();
            $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('citizens.export_pdf', [
                'citizens' => $citizens,
                'fields'   => $orderedFields,
            ])->setPaper('a4', 'landscape');

            return $pdf->download($filename . '.pdf');
        }

        return response()->streamDownload(function () use ($query, $orderedFields) {
            $handle = fopen('php://output', 'w');
            fwrite($handle, "\xEF\xBB\xBF");
            fputcsv($handle, array_column($orderedFields, 'label'));

            $query->chunk(200, function ($citizens) use ($handle, $orderedFields) {
                foreach ($citizens as $c) {
                    $row = [];
                    foreach ($orderedFields as $fieldDef) {
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

    private function buildFullAddress(Citizen $c): string
    {
        return $c->full_address;
    }

    public function create()
    {
        return view('citizens.create', [
            'civilStatuses'   => CivilStatus::orderBy('description')->get(),
            'addresses'       => Address::where('is_active', true)->orderBy('description')->get(),
            'approvalStatuses'=> ApprovalStatus::orderBy('id')->get(),
            'relations'       => Relation::orderBy('description')->get(),
            'allTags'         => \App\Models\Tag::orderBy('name')->get(),
        ]);
    }

    public function store(Request $request)
    {
        $rules = [
            'fname'           => 'required|max:250',
            'lname'           => 'required|max:250',
            'mname'           => 'nullable|max:250',
            'suffix'          => 'nullable|max:50',
            'gender'          => 'required|in:0,1',
            'civil_status'    => 'required|exists:eb_civil,id',
            'bday'            => 'required|date|before:today',
            'bplace'          => 'required|max:250',
            'citizenship'     => 'required|max:250',
            'occupation'      => 'nullable|max:250',
            'address'         => 'required|exists:eb_address,id',
            'year_stay'       => 'required|date',
            'owner_status'    => 'required|in:0,1',
            'email'           => 'nullable|email|max:250',
            'approval_status' => (auth()->user()->can('citizens.approve') ? 'required' : 'nullable') . '|exists:eb_approval_status,id',
            'is_active'       => 'required|in:0,1',
            'is_pwd'          => 'required|in:0,1',
            'is_soloparents'  => 'required|in:0,1',
            'voters'          => 'required|in:0,1',
            'is_verify'       => 'nullable|in:0,1',
            'ic_fullname'     => 'nullable|max:250',
            'ic_relationship' => 'nullable|max:250',
            'ic_address'      => 'nullable|max:500',
            'ic_contact'      => 'nullable|max:11',
            'ic_email'        => 'nullable|email|max:250',
            'note'            => 'nullable|max:1000',
            'profile'         => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
        ];

        if ($request->voters == 1) {
            $rules['pricinct_no'] = 'required|min:5|max:6';
        }

        $rules['contact'] = [
            'nullable',
            function ($attr, $value, $fail) {
                if ($value && (substr($value, 0, 2) !== '09' || strlen($value) !== 11)) {
                    $fail('Contact No. must be 11 digits starting with 09.');
                }
            },
        ];

        $rules['ic_contact'] = [
            'nullable',
            function ($attr, $value, $fail) {
                if ($value && (substr($value, 0, 2) !== '09' || strlen($value) !== 11)) {
                    $fail('Emergency contact no. must be 11 digits starting with 09.');
                }
            },
        ];

        $addressRecord = Address::find($request->address);
        if ($addressRecord && $addressRecord->rules_required) {
            foreach (explode(',', $addressRecord->rules_required) as $field) {
                $field = trim($field);
                if (!$field) continue;
                if ($field === 'street') {
                    $rules['street'] = 'required|max:250';
                } elseif ($field === 'phase') {
                    $rules['phase'] = 'required|numeric|min:1|max:99';
                } else {
                    $rules[$field] = 'required|numeric|min:1|max:9999';
                }
            }
        }

        $messages = [
            'pricinct_no.required' => 'Precinct No. is required for registered voters.',
            'pricinct_no.min'      => 'Precinct No. must be at least 5 characters (e.g. 0055F).',
            'pricinct_no.max'      => 'Precinct No. must not exceed 6 characters.',
            'bday.before'          => 'Birthday must be a past date.',
        ];

        // Duplicate check: same first name + last name + birthday
        $duplicate = Citizen::where('fname', $request->fname)
            ->where('lname', $request->lname)
            ->whereDate('bday', $request->bday)
            ->first();

        if ($duplicate) {
            return back()->withInput()->withErrors([
                'fname' => 'A citizen with the same name and birthday already exists (ID: ' . \App\Models\Setting::instance()->formatCitizenId($duplicate->id) . ').',
            ]);
        }

        $request->validate($rules, $messages);

        $qrcode = md5(\Illuminate\Support\Str::random(12) . date('Y-m-d H:i:s'));

        $profilePath = null;
        if ($request->hasFile('profile')) {
            $file     = $request->file('profile');
            $filename = $qrcode . '.' . $file->getClientOriginalExtension();
            $file->storeAs('public/citizen', $filename);
            $profilePath = 'public/citizen/' . $filename;
        }

        $citizen = Citizen::create([
            'fname'             => $request->fname,
            'mname'             => $request->mname,
            'lname'             => $request->lname,
            'suffix'            => $request->suffix,
            'gender'            => $request->gender,
            'civil_status'      => $request->civil_status,
            'bday'              => $request->bday,
            'bplace'            => $request->bplace,
            'citizenship'       => $request->citizenship,
            'occupation'        => $request->occupation,
            'address'           => $request->address,
            'no'                => $request->no,
            'street'            => $request->street,
            'blk'               => $request->blk,
            'lot'               => $request->lot,
            'phase'             => $request->phase,
            'complete_address'  => $request->complete_address,
            'year_stay'         => $request->year_stay . '-01',
            'owner_status'      => $request->owner_status,
            'contact'           => $request->contact,
            'email'             => $request->email,
            'approval_status'   => $this->approvalFor($request),
            'is_active'         => $request->is_active,
            'is_pwd'            => $request->is_pwd,
            'is_soloparents'    => $request->is_soloparents,
            'voters'            => $request->voters,
            'pricinct_no'       => $request->voters == 1 ? $request->pricinct_no : null,
            'is_verify'         => $request->is_verify,
            'ic_fullname'       => $request->ic_fullname,
            'ic_relationship'   => $request->ic_relationship,
            'ic_contact'        => $request->ic_contact,
            'ic_email'          => $request->ic_email,
            'ic_address'        => $request->ic_address,
            'note'              => $request->note,
            'profile'           => $profilePath,
            'qrcode'            => $qrcode,
            'date_created'      => now(),
            'date_last_updated' => now(),
        ]);

        ActivityLog::record(
            'created', 'citizen', $citizen->id,
            "{$citizen->lname}, {$citizen->fname} was registered",
            ['citizen_id' => \App\Models\Setting::instance()->formatCitizenId($citizen->id), 'qrcode' => $qrcode]
        );

        // ── Household assignment (optional) ───────────────────────────────────
        $hhRole = $request->input('hh_role'); // 'household_head' | 'family_head' | 'member'

        if ($hhRole === 'household_head') {
            $household = Household::create([
                'addressId'        => $citizen->address,
                'blk'              => $citizen->blk,
                'lot'              => $citizen->lot,
                'phaseStreet'      => $citizen->street ?? $citizen->phase,
                'no'               => $citizen->no,
                'completeAdress'   => $citizen->complete_address,
                'citizenHeadId'    => $citizen->id,
                'user_id'          => auth()->id(),
                'date_created'     => now(),
                'date_lastupdated' => now(),
            ]);
            $family = Family::create([
                'householdId'       => $household->id,
                'citizenId'         => $citizen->id,
                'date_created'      => now(),
                'date_last_updated' => now(),
            ]);
            $citizen->update(['familyId' => $family->id, 'isHead' => 2, 'date_last_updated' => now()]);

        } elseif ($hhRole === 'family_head') {
            $householdId = (int) $request->input('hh_household_id');
            if ($householdId) {
                $family = Family::create([
                    'householdId'       => $householdId,
                    'citizenId'         => $citizen->id,
                    'date_created'      => now(),
                    'date_last_updated' => now(),
                ]);
                $citizen->update(['familyId' => $family->id, 'isHead' => 1, 'date_last_updated' => now()]);
            }

        } elseif ($hhRole === 'member') {
            $familyId   = (int) $request->input('hh_family_id');
            $relationId = (int) $request->input('hh_relation_id');
            if ($familyId && $relationId) {
                $citizen->update([
                    'familyId'          => $familyId,
                    'isHead'            => 0,
                    'relationId'        => $relationId,
                    'date_last_updated' => now(),
                ]);
            }
        }

        $citizen->tags()->sync(
            array_filter(array_map('intval', (array) $request->input('tags', [])))
        );

        return redirect()->route('citizens.show', $citizen->id)
            ->with('success', 'Citizen record created successfully.');
    }

    public function show(Citizen $citizen)
    {
        $citizen->load(['civilStatus', 'addressZone', 'approvalStatus', 'approvedBy', 'relation', 'tags',
            'activeHold.placedBy', 'holds.placedBy', 'holds.liftedBy']);

        $encrypted = [
            'mname', 'bplace', 'contact', 'email', 'complete_address',
            'ic_email', 'ic_fullname', 'ic_contact', 'ic_address', 'ic_relationship',
        ];

        foreach ($encrypted as $field) {
            $citizen->$field = $this->safeDecrypt($citizen->$field);
        }

        $currentFamily    = $citizen->familyId ? Family::with(['household.address', 'head', 'members.relation'])->find($citizen->familyId) : null;
        $currentHousehold = $currentFamily?->household;

        if ($currentHousehold) {
            $currentHousehold->load(['householdHead', 'families.head', 'families.members.relation']);
        }

        // Profiling history
        $eventAttendances  = $citizen->eventAttendances()->with('event')->orderByDesc('time_in')->get();
        $eventWins         = $citizen->eventWins()->with('event')->orderByDesc('drawn_at')->get();
        $inventoryReleases = $citizen->inventoryReleases()->with(['items.item', 'releasedBy'])->orderByDesc('created_at')->get();
        $documentRequests  = $citizen->documentRequests()->with('documentType')->orderByDesc('created_at')->get();

        return view('citizens.show', compact(
            'citizen', 'currentFamily', 'currentHousehold',
            'eventAttendances', 'eventWins', 'inventoryReleases', 'documentRequests'
        ));
    }

    private function safeDecrypt(?string $value): ?string
    {
        if (empty($value)) return $value;
        try {
            return \Illuminate\Support\Facades\Crypt::decrypt($value);
        } catch (\Illuminate\Contracts\Encryption\DecryptException) {
            return $value;
        }
    }

    public function edit(Citizen $citizen)
    {
        $encrypted = [
            'mname', 'bplace', 'contact', 'email', 'complete_address',
            'ic_email', 'ic_fullname', 'ic_contact', 'ic_address', 'ic_relationship',
        ];

        foreach ($encrypted as $field) {
            $citizen->$field = $this->safeDecrypt($citizen->$field);
        }

        // Load current household assignment for the card
        $currentFamily    = $citizen->familyId ? Family::with(['household.address', 'head'])->find($citizen->familyId) : null;
        $currentHousehold = $currentFamily?->household;

        return view('citizens.edit', [
            'citizen'          => $citizen,
            'civilStatuses'    => CivilStatus::orderBy('description')->get(),
            'addresses'        => Address::where('is_active', true)->orderBy('description')->get(),
            'approvalStatuses' => ApprovalStatus::orderBy('id')->get(),
            'relations'        => Relation::orderBy('description')->get(),
            'currentFamily'    => $currentFamily,
            'currentHousehold' => $currentHousehold,
            'allTags'          => \App\Models\Tag::orderBy('name')->get(),
            'citizenTagIds'    => $citizen->tags()->pluck('eb_tags.id')->toArray(),
        ]);
    }

    public function update(Request $request, Citizen $citizen)
    {
        $rules = [
            'fname'           => 'required|max:250',
            'lname'           => 'required|max:250',
            'mname'           => 'nullable|max:250',
            'suffix'          => 'nullable|max:50',
            'gender'          => 'required|in:0,1',
            'civil_status'    => 'required|exists:eb_civil,id',
            'bday'            => 'required|date|before:today',
            'bplace'          => 'required|max:250',
            'citizenship'     => 'required|max:250',
            'occupation'      => 'nullable|max:250',
            'address'         => 'required|exists:eb_address,id',
            'year_stay'       => 'required|date',
            'owner_status'    => 'required|in:0,1',
            'email'           => 'nullable|email|max:250',
            'approval_status' => (auth()->user()->can('citizens.approve') ? 'required' : 'nullable') . '|exists:eb_approval_status,id',
            'is_active'       => 'required|in:0,1',
            'is_pwd'          => 'required|in:0,1',
            'is_soloparents'  => 'required|in:0,1',
            'voters'          => 'required|in:0,1',
            'is_verify'       => 'nullable|in:0,1',
            'ic_fullname'     => 'nullable|max:250',
            'ic_relationship' => 'nullable|max:250',
            'ic_address'      => 'nullable|max:500',
            'ic_contact'      => 'nullable|max:11',
            'ic_email'        => 'nullable|email|max:250',
            'note'            => 'nullable|max:1000',
            'profile'         => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
        ];

        // Voter requires precinct no.
        if ($request->voters == 1) {
            $rules['pricinct_no'] = 'required|min:5|max:6';
        }

        // Contact number format
        $rules['contact'] = [
            'nullable',
            function ($attr, $value, $fail) {
                if ($value && (substr($value, 0, 2) !== '09' || strlen($value) !== 11)) {
                    $fail('Contact No. must be 11 digits starting with 09.');
                }
            },
        ];

        $rules['ic_contact'] = [
            'nullable',
            function ($attr, $value, $fail) {
                if ($value && (substr($value, 0, 2) !== '09' || strlen($value) !== 11)) {
                    $fail('Emergency contact no. must be 11 digits starting with 09.');
                }
            },
        ];

        // Dynamic address sub-field rules from eb_address.rules_required
        $addressRecord = Address::find($request->address);
        if ($addressRecord && $addressRecord->rules_required) {
            foreach (explode(',', $addressRecord->rules_required) as $field) {
                $field = trim($field);
                if (!$field) continue;
                if ($field === 'street') {
                    $rules['street'] = 'required|max:250';
                } elseif ($field === 'phase') {
                    $rules['phase'] = 'required|numeric|min:1|max:99';
                } else {
                    $rules[$field] = 'required|numeric|min:1|max:9999';
                }
            }
        }

        $messages = [
            'pricinct_no.required' => 'Precinct No. is required for registered voters.',
            'pricinct_no.min'      => 'Precinct No. must be at least 5 characters (e.g. 0055F).',
            'pricinct_no.max'      => 'Precinct No. must not exceed 6 characters.',
            'bday.before'          => 'Birthday must be a past date.',
        ];

        $request->validate($rules, $messages);

        // Capture changed fields before saving
        $trackFields = [
            'fname', 'lname', 'mname', 'suffix', 'gender', 'civil_status',
            'bday', 'bplace', 'citizenship', 'occupation',
            'address', 'contact', 'email',
            'approval_status', 'is_active', 'is_pwd', 'is_soloparents',
            'voters', 'pricinct_no', 'is_verify',
        ];
        $changed = [];
        foreach ($trackFields as $field) {
            $reqVal = (string) ($request->input($field) ?? '');
            $curVal = $citizen->$field instanceof \Carbon\Carbon
                ? $citizen->$field->format('Y-m-d')
                : (string) ($citizen->$field ?? '');
            if ($reqVal !== $curVal) {
                $changed[$field] = ['from' => $curVal ?: '—', 'to' => $reqVal ?: '—'];
            }
        }

        // Profile photo upload
        $profilePath = $citizen->profile;
        if ($request->hasFile('profile')) {
            $file     = $request->file('profile');
            $filename = $citizen->qrcode . '.' . $file->getClientOriginalExtension();
            $file->storeAs('public/citizen', $filename);
            $profilePath = 'public/citizen/' . $filename;
        }

        $citizen->update([
            'fname'           => $request->fname,
            'mname'           => $request->mname,
            'lname'           => $request->lname,
            'suffix'          => $request->suffix,
            'gender'          => $request->gender,
            'civil_status'    => $request->civil_status,
            'bday'            => $request->bday,
            'bplace'          => $request->bplace,
            'citizenship'     => $request->citizenship,
            'occupation'      => $request->occupation,
            'address'         => $request->address,
            'no'              => $request->no,
            'street'          => $request->street,
            'blk'             => $request->blk,
            'lot'             => $request->lot,
            'phase'           => $request->phase,
            'complete_address'=> $request->complete_address,
            'year_stay'       => $request->year_stay . '-01',
            'owner_status'    => $request->owner_status,
            'contact'         => $request->contact,
            'email'           => $request->email,
            'approval_status' => $this->approvalFor($request, $citizen),
            'is_active'       => $request->is_active,
            'is_pwd'          => $request->is_pwd,
            'is_soloparents'  => $request->is_soloparents,
            'voters'          => $request->voters,
            'pricinct_no'     => $request->voters == 1 ? $request->pricinct_no : null,
            'is_verify'       => $request->is_verify,
            'ic_fullname'     => $request->ic_fullname,
            'ic_relationship' => $request->ic_relationship,
            'ic_contact'      => $request->ic_contact,
            'ic_email'        => $request->ic_email,
            'ic_address'      => $request->ic_address,
            'note'            => $request->note,
            'profile'         => $profilePath,
            'date_last_updated' => now(),
        ]);

        ActivityLog::record(
            'updated', 'citizen', $citizen->id,
            "{$citizen->lname}, {$citizen->fname} was updated",
            ['changed' => $changed]
        );

        // ── Household assignment (optional) ───────────────────────────────────
        $hhRole   = $request->input('hh_role');
        $hhAction = $request->input('hh_action'); // 'assign' | 'remove' | '' (no change)

        if ($hhAction === 'remove') {
            // Detach from current family — mirrors removeFamilyMember logic
            $oldFamily = $citizen->familyId ? Family::find($citizen->familyId) : null;
            if ($oldFamily) {
                // If this citizen was the household head, clear citizenHeadId
                $household = Household::find($oldFamily->householdId);
                if ($household && $household->citizenHeadId === $citizen->id) {
                    $household->update(['citizenHeadId' => null]);
                }
                // If they were the family head (isHead=1 or 2), delete the family record
                if (in_array($citizen->isHead, [1, 2])) {
                    $memberCount = Citizen::where('familyId', $oldFamily->id)->where('isHead', 0)->count();
                    if ($memberCount === 0) {
                        $oldFamily->delete();
                    }
                }
            }
            $citizen->update(['familyId' => null, 'isHead' => 0, 'relationId' => null, 'date_last_updated' => now()]);

        } elseif ($hhAction === 'assign' && $hhRole) {
            // Remove old assignment first if any
            if ($citizen->familyId) {
                $oldFamily = Family::find($citizen->familyId);
                if ($oldFamily) {
                    $household = Household::find($oldFamily->householdId);
                    if ($household && $household->citizenHeadId === $citizen->id) {
                        $household->update(['citizenHeadId' => null]);
                    }
                    if (in_array($citizen->isHead, [1, 2])) {
                        $memberCount = Citizen::where('familyId', $oldFamily->id)->where('isHead', 0)->count();
                        if ($memberCount === 0) $oldFamily->delete();
                    }
                }
                $citizen->update(['familyId' => null, 'isHead' => 0, 'relationId' => null]);
            }

            if ($hhRole === 'household_head') {
                $household = Household::create([
                    'addressId'        => $citizen->address,
                    'blk'              => $citizen->blk,
                    'lot'              => $citizen->lot,
                    'phaseStreet'      => $citizen->street ?? $citizen->phase,
                    'no'               => $citizen->no,
                    'completeAdress'   => $citizen->complete_address,
                    'citizenHeadId'    => $citizen->id,
                    'user_id'          => auth()->id(),
                    'date_created'     => now(),
                    'date_lastupdated' => now(),
                ]);
                $family = Family::create([
                    'householdId'       => $household->id,
                    'citizenId'         => $citizen->id,
                    'date_created'      => now(),
                    'date_last_updated' => now(),
                ]);
                $citizen->update(['familyId' => $family->id, 'isHead' => 2, 'date_last_updated' => now()]);

            } elseif ($hhRole === 'family_head') {
                $householdId = (int) $request->input('hh_household_id');
                if ($householdId) {
                    $family = Family::create([
                        'householdId'       => $householdId,
                        'citizenId'         => $citizen->id,
                        'date_created'      => now(),
                        'date_last_updated' => now(),
                    ]);
                    $citizen->update(['familyId' => $family->id, 'isHead' => 1, 'date_last_updated' => now()]);
                }

            } elseif ($hhRole === 'member') {
                $familyId   = (int) $request->input('hh_family_id');
                $relationId = (int) $request->input('hh_relation_id');
                if ($familyId && $relationId) {
                    $citizen->update([
                        'familyId'          => $familyId,
                        'isHead'            => 0,
                        'relationId'        => $relationId,
                        'date_last_updated' => now(),
                    ]);
                }
            }
        }

        $tagsBefore = $citizen->tags()->orderBy('name')->pluck('name')->all();
        $citizen->tags()->sync(
            array_filter(array_map('intval', (array) $request->input('tags', [])))
        );
        $tagsAfter = $citizen->tags()->orderBy('name')->pluck('name')->all();

        if ($tagsBefore !== $tagsAfter) {
            ActivityLog::record('tags_updated', 'citizen', $citizen->id,
                "Tags updated for {$citizen->lname}, {$citizen->fname}",
                ['before' => $tagsBefore, 'after' => $tagsAfter]
            );
        }

        return redirect()->route('citizens.show', $citizen->id)
            ->with('success', 'Citizen record updated successfully.');
    }

    /**
     * Archive rather than delete. Barangay records are referenced by blotter
     * entries, document requests, households and IDs, so the row is kept and
     * only hidden from the active list.
     */
    public function destroy(Request $request, Citizen $citizen)
    {
        $data = $request->validate(
            ['reason' => 'required|string|min:5|max:500'],
            [
                'reason.required' => 'A reason is required before archiving a citizen.',
                'reason.min'      => 'Please give a slightly fuller reason (at least 5 characters).',
            ]
        );

        $citizen->update([
            'is_active'         => 0,
            'archive_reason'    => $data['reason'],
            'archived_at'       => now(),
            'archived_by'       => auth()->id(),
            'date_last_updated' => now(),
        ]);

        ActivityLog::record(
            'archived', 'citizen', $citizen->id,
            "{$citizen->lname}, {$citizen->fname} was archived",
            ['reason' => $data['reason']]
        );

        return redirect()
            ->route('citizens.index')
            ->with('success', "\"{$citizen->full_name}\" moved to the Archive.");
    }

    /** Citizens that have been archived. */
    public function inactive(Request $request)
    {
        $search = $request->get('search');

        $citizens = Citizen::onlyInactive()
            ->with(['civilStatus', 'addressZone', 'approvalStatus', 'archivedBy'])
            ->when($search, fn($q) => $q->searchName($search))
            ->orderByDesc('date_last_updated')
            ->paginate(15)
            ->withQueryString();

        return view('citizens.inactive', compact('citizens', 'search'));
    }


    /**
     * Return an archived citizen to the active list.
     *
     * Resolved by id rather than route-model binding: the global "active"
     * scope hides archived citizens, so binding would 404.
     */
    public function restore(int $citizenId)
    {
        $citizen = Citizen::withInactive()->findOrFail($citizenId);

        if ($citizen->is_active) {
            return back()->with('error', 'That citizen is already active.');
        }

        $citizen->update([
            'is_active'         => 1,
            // These describe the archived state only.
            'archive_reason'    => null,
            'archived_at'       => null,
            'archived_by'       => null,
            'date_last_updated' => now(),
        ]);

        ActivityLog::record(
            'restored', 'citizen', $citizen->id,
            "{$citizen->lname}, {$citizen->fname} was restored to active"
        );

        return back()->with('success', "\"{$citizen->full_name}\" restored to active.");
    }

    public function detail(Citizen $citizen)
    {
        $citizen->loadMissing('addressZone', 'civilStatus');
        return response()->json([
            'id'             => $citizen->id,
            'full_name'      => $citizen->full_name,
            'fname'          => $citizen->fname,
            'lname'          => $citizen->lname,
            'mname'          => $citizen->mname,
            'suffix'         => $citizen->suffix,
            'qrcode'         => $citizen->qrcode,
            'bday'           => $citizen->bday?->format('M d, Y'),
            'gender'         => match((int)$citizen->gender) { 1 => 'Male', 2 => 'Female', default => '—' },
            'contact'        => $citizen->contact,
            'address'        => $citizen->full_address,
            'year_stay'      => $citizen->year_stay?->format('Y'),
            'civil_status'   => $citizen->civilStatus?->description,
            'ic_fullname'    => $citizen->ic_fullname,
            'ic_contact'     => $citizen->ic_contact,
            'ic_relationship'=> $citizen->ic_relationship,
            'ic_address'     => $citizen->ic_address,
            'profile'        => $citizen->profile_url,
        ]);
    }

    // Everything a citizen has been involved in, newest first — used by the pop-up
    // that opens when an ID is scanned on the Citizens page. Look up by ?code=
    // (the QR on their ID) or ?id=. Each section is only included when the user
    // may view that module.
    public function activity(Request $request)
    {
        $user  = auth()->user();
        $query = Citizen::with(['addressZone', 'activeHold', 'family.household', 'tags']);
        $citizen = $request->filled('code')
            ? $query->where('qrcode', trim((string) $request->input('code')))->first()
            : $query->find((int) $request->input('id'));

        if (!$citizen) {
            return response()->json(['found' => false, 'message' => 'No citizen found for this code.'], 404);
        }

        $items  = collect();
        $counts = [];
        // tone: success | warning | danger | info | gray (rendered as the project's dot-pill badges)
        $add = function (string $cat, string $icon, string $title, ?string $sub, ?string $label, string $tone, $when, ?string $url = null) use (&$items) {
            $at = $when ? \Carbon\Carbon::parse($when) : null;
            $items->push([
                'cat' => $cat, 'icon' => $icon, 'title' => $title, 'sub' => $sub,
                'label' => $label, 'tone' => $tone, 'url' => $url,
                'date' => $at?->format('M d, Y g:i A'), 'sort' => $at?->timestamp ?? 0,
            ]);
        };
        $docTone = ['pending' => 'warning', 'approved' => 'info', 'ready_for_release' => 'info', 'released' => 'success', 'rejected' => 'danger', 'voided' => 'danger'];
        $nice    = fn(?string $v) => $v ? ucfirst(str_replace('_', ' ', $v)) : null;   // ready_for_release → Ready for release

        if ($user->can('documents.view')) {
            $q = $citizen->documentRequests()->with('documentType');
            $counts['documents'] = (clone $q)->count();
            foreach ($q->orderByDesc('created_at')->limit(50)->get() as $r) {
                $add('documents', 'mgc_file_line', $r->documentType?->name ?? 'Document',
                    trim(($r->doc_number ? "#{$r->doc_number}" : '') . ($r->purpose ? " · {$r->purpose}" : ''), ' ·'),
                    $nice($r->status), $docTone[$r->status] ?? 'gray', $r->created_at, route('documents.requests.show', $r));
            }
        }

        if ($user->can('blotter.view')) {
            $parties = \App\Models\BlotterParty::with('blotter')->where('citizen_id', $citizen->id)->get()->filter(fn($p) => $p->blotter);
            $counts['blotter'] = $parties->unique('blotter_id')->count();
            $blotterTone = ['filed' => 'info', 'pending' => 'warning', 'ongoing' => 'warning', 'scheduled' => 'warning',
                            'settled' => 'success', 'referred' => 'gray', 'dismissed' => 'danger', 'closed' => 'gray'];
            foreach ($parties->unique('blotter_id')->take(50) as $p) {
                $b = $p->blotter;
                $add('blotter', 'mgc_alert_line', "Blotter {$b->blotter_no}", ucfirst($p->role) . ' · ' . $b->typeLabel(),
                    $b->statusLabel(), $blotterTone[$b->status] ?? 'gray', $b->filed_date ?? $b->created_at, route('blotters.show', $b));
            }
        }

        if ($user->can('events.view')) {
            $att  = $citizen->eventAttendances()->with('event')->orderByDesc('time_in')->limit(50)->get();
            $wins = $citizen->eventWins()->with('event')->orderByDesc('drawn_at')->limit(50)->get();
            $counts['events'] = $citizen->eventAttendances()->count() + $citizen->eventWins()->count();
            foreach ($att as $a) {
                $add('events', 'mgc_calendar_line', 'Attended ' . ($a->event?->title ?? 'an event'),
                    ['qr' => 'Checked in by QR scan', 'manual' => 'Checked in manually', 'bulk' => 'Added in bulk'][$a->method] ?? null,
                    null, 'info', $a->time_in, $a->event ? route('events.show', $a->event) : null);
            }
            foreach ($wins as $w) {
                $add('events', 'mgc_trophy_line', 'Raffle winner · ' . ($w->event?->title ?? 'event'),
                    trim("Round #{$w->round}" . ($w->prize_label ? " · {$w->prize_label}" : '')),
                    $w->claimed_at ? 'Claimed' : 'Not claimed', $w->claimed_at ? 'success' : 'warning',
                    $w->drawn_at, $w->event ? route('events.winners.index', $w->event) : null);
            }
        }

        if ($user->can('health.view')) {
            $hq = \App\Models\HealthCheckup::with('medicines')->where('citizen_id', $citizen->id);
            $counts['health'] = (clone $hq)->count();
            foreach ($hq->orderByDesc('checked_at')->limit(50)->get() as $h) {
                $alerts = $h->alerts();
                $sub = collect([
                    $h->bloodPressure() ? 'BP ' . $h->bloodPressure() : null,
                    $h->weight_kg ? rtrim(rtrim(number_format($h->weight_kg, 1), '0'), '.') . ' kg' : null,
                    $h->complaint ? \Illuminate\Support\Str::limit($h->complaint, 60) : null,
                    $h->medicines->isNotEmpty() ? $h->medicines->count() . ' medicine' . ($h->medicines->count() > 1 ? 's' : '') . ' given' : null,
                ])->filter()->implode(' · ');
                $add('health', 'mgc_heartbeat_line', 'Health check-up', $sub ?: null,
                    $alerts ? $alerts[0][0] : 'Normal', $alerts ? $alerts[0][1] : 'success', $h->checked_at, route('health.show', $h));
            }
        }

        $canRel   = $user->can('inventory_releases.view');
        $canRent  = $user->can('inventory_rentals.view');
        $canSched = $user->can('inventory_schedule.view');
        if ($canRel || $canRent || $canSched) {
            $releases = $canRel  ? $citizen->inventoryReleases()->with('items.item')->orderByDesc('created_at')->limit(50)->get() : collect();
            $rentals  = $canRent ? \App\Models\InventoryRental::with('items.item')->where('citizen_id', $citizen->id)->orderByDesc('created_at')->limit(50)->get() : collect();
            $counts['inventory'] = ($canRel ? $citizen->inventoryReleases()->count() : 0)
                + ($canRent ? \App\Models\InventoryRental::where('citizen_id', $citizen->id)->count() : 0);
            $itemList = fn($rows) => $rows->map(fn($i) => ($i->quantity ?? 1) . '× ' . ($i->item?->name ?? 'item'))->implode(', ');
            $relTone  = ['pending' => 'warning', 'approved' => 'info', 'released' => 'success', 'rejected' => 'danger'];
            foreach ($releases as $r) {
                $add('inventory', 'mgc_box_3_line', 'Received items', $itemList($r->items) ?: $r->purpose,
                    ucfirst($r->status), $relTone[$r->status] ?? 'gray', $r->released_at ?? $r->created_at, route('inventory.releases.show', $r));
            }
            $rentTone = ['scheduled' => 'info', 'out' => 'warning', 'returned' => 'success', 'cancelled' => 'gray'];
            foreach ($rentals as $r) {
                $overdue = $r->status === 'out' && $r->due_on && now()->gt($r->due_on);
                $add('inventory', 'mgc_box_3_line', 'Borrowed items', $itemList($r->items) . ($r->due_on ? ' · due ' . \Carbon\Carbon::parse($r->due_on)->format('M d, Y') : ''),
                    $overdue ? 'Overdue' : (\App\Models\InventoryRental::STATUSES[$r->status] ?? ucfirst($r->status)),
                    $overdue ? 'danger' : ($rentTone[$r->status] ?? 'gray'), $r->borrowed_on ?? $r->created_at, route('inventory.rentals.show', $r));
            }
            // Reservations on the Inventory → Schedule calendar (ambulance, hall…)
            $bookings = $canSched ? \App\Models\InventorySchedule::with('item:id,name')->where('citizen_id', $citizen->id)->orderByDesc('start_at')->limit(50)->get() : collect();
            $counts['inventory'] += $canSched ? \App\Models\InventorySchedule::where('citizen_id', $citizen->id)->count() : 0;
            $bookTone = ['reserved' => 'info', 'completed' => 'success', 'cancelled' => 'gray'];
            foreach ($bookings as $b) {
                $add('inventory', 'mgc_calendar_line', 'Reserved ' . ($b->quantity > 1 ? "{$b->quantity}× " : '') . ($b->item?->name ?? 'item'),
                    $b->start_at->format('M d, Y g:i A') . ' – ' . $b->end_at->format($b->start_at->isSameDay($b->end_at) ? 'g:i A' : 'M d, Y g:i A') . ($b->purpose ? " · {$b->purpose}" : ''),
                    \App\Models\InventorySchedule::STATUSES[$b->status] ?? ucfirst($b->status), $bookTone[$b->status] ?? 'gray', $b->created_at, route('inventory.schedule.index'));
            }
        }

        // The citizen record itself: ID cards, holds, and changes to the record.
        $ids   = $citizen->citizenIds()->orderByDesc('created_at')->get();
        $holds = $citizen->holds()->orderByDesc('placed_at')->get();
        $logs  = $user->can('activity_logs.view')
            ? \App\Models\ActivityLog::with('user')->where('subject_type', 'citizen')->where('subject_id', $citizen->id)
                ->whereNotIn('action', ['hold_placed', 'hold_lifted'])->orderByDesc('created_at')->limit(30)->get()
            : collect();
        $counts['records'] = $ids->count() + $holds->count() + $logs->count();
        foreach ($ids as $i) {
            $add('records', 'mgc_IDcard_line', 'Barangay ID issued', $i->valid_until ? 'Valid until ' . $i->valid_until->format('M d, Y') : null,
                $i->valid_until && $i->valid_until->isPast() ? 'Expired' : 'Valid', $i->valid_until && $i->valid_until->isPast() ? 'gray' : 'success', $i->created_at);
        }
        foreach ($holds as $h) {
            $add('records', 'mgc_lock_line', 'Placed on hold', $h->reason, $h->lifted_at ? 'Lifted' : 'Active', $h->lifted_at ? 'gray' : 'danger', $h->placed_at);
        }
        foreach ($logs as $l) {
            $add('records', 'mgc_history_line', $l->description ?: ucfirst(str_replace('_', ' ', $l->action)),
                $l->user ? 'by ' . $l->user->name : null, null, 'gray', $l->created_at);
        }

        $household = $citizen->family?->household;
        $latestId  = $ids->first();

        return response()->json([
            'found'   => true,
            'citizen' => [
                'id'         => $citizen->id,
                'name'       => $citizen->full_name,
                'code'       => \App\Models\Setting::instance()->formatCitizenId($citizen->id),
                'photo'      => $citizen->profile_url,
                'age'        => $citizen->age,
                'gender'     => match ((int) $citizen->gender) { 1 => 'Male', 2 => 'Female', default => null },
                'address'    => $citizen->full_address,
                'contact'    => $citizen->contact,
                'voter'      => (bool) $citizen->voters,
                'pwd'        => (bool) $citizen->is_pwd,
                'senior'     => ($citizen->age ?? 0) >= 60,
                'solo'       => (bool) $citizen->is_soloparents,
                'archived'   => !$citizen->is_active,
                'hold'       => $citizen->activeHold?->reason,
                'household'  => $household ? ($household->formatted_id ?? ('#' . $household->id)) . ($citizen->is_household_head ? ' · Head' : '') : null,
                'id_valid'   => $latestId?->valid_until?->format('M d, Y'),
                // Optional income classification of the citizen's family
                'income'     => \App\Support\IncomeClass::visibleTo($user) && $citizen->family
                    ? \App\Support\IncomeClass::describe($citizen->family) : null,
                'tags'       => $citizen->tags->map(fn($t) => ['name' => $t->name, 'color' => $t->color])->values(),
            ],
            'counts'  => $counts,
            'items'   => $items->sortByDesc('sort')->values(),
            'links'   => [
                'profile' => route('citizens.show', $citizen),
                'request' => $user->can('documents.create') ? route('documents.requests.create', ['citizen' => $citizen->id]) : null,
            ],
        ]);
    }

    // Also used for walk-ins on the document request page, so they're looked up
    // explicitly (route-model binding would hide them).
    public function quickHistory($citizen)
    {
        $citizen = Citizen::withWalkins()->findOrFail((int) $citizen);
        $requests = $citizen->documentRequests()
            ->with('documentType')
            ->orderByDesc('created_at')
            ->limit(5)
            ->get()
            ->map(fn($r) => [
                'id'     => $r->id,
                'type'   => $r->documentType?->name ?? '—',
                'status' => $r->status,
                'color'  => $r->status_color,
                'label'  => $r->status_label,
                'date'   => $r->created_at?->format('M d, Y') ?? '—',
            ]);

        $blotters = \App\Models\Blotter::whereHas('parties', fn($q) => $q->where('citizen_id', $citizen->id))
            ->orderByDesc('filed_date')
            ->limit(5)
            ->get()
            ->map(fn($b) => [
                'id'     => $b->id,
                'no'     => $b->blotter_no,
                'type'   => $b->typeLabel(),
                'status' => $b->status,
                'color'  => $b->statusColor(),
                'label'  => $b->statusLabel(),
                'date'   => $b->filed_date?->format('M d, Y') ?? '—',
            ]);

        return response()->json(compact('requests', 'blotters'));
    }

    public function demographics()
    {
        $total  = Citizen::where('is_active', 1)->count();
        $male   = Citizen::where('is_active', 1)->where('gender', 1)->count();
        $female = Citizen::where('is_active', 1)->where('gender', 0)->count();
        $voters = Citizen::where('is_active', 1)->where('voters', 1)->count();

        // Age groups
        $ageGroup = fn(int $min, int $max) => Citizen::where('is_active', 1)
            ->whereRaw('TIMESTAMPDIFF(YEAR, bday, CURDATE()) >= ?', [$min])
            ->whereRaw('TIMESTAMPDIFF(YEAR, bday, CURDATE()) <= ?', [$max])
            ->count();
        $children = $ageGroup(0, 12);
        $youth    = $ageGroup(13, 17);
        $adult    = $ageGroup(18, 59);
        $senior   = $ageGroup(60, 120);

        // Civil status
        $civilRows   = Citizen::where('is_active', 1)->with('civilStatus')
            ->selectRaw('civil_status, COUNT(*) as cnt')->groupBy('civil_status')->get();
        $civilLabels = $civilRows->map(fn($r) => optional($r->civilStatus)->description ?? 'Unknown')->values()->toArray();
        $civilValues = $civilRows->pluck('cnt')->toArray();

        // Voters by age
        $voterAge  = fn(int $min, int $max) => Citizen::where('is_active', 1)->where('voters', 1)
            ->whereRaw('TIMESTAMPDIFF(YEAR, bday, CURDATE()) >= ?', [$min])
            ->whereRaw('TIMESTAMPDIFF(YEAR, bday, CURDATE()) <= ?', [$max])
            ->count();
        $childrenV = $voterAge(0, 12);
        $youthV    = $voterAge(13, 17);
        $adultV    = $voterAge(18, 59);
        $seniorV   = $voterAge(60, 120);

        // Voters by zone
        $voterZoneRows  = Citizen::where('is_active', 1)->where('voters', 1)->with('addressZone')
            ->selectRaw('address, COUNT(*) as cnt')->groupBy('address')->orderByDesc('cnt')->get();
        $voterZoneLabels = $voterZoneRows->map(fn($r) => optional($r->addressZone)->description ?? 'No Zone')->values()->toArray();
        $voterZoneValues = $voterZoneRows->pluck('cnt')->toArray();

        // ── Household & Family ─────────────────────────────────────────────
        $totalHouseholds = \App\Models\Household::count();
        $totalFamilies   = \App\Models\Family::count();
        $assigned        = Citizen::where('is_active', 1)->whereNotNull('familyId')->count();
        $unassigned      = $total - $assigned;
        $avgSize         = $totalHouseholds > 0 ? round($assigned / $totalHouseholds, 1) : 0;

        // Members per zone
        $zoneMemberRows   = Citizen::where('is_active', 1)->whereNotNull('familyId')->with('addressZone')
            ->selectRaw('address, COUNT(*) as cnt')->groupBy('address')->orderByDesc('cnt')->get();
        $zoneMemberLabels = $zoneMemberRows->map(fn($r) => optional($r->addressZone)->description ?? 'No Zone')->values()->toArray();
        $zoneMemberValues = $zoneMemberRows->pluck('cnt')->toArray();

        // ── Special Sectors ────────────────────────────────────────────────
        $pwd        = Citizen::where('is_active', 1)->where('is_pwd', 1)->count();
        $soloParents = Citizen::where('is_active', 1)->where('is_soloparents', 1)->count();

        // Sectors by zone
        $allZones = Address::where('is_active', true)->orderBy('description')->get();
        $sectorsZoneLabels = $allZones->pluck('description')->toArray();
        $sectorsPwd = $sectorsSolo = $sectorsSenior = [];
        foreach ($allZones as $zone) {
            $base = Citizen::where('is_active', 1)->where('address', $zone->id);
            $sectorsPwd[]    = (clone $base)->where('is_pwd', 1)->count();
            $sectorsSolo[]   = (clone $base)->where('is_soloparents', 1)->count();
            $sectorsSenior[] = (clone $base)->whereRaw('TIMESTAMPDIFF(YEAR, bday, CURDATE()) >= 60')->count();
        }

        // ── Compile ────────────────────────────────────────────────────────
        $stats = compact('total', 'male', 'female', 'voters');

        $hh = [
            'total_households' => $totalHouseholds,
            'total_families'   => $totalFamilies,
            'avg_household_size' => $avgSize,
            'unassigned'       => $unassigned,
        ];

        $sectors = [
            'pwd'         => $pwd,
            'solo_parents' => $soloParents,
            'senior'      => $senior,
        ];

        $charts = [
            'gender'       => ['male' => $male, 'female' => $female],
            'age'          => ['children' => $children, 'youth' => $youth, 'adult' => $adult, 'senior' => $senior],
            'civil'        => ['labels' => $civilLabels, 'values' => $civilValues],
            'voter_split'  => ['voters' => $voters, 'non_voters' => $total - $voters],
            'voter_age'    => [
                'children_voters'     => $childrenV,  'children_non_voters' => $children - $childrenV,
                'youth_voters'        => $youthV,     'youth_non_voters'    => $youth - $youthV,
                'adult_voters'        => $adultV,     'adult_non_voters'    => $adult - $adultV,
                'senior_voters'       => $seniorV,    'senior_non_voters'   => $senior - $seniorV,
            ],
            'voter_zone'   => ['labels' => $voterZoneLabels, 'values' => $voterZoneValues],
            'zone_members' => ['labels' => $zoneMemberLabels, 'values' => $zoneMemberValues],
            'sectors_zone' => [
                'labels'      => $sectorsZoneLabels,
                'pwd'         => $sectorsPwd,
                'solo_parents' => $sectorsSolo,
                'senior'      => $sectorsSenior,
            ],
        ];

        // Optional income classification: families in active households, grouped by class.
        $income = null;
        if (\App\Support\IncomeClass::visibleTo(auth()->user())) {
            $bands   = \App\Support\IncomeClass::BANDS;
            $fam     = array_fill_keys(array_keys($bands), 0);
            $people  = array_fill_keys(array_keys($bands), 0);
            $byZone  = [];
            $unassessed = 0;
            $waitingThreshold = 0;
            $rows = Family::with('household.address')->withCount('allMembers')
                ->whereHas('household', fn($q) => $q->where('is_active', 1))
                ->get(['id', 'householdId', 'citizenId', 'monthly_income', 'income_class']);
            foreach ($rows as $f) {
                $key = \App\Support\IncomeClass::keyFor($f->monthly_income, $f->income_class, (int) $f->all_members_count);
                if (!$key) {
                    $f->monthly_income !== null ? $waitingThreshold++ : $unassessed++;
                    continue;
                }
                $fam[$key]++;
                $people[$key] += (int) $f->all_members_count;
                $zone = $f->household?->address?->description ?? 'No zone';
                $byZone[$zone] = ($byZone[$zone] ?? array_fill_keys(array_keys($bands), 0));
                $byZone[$zone][$key]++;
            }
            ksort($byZone);
            $assessed = array_sum($fam);
            $income = [
                'labels'     => array_column($bands, 'label'),
                'keys'       => array_keys($bands),
                'families'   => array_values($fam),
                'people'     => array_values($people),
                'by_zone'    => $byZone,
                'total'      => $rows->count(),
                'assessed'   => $assessed,
                'unassessed' => $unassessed,
                'waiting'    => $waitingThreshold,      // amounts entered but no threshold set yet
                'poor_pct'   => $assessed ? round($fam['poor'] / $assessed * 100, 1) : null,
                'threshold'  => \App\Support\IncomeClass::threshold(),
            ];
        }

        return view('citizens.demographics', compact('stats', 'hh', 'sectors', 'charts', 'income'));
    }

    public function createMinor()
    {
        return view('citizens.create_minor', [
            'addresses'       => Address::where('is_active', true)->orderBy('description')->get(),
            'approvalStatuses'=> ApprovalStatus::orderBy('id')->get(),
            'relations'       => Relation::orderBy('description')->get(),
        ]);
    }

    public function storeMinor(Request $request)
    {
        $rules = [
            'parent_id'       => 'required|exists:eb_citizen,id',
            'fname'           => 'required|max:250',
            'lname'           => 'required|max:250',
            'mname'           => 'nullable|max:250',
            'suffix'          => 'nullable|max:50',
            'gender'          => 'required|in:0,1',
            'bday'            => 'required|date|before:today',
            'bplace'          => 'required|max:250',
            'citizenship'     => 'required|max:250',
            'address'         => 'required|exists:eb_address,id',
            'relation_id'     => 'required|exists:eb_relation,id',
            'approval_status' => (auth()->user()->can('citizens.approve') ? 'required' : 'nullable') . '|exists:eb_approval_status,id',
            'is_pwd'          => 'required|in:0,1',
            'note'            => 'nullable|max:1000',
            'profile'         => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
        ];

        $messages = [
            'bday.before' => 'Birthday must be a past date.',
        ];

        $validator = \Illuminate\Support\Facades\Validator::make($request->all(), $rules, $messages);

        if ($validator->fails()) {
            return response()->json([
                'ok'     => false,
                'errors' => $validator->errors()->toArray(),
            ], 422);
        }

        // Age check
        $age = \Carbon\Carbon::parse($request->bday)->age;
        if ($age >= 18) {
            return response()->json([
                'ok'     => false,
                'errors' => ['bday' => ['This person is 18 or older. Use the regular citizen registration form.']],
            ], 422);
        }

        // Parent must have a family
        $parent = Citizen::with('family.household')->findOrFail($request->parent_id);
        if (!$parent->familyId || !$parent->family) {
            return response()->json([
                'ok'      => false,
                'message' => 'The selected parent has no household or family assignment. Please assign the parent to a household first.',
            ], 422);
        }

        // Duplicate check
        $duplicate = Citizen::where('fname', $request->fname)
            ->where('lname', $request->lname)
            ->whereDate('bday', $request->bday)
            ->first();

        if ($duplicate) {
            return response()->json([
                'ok'     => false,
                'errors' => ['fname' => ['A citizen with the same name and birthday already exists (ID: ' . \App\Models\Setting::instance()->formatCitizenId($duplicate->id) . ').']],
            ], 422);
        }

        $qrcode = md5(\Illuminate\Support\Str::random(12) . date('Y-m-d H:i:s'));

        $profilePath = null;
        if ($request->hasFile('profile')) {
            $file     = $request->file('profile');
            $filename = $qrcode . '.' . $file->getClientOriginalExtension();
            $file->storeAs('public/citizen', $filename);
            $profilePath = 'public/citizen/' . $filename;
        }

        $household = $parent->family->household;

        $citizen = Citizen::create([
            'fname'             => $request->fname,
            'mname'             => $request->mname,
            'lname'             => $request->lname,
            'suffix'            => $request->suffix,
            'gender'            => $request->gender,
            'civil_status'      => \App\Models\CivilStatus::where('description', 'like', '%single%')->value('id') ?? 1,
            'bday'              => $request->bday,
            'bplace'            => $request->bplace,
            'citizenship'       => $request->citizenship,
            'address'           => $household->addressId ?? $request->address,
            'no'                => $household->no,
            'street'            => $household->phaseStreet,
            'blk'               => $household->blk,
            'lot'               => $household->lot,
            'phase'             => null,
            'complete_address'  => $household->completeAdress,
            'year_stay'         => now()->format('Y-m') . '-01',
            'owner_status'      => 0,
            'voters'            => 0,
            'pricinct_no'       => null,
            'is_soloparents'    => 0,
            'is_pwd'            => $request->is_pwd,
            'is_id_release'     => 0,
            'is_verify'         => 0,
            'approval_status'   => $this->approvalFor($request),
            'is_active'         => 1,
            'ic_fullname'       => $parent->fname . ' ' . $parent->lname,
            'ic_relationship'   => 'Parent',
            'ic_contact'        => $parent->contact,
            'note'              => $request->note,
            'profile'           => $profilePath,
            'qrcode'            => $qrcode,
            'date_created'      => now(),
            'date_last_updated' => now(),
        ]);

        $citizen->update([
            'familyId'          => $parent->familyId,
            'isHead'            => 0,
            'relationId'        => $request->relation_id,
            'date_last_updated' => now(),
        ]);

        ActivityLog::record(
            'created', 'citizen', $citizen->id,
            "{$citizen->lname}, {$citizen->fname} was registered as a minor under {$parent->lname}, {$parent->fname}",
            ['citizen_id' => \App\Models\Setting::instance()->formatCitizenId($citizen->id), 'parent_id' => $parent->id]
        );

        return response()->json([
            'ok'       => true,
            'redirect' => route('citizens.show', $citizen->id),
            'message'  => 'Minor registered successfully and linked to parent\'s household.',
        ]);
    }

    public function parentSearch(Request $request)
    {
        $q = trim($request->get('q', ''));

        if (strlen($q) < 2) {
            return response()->json([]);
        }

        return response()->json(
            Citizen::with(['family.household.address', 'addressZone'])
                ->where('is_active', 1)
                ->searchName($q)
                ->orderBy('lname')->orderBy('fname')
                ->limit(12)
                ->get()
                ->map(function ($c) {
                    $family    = $c->family;
                    $household = $family?->household;
                    $hasFamily = $family && $household;

                    $address = '';
                    if ($household) {
                        $parts = [];
                        if ($household->blk)           $parts[] = 'Blk ' . $household->blk;
                        if ($household->lot)           $parts[] = 'Lot ' . $household->lot;
                        if ($household->phaseStreet)   $parts[] = $household->phaseStreet;
                        if ($household->no)            $parts[] = $household->no;
                        if ($household->address)       $parts[] = $household->address->description ?? '';
                        $address = implode(', ', array_filter($parts));
                    }

                    return [
                        'id'         => $c->id,
                        'name'       => trim("{$c->lname}, {$c->fname}" . ($c->mname ? " {$c->mname}" : '')),
                        'age'        => $c->age,
                        'profile'    => $c->profile_url,
                        'zone'       => $c->addressZone?->description ?? '',
                        'has_family' => $hasFamily,
                        'family_id'  => $family?->id,
                        'address'    => $address,
                        'address_id' => $household?->addressId,
                        'hh_no'      => $household ? 'HH-' . str_pad($household->id, 5, '0', STR_PAD_LEFT) : null,
                        'blk'        => $household?->blk,
                        'lot'        => $household?->lot,
                        'phase_street' => $household?->phaseStreet,
                        'no'         => $household?->no,
                        'complete_address' => $household?->completeAdress,
                        'is_subd'    => $household?->address?->is_subd ?? 0,
                    ];
                })
        );
    }

    /**
     * Approval status to save. Only staff with citizens.approve choose it; for
     * everyone else a new record starts as Pending (1) and an edit keeps the
     * current status.
     */
    private function approvalFor(Request $request, ?Citizen $existing = null): int
    {
        if ($request->user()->can('citizens.approve')) {
            return (int) $request->approval_status;
        }
        return (int) ($existing?->approval_status ?? 1);
    }

    public function search(Request $request)
    {
        $q = trim($request->get('q', ''));

        if (strlen($q) < 2) {
            return response()->json([]);
        }

        return response()->json(
            $this->mapCitizens(
                Citizen::with(['activeHold', 'addressZone'])
                    ->where('is_active', 1)
                    ->searchName($q)
                    ->orderBy('lname')->orderBy('fname')
                    ->limit(10)
                    ->get($this->citizenPickerColumns())
            )
        );
    }

    /**
     * Most-recently-updated citizens, for pre-populating a picker before search.
     */
    public function recent(Request $request)
    {
        $limit = min((int) $request->get('limit', 5), 20);

        return response()->json(
            $this->mapCitizens(
                Citizen::with(['activeHold', 'addressZone'])
                    ->where('is_active', 1)
                    ->orderByDesc('date_last_updated')
                    ->orderByDesc('id')   // stable tie-break when timestamps are equal
                    ->limit($limit)
                    ->get($this->citizenPickerColumns())
            )
        );
    }

    protected function citizenPickerColumns(): array
    {
        // Address parts + zone id are needed for full_address (most records keep
        // their address only in these parts, not in complete_address).
        return ['id', 'fname', 'mname', 'lname', 'suffix', 'contact', 'complete_address', 'qrcode', 'profile', 'voters', 'is_pwd', 'is_soloparents', 'bday', 'date_last_updated',
                'address', 'blk', 'lot', 'phase', 'street', 'no'];
    }

    protected function mapCitizens($citizens)
    {
        return $citizens->map(fn($c) => [
            'id'      => $c->id,
            'name'    => $c->full_name,
            'contact' => $c->contact ?? '',
            'address' => $c->full_address,
            'qrcode'  => $c->qrcode ?? '',
            'profile' => $c->profile_url,
            'voter'   => (bool) $c->voters,
            'pwd'     => (bool) $c->is_pwd,
            'solo'    => (bool) $c->is_soloparents,
            'senior'  => $c->age !== null && $c->age >= 60,
            // Reason text when on hold (blocks document issuance), else null.
            'hold'    => $c->activeHold?->reason,
        ]);
    }
}
