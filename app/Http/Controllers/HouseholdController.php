<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
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

    // Cache key for the KPI strip. Cleared by touchKpi() on every write so the
    // numbers never go stale after a household changes.
    private const KPI_CACHE_KEY = 'households.kpi';

    public function kpi()
    {
        // Four full-table counts on every page load; cached briefly because the
        // figures only move when a household/family/member changes, and those
        // paths bust the key explicitly.
        $payload = Cache::remember(self::KPI_CACHE_KEY, now()->addMinutes(10), function () {
            $households = Household::where('is_active', 1)->count();
            $families   = Family::count();

            // One grouped query instead of two separate counts over the same rows.
            $byHead = Citizen::whereNotNull('familyId')
                ->selectRaw('isHead, COUNT(*) as c')
                ->groupBy('isHead')
                ->pluck('c', 'isHead');

            $members = (int) ($byHead[0] ?? 0);
            $heads   = (int) ($byHead[1] ?? 0) + (int) ($byHead[2] ?? 0);
            $people  = $members + $heads;

            return [
                'households' => $households,
                'families'   => $families,
                'members'    => $people,
                'avg'        => $households > 0 ? round($people / $households, 1) : 0,
            ];
        });

        return response()->json(['success' => true] + $payload);
    }

    // Drop the cached KPI figures after any write that changes them.
    private function touchKpi(): void
    {
        Cache::forget(self::KPI_CACHE_KEY);

        // The picker's cached result counts are keyed by search term, so they
        // cannot all be listed. Their 2-minute TTL bounds the staleness, and
        // the unsearched count (the common case) is cleared here explicitly.
        Cache::forget('hh.picker.count.' . md5(''));
    }

    // ─── Paginated list (AJAX) ────────────────────────────────────────────────

    public function retrieve(Request $request)
    {
        $page      = max(1, (int) $request->input('page', 1));
        $limit     = (int) $request->input('limit', 6);
        $addressId = $request->input('addressId');
        $search    = $request->input('search');

        // Archived households are hidden unless explicitly asked for.
        $archived = filter_var($request->input('archived', false), FILTER_VALIDATE_BOOLEAN);

        $query = Household::with(['address', 'householdHead', 'families'])
            ->where('is_active', $archived ? 0 : 1)
            ->when($addressId, fn($q) => $q->where('addressId', $addressId))
            ->when($search, function ($q) use ($search) {
                $q->where(function ($q) use ($search) {
                    $q->where('phaseStreet', 'like', "%$search%")
                      ->orWhere('no', 'like', "%$search%")
                      ->orWhere('internal', 'like', "%$search%")
                      ->orWhere('completeAdress', 'like', "%$search%")
                      ->orWhereHas('address', fn($q) => $q->where('description', 'like', "%$search%"))
                      ->orWhereHas('householdHead', fn($q) => $q->searchName($search))
                      // Also match ANY resident of the household, not just its
                      // head — searching a member's name previously returned
                      // nothing even though they live there.
                      ->orWhereHas('families.members', fn($q) => $q->searchName($search))
                      ->orWhereHas('families.head', fn($q) => $q->searchName($search));
                });
            })
            ->orderByDesc('date_created');

        $total      = $query->count();
        $households = $query->skip(($page - 1) * $limit)->take($limit)->get();

        // Member counts for the whole page in ONE grouped query. Previously this
        // ran a COUNT per household inside the map below (an N+1).
        $allFamilyIds = $households->flatMap(fn (Household $h) => $h->families->pluck('id'))->all();
        $memberCounts = empty($allFamilyIds)
            ? collect()
            : Citizen::whereIn('familyId', $allFamilyIds)
                ->where('isHead', 0)
                ->selectRaw('familyId, COUNT(*) as c')
                ->groupBy('familyId')
                ->pluck('c', 'familyId');

        $data = $households->map(function (Household $h) use ($memberCounts) {
            $memberCount = $h->families->sum(fn ($f) => (int) ($memberCounts[$f->id] ?? 0));

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

        $showIncome = \App\Support\IncomeClass::visibleTo(auth()->user());

        $families = $household->families->map(function (Family $f) use ($household, $showIncome) {
            $head = $f->head;
            $size = $f->members->count() + ($head ? 1 : 0);
            return [
                // Only when Income Classification is on and the user may see income.
                'income'           => $showIncome ? \App\Support\IncomeClass::describe($f, $size) + [
                    'options' => \App\Support\IncomeClass::options($size),
                ] : null,
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

    // ─── Family income (optional; Income Classification) ─────────────────────
    // Either an exact monthly amount or, when that is unknown, a picked class.
    // Clearing both marks the family "not assessed". The log records the class
    // change only — never the amount.

    public function updateFamilyIncome(Request $request)
    {
        abort_unless(\App\Support\IncomeClass::enabled(), 404);

        $request->validate([
            'family_id'      => 'required|integer|exists:eb_family,id',
            'monthly_income' => 'nullable|numeric|min:0|max:100000000',
            'income_class'   => 'nullable|in:' . implode(',', array_keys(\App\Support\IncomeClass::BANDS)),
        ]);

        $family = Family::with(['head', 'members', 'household'])->findOrFail($request->family_id);
        $size   = $family->members->count() + ($family->head ? 1 : 0);
        $before = \App\Support\IncomeClass::describe($family, $size)['label'] ?? 'Not assessed';

        $amount = $request->filled('monthly_income') ? $request->input('monthly_income') : null;
        $family->update([
            'monthly_income'    => $amount,
            'income_class'      => $amount === null && $request->filled('income_class') ? $request->input('income_class') : null,
            'income_updated_at' => now(),
            'income_updated_by' => auth()->id(),
        ]);

        $info  = \App\Support\IncomeClass::describe($family->fresh(), $size);
        $after = $info['label'] ?? 'Not assessed';
        ActivityLog::record('income_updated', 'household', $family->householdId,
            "Income class for family {$family->formatted_id} (" . ($family->head?->full_name ?? '—') . "): {$before} → {$after}"
        );

        return response()->json(['success' => true, 'income' => $info + ['options' => \App\Support\IncomeClass::options($size)]]);
    }

    // ─── Create household ─────────────────────────────────────────────────────

    public function store(Request $request)
    {
        $request->validate([
            'addressId'  => 'required|integer|exists:eb_address,id',
            'citizenId'  => 'required|integer|exists:eb_citizen,id',
        ]);

        // The head must not already belong to a family, or they would be moved
        // out of their current household silently.
        $head = Citizen::find($request->citizenId);
        if ($head && $head->familyId) {
            return response()->json([
                'success' => false,
                'message' => 'That citizen already belongs to a family. Remove them from it first.',
            ]);
        }

        $isSub = (int) $request->input('is_sub');

        // Duplicate check
        $exists = $this->duplicateCheck($request, $isSub);
        if ($exists) {
            return response()->json(['success' => false, 'message' => 'This address already exists.']);
        }

        if (!$request->confirmAddress) {
            return response()->json(['success' => false, 'noConfirm' => true]);
        }

        // Household + family + citizen must land together: a failure part-way
        // through used to leave a household with no family, or a family whose
        // head was never marked as such.
        $household = DB::transaction(function () use ($request, $isSub) {
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

            return $household;
        });

        $this->touchKpi();

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
        $request->validate([
            'addressId' => 'required|integer|exists:eb_address,id',
            'edit_id'   => 'required|integer|exists:eb_household,id',
        ]);

        $isSub = (int) $request->input('is_sub');

        if (!$request->confirmAddress) {
            return response()->json(['success' => false, 'noConfirm' => true]);
        }

        $data = $this->buildAddressData($request, $isSub);
        $data['date_lastupdated'] = now();

        Household::where('id', $request->edit_id)->update($data);

        $household = Household::find($request->edit_id);
        ActivityLog::record('updated', 'household', $household?->id,
            'Address updated for household ' . ($household?->formatted_id ?? $request->edit_id) . '.'
        );

        return response()->json(['success' => true]);
    }

    // ─── Add family head to existing household ────────────────────────────────

    public function storeFamily(Request $request)
    {
        $request->validate([
            'householdId' => 'required|integer|exists:eb_household,id',
            'citizenId'   => 'required|integer|exists:eb_citizen,id',
        ]);

        $householdId = (int) $request->householdId;
        $citizenId   = (int) $request->citizenId;

        $citizen = Citizen::find($citizenId);

        if ($citizen->familyId) {
            return response()->json(['success' => false, 'message' => 'This citizen is already assigned to a family. Remove the current assignment first.']);
        }

        DB::transaction(function () use ($householdId, $citizenId, $citizen) {
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
        });

        $this->touchKpi();

        $household = Household::find($householdId);
        ActivityLog::record('updated', 'household', $householdId,
            "Family head {$citizen->full_name} added to household " . ($household?->formatted_id ?? $householdId) . '.'
        );

        return response()->json(['success' => true]);
    }

    // ─── Add member to a family ───────────────────────────────────────────────

    public function storeFamilyMember(Request $request)
    {
        $request->validate([
            'familyId'   => 'required|integer|exists:eb_family,id',
            'citizenId'  => 'required|integer|exists:eb_citizen,id',
            'relationId' => 'required|integer|exists:eb_relation,id',
        ]);

        $citizen = Citizen::find($request->citizenId);

        if ($citizen->familyId) {
            return response()->json(['success' => false, 'message' => 'This citizen is already assigned to a family.']);
        }

        $citizen->update([
            'familyId'          => (int) $request->familyId,
            'isHead'            => 0,
            'relationId'        => (int) $request->relationId,
            'date_last_updated' => now(),
        ]);

        $this->touchKpi();

        $family = Family::find($request->familyId);
        ActivityLog::record('updated', 'household', $family?->householdId,
            "{$citizen->full_name} added as a member of family #{$request->familyId}."
        );

        return response()->json(['success' => true]);
    }

    // ─── Remove family head ───────────────────────────────────────────────────

    public function removeFamilyHead(Request $request)
    {
        $request->validate([
            'familyId'  => 'required|integer|exists:eb_family,id',
            'citizenId' => 'required|integer|exists:eb_citizen,id',
        ]);

        $familyId  = (int) $request->familyId;
        $citizenId = (int) $request->citizenId;

        $family  = Family::find($familyId);
        $citizen = Citizen::find($citizenId);

        // The citizen must actually be the head of THIS family. Without this a
        // crafted request could detach any citizen in the system.
        if ((int) $citizen->familyId !== $familyId || (int) $family->citizenId !== $citizenId) {
            return response()->json([
                'success' => false,
                'message' => 'That citizen is not the head of this family.',
            ], 422);
        }

        $memberCount = Citizen::where('familyId', $familyId)->where('isHead', 0)->count();
        if ($memberCount > 0) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot remove: family still has ' . $memberCount . ' member(s). Remove all members first.',
            ]);
        }

        $household = Household::find($family->householdId);

        // Clearing the household head, deleting the family and detaching the
        // citizen must succeed or fail together — a partial run left a deleted
        // family with a citizen still pointing at it.
        DB::transaction(function () use ($family, $household, $citizenId) {
            if ($household && (int) $household->citizenHeadId === $citizenId) {
                $household->update(['citizenHeadId' => null]);
            }

            $family->delete();

            Citizen::where('id', $citizenId)->update([
                'familyId'          => null,
                'isHead'            => 0,
                'relationId'        => null,
                'date_last_updated' => now(),
            ]);
        });

        $this->touchKpi();

        ActivityLog::record('updated', 'household', $household?->id,
            "Family head {$citizen->full_name} removed from household " . ($household?->formatted_id ?? '—') . '.'
        );

        return response()->json(['success' => true]);
    }

    // ─── Remove family member ─────────────────────────────────────────────────

    public function removeFamilyMember(Request $request)
    {
        $request->validate([
            'memberId' => 'required|integer|exists:eb_citizen,id',
            'familyId' => 'nullable|integer|exists:eb_family,id',
        ]);

        $member = Citizen::find($request->memberId);

        // Previously this detached ANY citizen id that was posted, with no check
        // that they belonged to a family at all — let alone the one being edited.
        if (!$member->familyId) {
            return response()->json([
                'success' => false,
                'message' => 'That citizen does not belong to a family.',
            ], 422);
        }

        // When the caller says which family it is editing, the member must be in it.
        if ($request->filled('familyId') && (int) $member->familyId !== (int) $request->familyId) {
            return response()->json([
                'success' => false,
                'message' => 'That citizen is not a member of this family.',
            ], 422);
        }

        // Heads are removed through removeFamilyHead, which also tidies up the
        // family row; detaching one here would orphan it.
        if ((int) $member->isHead !== 0) {
            return response()->json([
                'success' => false,
                'message' => 'That citizen is a family head — remove them as a head instead.',
            ], 422);
        }

        $family = Family::find($member->familyId);

        $member->update([
            'familyId'          => null,
            'isHead'            => 0,
            'relationId'        => null,
            'date_last_updated' => now(),
        ]);

        $this->touchKpi();

        ActivityLog::record('updated', 'household', $family?->householdId,
            "{$member->full_name} removed from family #" . ($family?->id ?? '—') . '.'
        );

        return response()->json(['success' => true]);
    }

    // ─── Set household head (switch among family heads) ──────────────────────

    public function setHouseholdHead(Request $request)
    {
        $request->validate([
            'householdId' => 'required|integer|exists:eb_household,id',
            'citizenId'   => 'required|integer|exists:eb_citizen,id',
        ]);

        $householdId = (int) $request->householdId;
        $citizenId   = (int) $request->citizenId;

        $household = Household::find($householdId);
        $citizen   = Citizen::find($citizenId);

        // The new head must be a family head inside THIS household — otherwise
        // any citizen could be promoted into a household they don't live in.
        $family = Family::where('householdId', $householdId)
            ->where('citizenId', $citizenId)
            ->first();

        if (!$family || (int) $citizen->familyId !== (int) $family->id) {
            return response()->json([
                'success' => false,
                'message' => 'That citizen is not a family head in this household.',
            ], 422);
        }

        // Demote-then-promote must be atomic: a failure between the two left the
        // household with two heads, or none at all.
        DB::transaction(function () use ($household, $householdId, $citizenId) {
            if ($household?->citizenHeadId) {
                Citizen::where('id', $household->citizenHeadId)->update(['isHead' => 1]);
            }

            Citizen::where('id', $citizenId)->update(['isHead' => 2]);
            Household::where('id', $householdId)->update([
                'citizenHeadId'   => $citizenId,
                'date_lastupdated'=> now(),
            ]);
        });

        $this->touchKpi();

        ActivityLog::record('updated', 'household', $householdId,
            "Household head changed to {$citizen->full_name} for " . ($household?->formatted_id ?? $householdId) . '.'
        );

        return response()->json(['success' => true]);
    }

    // ─── Citizen picker (AJAX HTML partial) ──────────────────────────────────

    public function filterCitizens(Request $request)
    {
        $search = $request->input('search');
        $page   = max(1, (int) $request->input('page', 1));
        $limit  = 8;

        $base = Citizen::query()
            ->where('is_active', 1)
            ->where('approval_status', 1)
            ->when($search, fn($q) => $q->searchName($search));

        // COUNT over a leading-wildcard LIKE has to scan every match (~145ms on
        // 23k rows), and the picker re-runs it on every keystroke. The result
        // only depends on the search term, so cache it briefly — paging through
        // the same search then costs nothing.
        $cacheKey = 'hh.picker.count.' . md5((string) $search);
        $total = Cache::remember($cacheKey, now()->addMinutes(2),
            fn () => (clone $base)->count());

        // Ordering is irrelevant to the count above, so it is applied only here.
        $citizens = (clone $base)
            ->with('addressZone')
            ->orderBy('lname')->orderBy('fname')
            ->skip(($page - 1) * $limit)->take($limit)->get();
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
                          ->orWhereHas('householdHead', fn($x) => $x->searchName($q));
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

    // ─── Archive / restore ────────────────────────────────────────────────────

    /**
     * Households are archived, never deleted — the family and member links are
     * part of the barangay's historical record. Mirrors how citizens are handled.
     */
    public function archive(Request $request)
    {
        $request->validate(['id' => 'required|integer|exists:eb_household,id']);

        $household = Household::with('families')->find($request->id);

        if (!$household->is_active) {
            return response()->json(['success' => false, 'message' => 'This household is already archived.'], 422);
        }

        // Archiving a household that still has residents would hide them from
        // the list while leaving them attached — make the caller detach first.
        $residents = Citizen::whereIn('familyId', $household->families->pluck('id'))->count();
        if ($residents > 0) {
            return response()->json([
                'success' => false,
                'message' => "Cannot archive: {$residents} resident(s) are still assigned. Remove them first.",
            ], 422);
        }

        $household->update([
            'is_active'        => false,
            'archived_at'      => now(),
            'archived_by'      => auth()->id(),
            'date_lastupdated' => now(),
        ]);

        $this->touchKpi();

        ActivityLog::record('archived', 'household', $household->id,
            "Household {$household->formatted_id} archived."
        );

        return response()->json(['success' => true]);
    }

    public function restore(Request $request)
    {
        $request->validate(['id' => 'required|integer|exists:eb_household,id']);

        $household = Household::find($request->id);

        $household->update([
            'is_active'        => true,
            'archived_at'      => null,
            'archived_by'      => null,
            'date_lastupdated' => now(),
        ]);

        $this->touchKpi();

        ActivityLog::record('restored', 'household', $household->id,
            "Household {$household->formatted_id} restored."
        );

        return response()->json(['success' => true]);
    }

    // ─── Export ───────────────────────────────────────────────────────────────

    /**
     * CSV of the household list, honouring the same filters as the on-screen
     * list so what you export is what you are looking at. Streamed rather than
     * built in memory, since this grows with the barangay.
     */
    public function export(Request $request)
    {
        $addressId = $request->input('addressId');
        $search    = $request->input('search');

        $query = Household::with(['address', 'householdHead', 'families.head', 'families.members'])
            ->where('is_active', 1)
            ->when($addressId, fn($q) => $q->where('addressId', $addressId))
            ->when($search, function ($q) use ($search) {
                $q->where(function ($q) use ($search) {
                    $q->where('phaseStreet', 'like', "%$search%")
                      ->orWhere('no', 'like', "%$search%")
                      ->orWhere('internal', 'like', "%$search%")
                      ->orWhere('completeAdress', 'like', "%$search%")
                      ->orWhereHas('address', fn($q) => $q->where('description', 'like', "%$search%"))
                      ->orWhereHas('householdHead', fn($q) => $q->searchName($search))
                      ->orWhereHas('families.members', fn($q) => $q->searchName($search))
                      ->orWhereHas('families.head', fn($q) => $q->searchName($search));
                });
            })
            ->orderByDesc('date_created');

        $filename = 'households-' . now()->format('Y-m-d-His') . '.csv';

        $headers = [
            'Content-Type'        => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
            'Cache-Control'       => 'no-store, no-cache',
        ];

        ActivityLog::record('exported', 'household', null, 'Household list exported to CSV.');

        return response()->stream(function () use ($query) {
            $out = fopen('php://output', 'w');
            // BOM so Excel opens UTF-8 names correctly.
            fwrite($out, "\xEF\xBB\xBF");

            fputcsv($out, [
                'Household ID', 'Zone / Area', 'Full Address', 'Internal Note',
                'Household Head', 'Families', 'Members', 'Total Residents',
                'Date Created', 'Last Updated',
            ]);

            $query->chunk(200, function ($households) use ($out) {
                foreach ($households as $h) {
                    $heads   = $h->families->filter(fn ($f) => $f->head)->count();
                    $members = $h->families->sum(fn ($f) => $f->members->count());

                    fputcsv($out, [
                        $h->formatted_id,
                        $h->address?->description ?? '',
                        $h->full_address,
                        $h->internal ?? '',
                        $h->householdHead?->full_name ?? '',
                        $h->families->count(),
                        $members,
                        $heads + $members,
                        $h->date_created?->format('Y-m-d') ?? '',
                        $h->date_lastupdated?->format('Y-m-d') ?? '',
                    ]);
                }
            });

            fclose($out);
        }, 200, $headers);
    }
}
