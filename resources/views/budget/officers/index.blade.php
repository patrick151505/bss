@extends('layouts.vertical', [
    'title'         => 'Accountable Officers',
    'sub_title'     => 'Budget',
    'sub_title_url' => route('budget.index'),
    'tagline'       => 'Accountable Officers',
])

@section('content')

<div class="flex items-center justify-between mb-6">
    <p class="text-sm text-gray-500">Officers eligible to receive cash advances.</p>
    <button onclick="document.getElementById('add-officer-form').classList.toggle('hidden')"
        class="btn btn-sm btn-primary">
        <i class="mgc_add_line me-1"></i> Add Officer
    </button>
</div>

{{-- Add Form --}}
<div id="add-officer-form" class="hidden card mb-6">
    <div class="card-header"><h5 class="card-title">Add Accountable Officer</h5></div>
    <div class="card-body">
        <form method="POST" action="{{ route('budget.officers.store') }}">
            @csrf
            <div class="grid grid-cols-1 md:grid-cols-3 gap-3 mb-3">
                <div>
                    <label class="form-label">Full Name <span class="text-danger">*</span></label>
                    <input type="text" name="name" class="form-input" required value="{{ old('name') }}">
                </div>
                <div>
                    <label class="form-label">Position / Designation</label>
                    <input type="text" name="position" class="form-input" placeholder="e.g. Barangay Treasurer" value="{{ old('position') }}">
                </div>
                <div>
                    <label class="form-label">Fidelity Bond Amount (₱)</label>
                    <input type="number" name="fidelity_bond_amount" class="form-input" step="0.01" min="0" value="{{ old('fidelity_bond_amount', 0) }}">
                </div>
            </div>
            <div class="flex gap-2">
                <button type="submit" class="btn btn-primary">Save Officer</button>
                <button type="button" onclick="document.getElementById('add-officer-form').classList.add('hidden')"
                    class="btn btn-light">Cancel</button>
            </div>
        </form>
    </div>
</div>

{{-- Officers List --}}
<div class="card">
    <div class="card-body p-0">
        <table class="table table-sm mb-0">
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Position</th>
                    <th class="text-end">Fidelity Bond</th>
                    <th>Open CA</th>
                    <th>Status</th>
                    <th class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($officers as $officer)
                @php $openCa = $officer->openCashAdvance(); @endphp
                <tr>
                    <td class="font-medium">{{ $officer->name }}</td>
                    <td class="text-sm text-gray-500">{{ $officer->position ?: '—' }}</td>
                    <td class="text-end font-mono">₱{{ number_format($officer->fidelity_bond_amount, 2) }}</td>
                    <td>
                        @if($openCa)
                            <a href="{{ route('budget.cash-advances.show', $openCa) }}"
                                class="badge bg-warning/15 text-warning hover:underline text-xs">
                                {{ $openCa->ca_no }}
                                @if($openCa->isOverdue()) <span class="ms-1 text-danger">(Overdue)</span> @endif
                            </a>
                        @else
                            <span class="text-xs text-gray-400">None</span>
                        @endif
                    </td>
                    <td>
                        @if($officer->is_active)
                            <span class="badge bg-success/15 text-success">Active</span>
                        @else
                            <span class="badge bg-gray-100 text-gray-500">Inactive</span>
                        @endif
                    </td>
                    <td class="text-end">
                        <button onclick="openEditModal({{ $officer->id }}, @json($officer->name), @json($officer->position), {{ $officer->fidelity_bond_amount }}, {{ $officer->is_active ? 1 : 0 }})"
                            class="btn btn-xs btn-light">Edit</button>

                        <form method="POST" action="{{ route('budget.officers.destroy', $officer) }}" class="inline"
                            onsubmit="return confirm('Remove {{ addslashes($officer->name) }}?')">
                            @csrf @method('DELETE')
                            <button type="submit" class="btn btn-xs btn-danger-light">Remove</button>
                        </form>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="6" class="text-center text-gray-400 py-10">
                        No accountable officers added yet.
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

{{-- Edit Modal --}}
<div id="edit-officer-modal" class="hidden fixed inset-0 z-50 flex items-center justify-center bg-black/50">
    <div class="bg-white dark:bg-gray-900 rounded-xl shadow-xl w-full max-w-md p-6">
        <h5 class="font-semibold mb-4">Edit Officer</h5>
        <form method="POST" id="edit-officer-form">
            @csrf @method('PUT')
            <div class="mb-3">
                <label class="form-label">Full Name <span class="text-danger">*</span></label>
                <input type="text" name="name" id="edit-name" class="form-input" required>
            </div>
            <div class="mb-3">
                <label class="form-label">Position</label>
                <input type="text" name="position" id="edit-position" class="form-input">
            </div>
            <div class="mb-3">
                <label class="form-label">Fidelity Bond (₱)</label>
                <input type="number" name="fidelity_bond_amount" id="edit-bond" class="form-input" step="0.01" min="0">
            </div>
            <div class="mb-4 flex items-center gap-2">
                <input type="checkbox" name="is_active" id="edit-active" value="1" class="form-check">
                <label for="edit-active" class="form-check-label">Active</label>
            </div>
            <div class="flex gap-2 justify-end">
                <button type="button" onclick="document.getElementById('edit-officer-modal').classList.add('hidden')"
                    class="btn btn-light">Cancel</button>
                <button type="submit" class="btn btn-primary">Update</button>
            </div>
        </form>
    </div>
</div>

@endsection

@push('scripts')
<script>
function openEditModal(id, name, position, bond, isActive) {
    const base = "{{ url('budget/officers') }}";
    document.getElementById('edit-officer-form').action = base + '/' + id;
    document.getElementById('edit-name').value = name;
    document.getElementById('edit-position').value = position || '';
    document.getElementById('edit-bond').value = bond;
    document.getElementById('edit-active').checked = !!isActive;
    document.getElementById('edit-officer-modal').classList.remove('hidden');
}
</script>
@endpush
