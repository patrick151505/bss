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

class CitizenController extends Controller
{
    public function index(Request $request)
    {
        $filters = $request->only([
            'search', 'gender', 'civil_status', 'address',
            'approval_status', 'age_min', 'age_max',
            'is_active', 'voters', 'is_pwd', 'is_soloparents', 'senior',
            'household_search', 'hh_role', 'hh_assigned', 'tags',
        ]);

        $query = Citizen::with(['civilStatus', 'addressZone', 'approvalStatus', 'family.household', 'tags'])
            ->when($filters['search'] ?? null, function ($q, $search) {
                $q->where(function ($q) use ($search) {
                    $q->where('fname', 'like', "%{$search}%")
                      ->orWhere('lname', 'like', "%{$search}%")
                      ->orWhere('mname', 'like', "%{$search}%")
                      ->orWhere('contact', 'like', "%{$search}%")
                      ->orWhere('email', 'like', "%{$search}%");
                });
            })
            ->when($filters['gender'] ?? null, fn($q, $v) => $q->where('gender', $v))
            ->when($filters['civil_status'] ?? null, fn($q, $v) => $q->where('civil_status', $v))
            ->when($filters['address'] ?? null, fn($q, $v) => $q->where('address', $v))
            ->when($filters['approval_status'] ?? null, fn($q, $v) => $q->where('approval_status', $v))
            ->when($filters['voters'] ?? null, fn($q, $v) => $q->where('voters', $v))
            ->when($filters['is_pwd'] ?? null, fn($q, $v) => $q->where('is_pwd', $v))
            ->when($filters['is_soloparents'] ?? null, fn($q, $v) => $q->where('is_soloparents', $v))
            ->when(isset($filters['is_active']) && $filters['is_active'] !== '', fn($q) => $q->where('is_active', $filters['is_active']))
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
            ->orderBy('lname')
            ->orderBy('fname');

        $perPage = (int) $request->get('per_page', 15);
        $citizens = $query->paginate(in_array($perPage, [15, 25, 50, 100]) ? $perPage : 15)
            ->withQueryString();

        if ($request->ajax()) {
            return response()->json([
                'html'  => view('citizens._table', compact('citizens'))->render(),
                'total' => $citizens->total(),
            ]);
        }

        $stats = [
            'total'  => Citizen::count(),
            'male'   => Citizen::where('gender', 1)->count(),
            'female' => Citizen::where('gender', 0)->count(),
            'voters' => Citizen::where('voters', 1)->count(),
            'pwd'    => Citizen::where('is_pwd', 1)->count(),
            'senior' => Citizen::whereRaw('TIMESTAMPDIFF(YEAR, bday, CURDATE()) >= 60')->count(),
        ];

        return view('citizens.index', [
            'citizens'        => $citizens,
            'filters'         => $filters,
            'stats'           => $stats,
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
            'is_active', 'voters', 'is_pwd', 'is_soloparents', 'senior',
        ]);

        $query = Citizen::with(['civilStatus', 'addressZone', 'approvalStatus'])
            ->when($filters['search'] ?? null, function ($q, $search) {
                $q->where(function ($q) use ($search) {
                    $q->where('fname', 'like', "%{$search}%")
                      ->orWhere('lname', 'like', "%{$search}%")
                      ->orWhere('mname', 'like', "%{$search}%")
                      ->orWhere('contact', 'like', "%{$search}%")
                      ->orWhere('email', 'like', "%{$search}%");
                });
            })
            ->when($filters['gender'] ?? null, fn($q, $v) => $q->where('gender', $v))
            ->when($filters['civil_status'] ?? null, fn($q, $v) => $q->where('civil_status', $v))
            ->when($filters['address'] ?? null, fn($q, $v) => $q->where('address', $v))
            ->when($filters['approval_status'] ?? null, fn($q, $v) => $q->where('approval_status', $v))
            ->when($filters['voters'] ?? null, fn($q, $v) => $q->where('voters', $v))
            ->when($filters['is_pwd'] ?? null, fn($q, $v) => $q->where('is_pwd', $v))
            ->when($filters['is_soloparents'] ?? null, fn($q, $v) => $q->where('is_soloparents', $v))
            ->when(isset($filters['is_active']) && $filters['is_active'] !== '', fn($q) => $q->where('is_active', $filters['is_active']))
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
            'approval_status' => 'required|exists:eb_approval_status,id',
            'is_active'       => 'required|in:0,1',
            'is_pwd'          => 'required|in:0,1',
            'is_soloparents'  => 'required|in:0,1',
            'voters'          => 'required|in:0,1',
            'is_id_release'   => 'required|in:0,1',
            'is_verify'       => 'required|in:0,1',
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
            'approval_status'   => $request->approval_status,
            'is_active'         => $request->is_active,
            'is_pwd'            => $request->is_pwd,
            'is_soloparents'    => $request->is_soloparents,
            'voters'            => $request->voters,
            'pricinct_no'       => $request->voters == 1 ? $request->pricinct_no : null,
            'is_id_release'     => $request->is_id_release,
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
        $citizen->load(['civilStatus', 'addressZone', 'approvalStatus', 'approvedBy', 'relation', 'tags']);

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
            'approval_status' => 'required|exists:eb_approval_status,id',
            'is_active'       => 'required|in:0,1',
            'is_pwd'          => 'required|in:0,1',
            'is_soloparents'  => 'required|in:0,1',
            'voters'          => 'required|in:0,1',
            'is_id_release'   => 'required|in:0,1',
            'is_verify'       => 'required|in:0,1',
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
            'voters', 'pricinct_no', 'is_id_release', 'is_verify',
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
            'approval_status' => $request->approval_status,
            'is_active'       => $request->is_active,
            'is_pwd'          => $request->is_pwd,
            'is_soloparents'  => $request->is_soloparents,
            'voters'          => $request->voters,
            'pricinct_no'     => $request->voters == 1 ? $request->pricinct_no : null,
            'is_id_release'   => $request->is_id_release,
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

    public function destroy(Citizen $citizen)
    {
        // TODO: implement destroy
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
            'address'        => $citizen->complete_address ?? $citizen->addressZone?->description,
            'year_stay'      => $citizen->year_stay?->format('Y'),
            'civil_status'   => $citizen->civilStatus?->description,
            'ic_fullname'    => $citizen->ic_fullname,
            'ic_contact'     => $citizen->ic_contact,
            'ic_relationship'=> $citizen->ic_relationship,
            'ic_address'     => $citizen->ic_address,
            'profile'        => $citizen->profile ? asset(str_replace('public/', 'storage/', $citizen->profile)) : null,
        ]);
    }

    public function quickHistory(Citizen $citizen)
    {
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

        return view('citizens.demographics', compact('stats', 'hh', 'sectors', 'charts'));
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
            'approval_status' => 'required|exists:eb_approval_status,id',
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
            'approval_status'   => $request->approval_status,
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
                ->where(function ($query) use ($q) {
                    $query->where('fname', 'like', "%{$q}%")
                          ->orWhere('lname', 'like', "%{$q}%")
                          ->orWhere('mname', 'like', "%{$q}%");
                })
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
                        'profile'    => $c->profile ? asset(str_replace('public/', 'storage/', $c->profile)) : null,
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

    public function search(Request $request)
    {
        $q = trim($request->get('q', ''));

        if (strlen($q) < 2) {
            return response()->json([]);
        }

        return response()->json(
            Citizen::where('is_active', 1)
                ->where(function ($query) use ($q) {
                    $query->where('fname', 'like', "%{$q}%")
                          ->orWhere('lname', 'like', "%{$q}%")
                          ->orWhere('mname', 'like', "%{$q}%")
                          ->orWhere('qrcode', $q);
                })
                ->orderBy('lname')->orderBy('fname')
                ->limit(10)
                ->get(['id', 'fname', 'mname', 'lname', 'suffix', 'contact', 'complete_address', 'qrcode', 'profile'])
                ->map(fn($c) => [
                    'id'      => $c->id,
                    'name'    => $c->full_name,
                    'contact' => $c->contact ?? '',
                    'address' => $c->complete_address ?? '',
                    'qrcode'  => $c->qrcode ?? '',
                    'profile' => $c->profile ? asset(str_replace('public/', 'storage/', $c->profile)) : null,
                ])
        );
    }
}
