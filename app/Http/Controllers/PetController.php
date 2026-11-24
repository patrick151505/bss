<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\Citizen;
use App\Models\Pet;
use App\Models\PetCategory;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * Citizen → Pets: pets owned by citizens, with category, photo and details.
 */
class PetController extends Controller
{
    public function index(Request $request)
    {
        $filters = $request->only(['search', 'category', 'status', 'vaccine']);

        $pets = Pet::with(['citizen:id,fname,mname,lname,suffix,address,profile,qrcode', 'citizen.addressZone', 'category'])
            ->search($filters['search'] ?? null)
            ->when($filters['category'] ?? null, fn ($q, $v) => $q->where('category_id', (int) $v))
            ->when($filters['status'] ?? null, fn ($q, $v) => $q->where('status', $v))
            ->when(($filters['vaccine'] ?? null) === 'due', fn ($q) => $q->where('status', 'active')->where(fn ($w) => $w
                ->whereNull('last_vaccinated_on')
                ->orWhereDate('last_vaccinated_on', '<=', today()->subDays(Pet::VACCINE_VALID_DAYS))))
            ->orderByDesc('id')
            ->paginate(24)
            ->withQueryString();

        // Count per category (active pets), for the summary chips.
        $byCategory = Pet::where('status', 'active')
            ->selectRaw('category_id, COUNT(*) c')->groupBy('category_id')->pluck('c', 'category_id');
        $categories = PetCategory::ordered()->get();

        $stats = [
            'total'   => (int) $byCategory->sum(),
            'owners'  => Pet::where('status', 'active')->distinct('citizen_id')->count('citizen_id'),
            'due'     => Pet::where('status', 'active')->where(fn ($w) => $w->whereNull('last_vaccinated_on')
                            ->orWhereDate('last_vaccinated_on', '<=', today()->subDays(Pet::VACCINE_VALID_DAYS)))->count(),
        ];

        return view('pets.index', compact('pets', 'filters', 'categories', 'byCategory', 'stats'));
    }

    public function create(Request $request)
    {
        $pet   = new Pet(['status' => 'active']);
        $owner = $request->filled('citizen') ? Citizen::find((int) $request->citizen) : null;

        return view('pets.form', [
            'pet'        => $pet,
            'owner'      => $owner,
            'categories' => PetCategory::where('is_active', true)->ordered()->pluck('name'),
        ]);
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);
        $pet  = Pet::create($data + ['created_by' => auth()->id(), 'updated_by' => auth()->id()]);
        if ($request->hasFile('photo')) {
            $pet->savePhoto($request->file('photo'));
        }

        ActivityLog::record('created', 'pet', $pet->id,
            "Added pet {$pet->name} ({$pet->category?->name}) for {$pet->citizen?->full_name}");

        if ($request->input('after') === 'another') {
            return redirect()->route('pets.create', ['citizen' => $pet->citizen_id])
                ->with('success', "{$pet->name} saved. Add another pet for the same owner.");
        }
        return redirect()->route('pets.show', $pet)->with('success', "{$pet->name} saved.");
    }

    public function show(Pet $pet)
    {
        $pet->load(['citizen.addressZone', 'category']);
        $others = Pet::with('category')->where('citizen_id', $pet->citizen_id)->where('id', '!=', $pet->id)->get();

        return view('pets.show', compact('pet', 'others'));
    }

    public function edit(Pet $pet)
    {
        $pet->load(['citizen', 'category']);

        return view('pets.form', [
            'pet'        => $pet,
            'owner'      => $pet->citizen,
            'categories' => PetCategory::where('is_active', true)->ordered()->pluck('name'),
        ]);
    }

    public function update(Request $request, Pet $pet)
    {
        $data = $this->validated($request);
        $pet->fill($data + ['updated_by' => auth()->id()]);
        $changed = array_keys($pet->getDirty());
        $pet->save();

        if ($request->hasFile('photo')) {
            $pet->savePhoto($request->file('photo'));
            $changed[] = 'photo';
        } elseif ($request->boolean('remove_photo') && $pet->photo) {
            \Illuminate\Support\Facades\Storage::delete($pet->photo);
            $pet->update(['photo' => null]);
            $changed[] = 'photo';
        }

        $changed = array_diff($changed, ['updated_by', 'updated_at']);
        if ($changed) {
            ActivityLog::record('updated', 'pet', $pet->id, "Updated pet {$pet->name}: " . implode(', ', array_map(
                fn ($f) => str_replace('_', ' ', $f), $changed)));
        }

        return redirect()->route('pets.show', $pet)->with('success', "{$pet->name} updated.");
    }

    public function destroy(Request $request, Pet $pet)
    {
        $name      = $pet->name;
        $owner     = $pet->citizen?->full_name;
        $citizenId = $pet->citizen_id;
        $pet->delete();

        ActivityLog::record('deleted', 'pet', null, "Removed pet {$name}" . ($owner ? " of {$owner}" : ''));

        // Removed from the citizen's profile → back there; otherwise to the list.
        return ($request->input('back') === 'citizen'
                ? redirect()->route('citizens.show', $citizenId)
                : redirect()->route('pets.index'))
            ->with('success', "{$name} removed.");
    }

    private function validated(Request $request): array
    {
        $request->validate([
            'citizen_id'         => ['required', 'integer', Rule::exists('eb_citizen', 'id')],
            'category'           => ['required', 'string', 'max:60'],
            'name'               => ['required', 'string', 'max:100'],
            'breed'              => ['nullable', 'string', 'max:100'],
            'sex'                => ['nullable', Rule::in(['male', 'female'])],
            'color'              => ['nullable', 'string', 'max:100'],
            'birth_date'         => ['nullable', 'date', 'before_or_equal:today'],
            'is_neutered'        => ['nullable', 'boolean'],
            'last_vaccinated_on' => ['nullable', 'date', 'before_or_equal:today'],
            'status'             => ['required', Rule::in(array_keys(Pet::STATUSES))],
            'notes'              => ['nullable', 'string', 'max:2000'],
            'photo'              => ['nullable', 'image', 'mimes:jpeg,png,jpg', 'max:4096'],
        ], [
            'citizen_id.required' => 'Choose the owner of the pet.',
            'category.required'   => 'Choose the kind of pet (Dog, Cat, …), or type a new one.',
            'name.required'       => 'Enter the pet’s name.',
            'birth_date.before_or_equal'         => 'The birth date can’t be in the future.',
            'last_vaccinated_on.before_or_equal' => 'The vaccination date can’t be in the future.',
        ]);

        // The owner must be someone this account can see (zone-limited accounts).
        abort_unless(Citizen::whereKey((int) $request->citizen_id)->exists(), 422, 'That owner is not in your zones.');

        return [
            'citizen_id'         => (int) $request->citizen_id,
            'category_id'        => PetCategory::resolveId($request->category),
            'name'               => trim($request->name),
            'breed'              => $request->breed ? trim($request->breed) : null,
            'sex'                => $request->sex ?: null,
            'color'              => $request->color ? trim($request->color) : null,
            'birth_date'         => $request->birth_date ?: null,
            'is_neutered'        => $request->boolean('is_neutered'),
            'last_vaccinated_on' => $request->last_vaccinated_on ?: null,
            'status'             => $request->status,
            'notes'              => $request->notes ? trim($request->notes) : null,
        ];
    }
}
