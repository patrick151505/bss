<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\Household;
use App\Models\Family;
use App\Models\Citizen;
use App\Models\Relation;
use App\Models\Address;
use App\Models\ActivityLog;

class HouseholdController extends Controller
{
    // ─── Index ───────────────────────────────────────────────────────────────

    public function index()
    {
        $addresses = Address::where('is_active', true)->orderBy('description')->get();
        $relations = Relation::orderBy('description')->get();
        return view('households.index', compact('addresses', 'relations'));
    }

    // ─── KPI counts ──────────────────────────────────────────────────────────

    public function kpi()
    {
        $households = Household::count();
        $families   = Family::count();
        $members    = Citizen::whereNotNull('familyId')->where('isHead', 0)->count();
        $heads      = Citizen::whereNotNull('familyId')->whereIn('isHead', [1, 2])->count();

        return response()->json([
            'success'    => true,
            'households' => $households,
            'families'   => $families,
            'members'    => $members + $heads,
            'avg'        => $households > 0 ? round(($members + $heads) / $households, 1) : 0,
        ]);
    }

    // ─── Paginated list (AJAX) ────────────────────────────────────────────────

    public function retrieve(Request $request)
    {
        $page      = max(1, (int) $request->input('page', 1));
        $limit     = (int) $request->input('limit', 6);
        $addressId = $request->input('addressId');
        $search    = $request->input('search');

        $query = Household::with(['address', 'householdHead', 'families'])
            ->when($addressId, fn($q) => $q->where('addressId', $addressId))
            ->when($search, function ($q) use ($search) {
                $q->where(function ($q) use ($search) {
                    $q->where('phaseStreet', 'like', "%$search%")
                      ->orWhere('no', 'like', "%$search%")
                      ->orWhere('internal', 'like', "%$search%")
                      ->orWhere('completeAdress', 'like', "%$search%")
                      ->orWhereHas('address', fn($q) => $q->where('description', 'like', "%$search%"))
                      ->orWhereHas('householdHead', fn($q) =>
                          $q->where(DB::raw("CONCAT(fname,' ',lname)"), 'like', "%$search%")
                      );
                });
            })
            ->orderByDesc('date_created');

        $total      = $query->count();
        $households = $query->skip(($page - 1) * $limit)->take($limit)->get();

        $data = $households->map(function (Household $h) {
            $familyIds   = $h->families->pluck('id');
            $memberCount = Citizen::whereIn('familyId', $familyIds)->where('isHead', 0)->count();

            return [
                'id'             => $h->id,
                'formatted_id'   => $h->formatted_id,
                'full_address'   => $h->full_address,
                'internal'       => $h->internal,
                'address_desc'   => $h->address?->description,
                'family_count'   => $h->families->count(),
                'member_count'   => $memberCount,
                'head_name'      => $h->householdHead?->full_name,
                'head_photo'     => $h->householdHead?->profile
                    ? asset(str_replace('public/', 'storage/', $h->householdHead->profile))
                    : null,
                'date_created'   => $h->date_created?->format('M d, Y'),
                'date_updated'   => $h->date_lastupdated?->format('M d, Y'),
            ];
        });

        return response()->json([
            'success' => true,
            'data'    => $data,
            'total'   => $total,
            'page'    => $page,
            'limit'   => $limit,
        ]);
    }

    // ─── Household details (families + members) ───────────────────────────────

    public function getDetails(Request $request)
    {
        $household = Household::with([
            'address',
            'householdHead',
            'families.head',
            'families.members.relation',
        ])->find($request->id);

        if (!$household) {
            return response()->json(['success' => false]);
        }

        $families = $household->families->map(function (Family $f) use ($household) {
            $head = $f->head;
            return [
                'family_id'        => $f->id,
                'family_id_format' => $f->formatted_id,
                'is_household_head'=> $head?->id === $household->citizenHeadId,
                'citizen_id'       => $head?->id,
                'head_name'        => $head?->full_name ?? '—',
                'head_photo'       => $head?->profile
                    ? asset(str_replace('public/', 'storage/', $head->profile))
                    : null,
                'head_role'        => $head?->id === $household->citizenHeadId ? 'Household Head' : 'Family Head',
                'head_qrcode'      => $head?->qrcode,
                'member_count'     => $f->members->count(),
                'members'          => $f->members->map(fn(Citizen $m) => [
                    'id'       => $m->id,
                    'name'     => $m->full_name,
                    'relation' => $m->relation?->description ?? '—',
                    'photo'    => $m->profile
                        ? asset(str_replace('public/', 'storage/', $m->profile))
                        : null,
                    'qrcode'   => $m->qrcode,
                    'age'      => $m->age,
                    'gender'   => $m->gender === 1 ? 'Male' : 'Female',
                ]),
            ];
        });

        return response()->json([
            'success' => true,
            'data'    => [
                'household_id'        => $household->id,
                'household_id_format' => $household->formatted_id,
                'full_address'        => $household->full_address,
                'internal'            => $household->internal,
                'head_name'           => $household->householdHead?->full_name,
                'head_photo'          => $household->householdHead?->profile
                    ? asset(str_replace('public/', 'storage/', $household->householdHead->profile))
                    : null,
                'family_count'        => $household->families->count(),
                'total_members'       => $household->families->sum(fn($f) => $f->members->count() + ($f->head ? 1 : 0)),
                'families'            => $families,
            ],
        ]);
    }

    // ─── Create household ─────────────────────────────────────────────────────

    public function store(Request $request)
    {
        $request->validate([
            'addressId'  => 'required|integer',
            'citizenId'  => 'required|integer',
        ]);

        $isSub = (int) $request->input('is_sub');

        // Duplicate check
        $exists = $this->duplicateCheck($request, $isSub);
        if ($exists) {
            return response()->json(['success' => false, 'message' => 'This address already exists.']);
        }

        if (!$request->confirmAddress) {
            return response()->json(['success' => false, 'noConfirm' => true]);
        }

        $data = $this->buildAddressData($request, $isSub);
        $data['citizenHeadId']   = $request->citizenId;
        $data['user_id']         = auth()->id();
        $data['date_created']    = now();
        $data['date_lastupdated']= now();

        $household = Household::create($data);

        // Create the family for the household head
        $family = Family::create([
            'householdId'       => $household->id,
            'citizenId'         => $request->citizenId,
            'date_created'      => now(),
            'date_last_updated' => now(),
        ]);

        // Mark citizen as household head
        Citizen::where('id', $request->citizenId)->update([
            'familyId'          => $family->id,
            'isHead'            => 2,
            'date_last_updated' => now(),
        ]);

        ActivityLog::record('created', 'household', $household->id,
            "Household {$household->formatted_id} created with head: " . optional(Citizen::find($request->citizenId))->full_name . "."
        );

        return response()->json(['success' => true, 'household_id' => $household->id]);
    }

    // ─── Get single household for edit ───────────────────────────────────────

    public function getHousehold(Request $request)
    {
        $h = Household::with('address')->find($request->id);
        if (!$h) return response()->json(['success' => false]);

        return response()->json([
            'success' => true,
            'data'    => [
                'id'          => $h->id,
                'addressId'   => $h->addressId,
                'is_subd'     => $h->address?->is_subd,
                'blk'         => $h->blk,
                'lot'         => $h->lot,
                'phaseStreet' => $h->phaseStreet,
                'no'          => $h->no,
                'internal'    => $h->internal,
                'completeAdress' => $h->completeAdress,
            ],
        ]);
    }

    // ─── Update household address ─────────────────────────────────────────────

    public function update(Request $request)
    {
        $request->validate(['addressId' => 'required|integer', 'edit_id' => 'required|integer']);

        $isSub = (int) $request->input('is_sub');

        if (!$request->confirmAddress) {
            return response()->json(['success' => false, 'noConfirm' => true]);
        }

        $data = $this->buildAddressData($request, $isSub);
        $data['date_lastupdated'] = now();

        Household::where('id', $request->edit_id)->update($data);

        return response()->json(['success' => true]);
    }

    // ─── Add family head to existing household ────────────────────────────────

    public function storeFamily(Request $request)
    {
        $householdId = (int) $request->householdId;
        $citizenId   = (int) $request->citizenId;

        if (!$householdId || !$citizenId) {
            return response()->json(['success' => false, 'message' => 'Invalid data.']);
        }

        $citizen = Citizen::find($citizenId);
        if (!$citizen) {
            return response()->json(['success' => false, 'message' => 'Citizen not found.']);
        }

        if ($citizen->familyId) {
            return response()->json(['success' => false, 'message' => 'This citizen is already assigned to a family. Remove the current assignment first.']);
        }

        $family = Family::create([
            'householdId'       => $householdId,
            'citizenId'         => $citizenId,
            'date_created'      => now(),
            'date_last_updated' => now(),
        ]);

        $citizen->update([
            'familyId'          => $family->id,
            'isHead'            => 1,
            'date_last_updated' => now(),
        ]);

        return response()->json(['success' => true]);
    }

    // ─── Add member to a family ───────────────────────────────────────────────

    public function storeFamilyMember(Request $request)
    {
        $familyId   = (int) $request->familyId;
        $citizenId  = (int) $request->citizenId;
        $relationId = (int) $request->relationId;

        if (!$familyId || !$citizenId || !$relationId) {
            return response()->json(['success' => false, 'message' => 'Invalid data.']);
        }

        $citizen = Citizen::find($citizenId);
        if (!$citizen) {
            return response()->json(['success' => false, 'message' => 'Citizen not found.']);
        }

        if ($citizen->familyId) {
            return response()->json(['success' => false, 'message' => 'This citizen is already assigned to a family.']);
        }

        $citizen->update([
            'familyId'          => $familyId,
            'isHead'            => 0,
            'relationId'        => $relationId,
            'date_last_updated' => now(),
        ]);

        return response()->json(['success' => true]);
    }

    // ─── Remove family head ───────────────────────────────────────────────────

    public function removeFamilyHead(Request $request)
    {
        $familyId  = (int) $request->familyId;
        $citizenId = (int) $request->citizenId;

        $family = Family::find($familyId);
        if (!$family) return response()->json(['success' => false, 'message' => 'Family not found.']);

        $memberCount = Citizen::where('familyId', $familyId)->where('isHead', 0)->count();
        if ($memberCount > 0) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot remove: family still has ' . $memberCount . ' member(s). Remove all members first.',
            ]);
        }

        // If this was the household head, clear citizenHeadId
        $household = Household::find($family->householdId);
        if ($household && $household->citizenHeadId === $citizenId) {
            $household->update(['citizenHeadId' => null]);
        }

        $family->delete();

        Citizen::where('id', $citizenId)->update([
            'familyId'          => null,
            'isHead'            => 0,
            'relationId'        => null,
            'date_last_updated' => now(),
        ]);

        return response()->json(['success' => true]);
    }

    // ─── Remove family member ─────────────────────────────────────────────────

    public function removeFamilyMember(Request $request)
    {
        $memberId = (int) $request->memberId;

        Citizen::where('id', $memberId)->update([
            'familyId'          => null,
            'isHead'            => 0,
            'relationId'        => null,
            'date_last_updated' => now(),
        ]);

        return response()->json(['success' => true]);
    }

    // ─── Set household head (switch among family heads) ──────────────────────

    public function setHouseholdHead(Request $request)
    {
        $householdId = (int) $request->householdId;
        $citizenId   = (int) $request->citizenId;

        // Demote current household head to family head
        $current = Household::find($householdId);
        if ($current?->citizenHeadId) {
            Citizen::where('id', $current->citizenHeadId)->update(['isHead' => 1]);
        }

        // Promote new head
        Citizen::where('id', $citizenId)->update(['isHead' => 2]);
        Household::where('id', $householdId)->update([
            'citizenHeadId'   => $citizenId,
            'date_lastupdated'=> now(),
        ]);

        return response()->json(['success' => true]);
    }

    // ─── Citizen picker (AJAX HTML partial) ──────────────────────────────────

    public function filterCitizens(Request $request)
    {
        $search = $request->input('search');
        $page   = max(1, (int) $request->input('page', 1));
        $limit  = 8;

        $query = Citizen::with('addressZone')
            ->where('is_active', 1)
            ->where('approval_status', 1)
            ->when($search, function ($q) use ($search) {
                $q->where(function ($q) use ($search) {
                    $q->where('fname', 'like', "%$search%")
                      ->orWhere('lname', 'like', "%$search%")
                      ->orWhere('mname', 'like', "%$search%");
                });
            })
            ->orderBy('lname')->orderBy('fname');

        $total    = $query->count();
        $citizens = $query->skip(($page - 1) * $limit)->take($limit)->get();
        $callback = in_array($request->input('callback'), ['selectCitizen', 'selectCitizenForWizard'])
            ? $request->input('callback')
            : 'selectCitizen';

        return view('households.partials.citizen-picker', compact('citizens', 'total', 'page', 'limit', 'search', 'callback'));
    }

    // ─── Get citizen address for auto-fill ───────────────────────────────────

    public function getCitizenAddress(Request $request)
    {
        $citizen = Citizen::with('addressZone')->find($request->citizenId);
        if (!$citizen) return response()->json(['success' => false]);

        return response()->json([
            'success' => true,
            'data'    => [
                'addressId'       => $citizen->address,
                'is_subd'         => $citizen->addressZone?->is_subd,
                'blk'             => $citizen->blk,
                'lot'             => $citizen->lot,
                'street'          => $citizen->street,
                'no'              => $citizen->no,
                'phase'           => $citizen->phase,
                'complete_address'=> $citizen->complete_address,
            ],
        ]);
    }

    // ─── Quick search for citizen registration modal ─────────────────────────

    public function search(Request $request)
    {
        $q = trim($request->input('q', ''));

        $households = Household::with(['address', 'householdHead', 'families.head'])
            ->when($q !== '', function ($query) use ($q) {
                $query->where(function ($query) use ($q) {
                    $query->where('no', 'like', "%$q%")
                          ->orWhere('phaseStreet', 'like', "%$q%")
                          ->orWhere('completeAdress', 'like', "%$q%")
                          ->orWhereHas('address', fn($x) => $x->where('description', 'like', "%$q%"))
                          ->orWhereHas('householdHead', fn($x) =>
                              $x->where(DB::raw("CONCAT(fname,' ',lname)"), 'like', "%$q%")
                          );
                });
            })
            ->orderByDesc('date_created')
            ->limit(10)
            ->get()
            ->map(fn(Household $h) => [
                'id'             => $h->id,
                'formatted_id'   => $h->formatted_id,
                'full_address'   => $h->full_address,
                'head_name'      => $h->householdHead?->full_name ?? '—',
                'family_count'   => $h->families->count(),
                'families'       => $h->families->map(fn(Family $f) => [
                    'id'        => $f->id,
                    'head_name' => $f->head?->full_name ?? '—',
                ]),
                // address fields for auto-fill on citizen form
                'addressId'      => $h->addressId,
                'is_subd'        => $h->address?->is_subd,
                'rules'          => $h->address?->rules_required ?? '',
                'blk'            => $h->blk,
                'lot'            => $h->lot,
                'phaseStreet'    => $h->phaseStreet,
                'no'             => $h->no,
                'completeAdress' => $h->completeAdress,
            ]);

        return response()->json(['success' => true, 'data' => $households]);
    }

    // ─── Helpers ──────────────────────────────────────────────────────────────

    private function duplicateCheck(Request $request, int $isSub): bool
    {
        $internal = $request->internal ?: null;
        $q = Household::where('addressId', $request->addressId)
            ->where('internal', $internal);

        if ($isSub === 1) {
            $q->where('blk', $request->blk)->where('lot', $request->lot)->where('phaseStreet', $request->phaseStreet);
        } elseif ($isSub === 0) {
            $q->where('no', $request->no)->where('phaseStreet', $request->phaseStreet2);
        } else {
            $q->where('completeAdress', $request->completeAdress);
        }

        return $q->exists();
    }

    private function buildAddressData(Request $request, int $isSub): array
    {
        $data = [
            'addressId'      => $request->addressId,
            'blk'            => null,
            'lot'            => null,
            'phaseStreet'    => null,
            'no'             => null,
            'internal'       => $request->internal ?: null,
            'completeAdress' => null,
        ];

        if ($isSub === 1) {
            $data['blk']         = $request->blk;
            $data['lot']         = $request->lot;
            $data['phaseStreet'] = $request->phaseStreet;
        } elseif ($isSub === 0) {
            $data['no']          = $request->no;
            $data['phaseStreet'] = $request->phaseStreet2;
        } else {
            $data['completeAdress'] = $request->completeAdress;
        }

        return $data;
    }
}
