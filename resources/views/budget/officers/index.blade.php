@extends('layouts.vertical', [
    'title'         => 'Accountable Officers',
    'sub_title'     => 'Budget',
    'sub_title_url' => route('budget.index'),
    'tagline'       => 'Accountable Officers',
])

@section('content')

@if(session('success'))
<div class="mb-4 p-4 rounded-lg bg-success/10 border border-success/30 flex gap-3">
    <i class="mgc_check_circle_line text-success text-xl mt-0.5 shrink-0"></i>
    <p class="text-sm text-success font-medium">{{ session('success') }}</p>
</div>
@endif
@if(session('error'))
<div class="mb-4 p-4 rounded-lg bg-danger/10 border border-danger/30 flex gap-3">
    <i class="mgc_alert_line text-danger text-xl mt-0.5 shrink-0"></i>
    <p class="text-sm text-danger font-medium">{{ session('error') }}</p>
</div>
@endif

<div class="flex items-center justify-between mb-6">
    <p class="text-sm text-gray-500">Officers eligible to receive cash advances.</p>
    @can('budget.create')
    <button onclick="document.getElementById('add-officer-form').classList.toggle('hidden')"
        class="btn bg-primary text-white flex items-center gap-2">
        <i class="mgc_add_line"></i> Add Officer
    </button>
    @endcan
</div>

{{-- Add Form --}}
<div id="add-officer-form" class="hidden card mb-6">
    <div class="card-header"><h5 class="card-title">Add Accountable Officer</h5></div>
    <div class="p-6">
        <form method="POST" action="{{ route('budget.officers.store') }}">
            @csrf
            <div class="grid grid-cols-1 md:grid-cols-3 gap-3 mb-4">
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
                <button type="submit" class="btn bg-primary text-white">Save Officer</button>
                <button type="button" onclick="document.getElementById('add-officer-form').classList.add('hidden')"
                    class="btn bg-dark/25 text-slate-900 dark:text-slate-200 hover:bg-dark hover:text-white">Cancel</button>
            </div>
        </form>
    </div>
</div>

{{-- Officers List --}}
<div class="card overflow-hidden">
    <div class="overflow-x-auto">
        <table class="min-w-full text-sm">
            <thead class="bg-gray-50 dark:bg-gray-700">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase dark:text-gray-400">Name</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase dark:text-gray-400">Position</th>
                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase dark:text-gray-400">Fidelity Bond</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase dark:text-gray-400">Open CA</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase dark:text-gray-400">Status</th>
                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase dark:text-gray-400">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                @forelse($officers as $officer)
                @php $openCa = $officer->openCashAdvance(); @endphp
                <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/40">
                    <td class="px-6 py-3 font-medium text-gray-800 dark:text-gray-100">{{ $officer->name }}</td>
                    <td class="px-6 py-3 text-gray-500 dark:text-gray-400">{{ $officer->position ?: '—' }}</td>
                    <td class="px-6 py-3 text-right font-mono text-gray-700 dark:text-gray-300">₱{{ number_format($officer->fidelity_bond_amount, 2) }}</td>
                    <td class="px-6 py-3">
                        @if($openCa)
                            <a href="{{ route('budget.cash-advances.show', $openCa) }}"
                                class="inline-flex items-center gap-1 py-1 px-2.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800 hover:bg-yellow-200 transition">
                                {{ $openCa->ca_no }}
                                @if($openCa->isOverdue()) <span class="text-red-700 font-semibold">· Overdue</span> @endif
                            </a>
                        @else
                            <span class="text-xs text-gray-400">None</span>
                        @endif
                    </td>
                    <td class="px-6 py-3">
                        @if($officer->is_active)
                            <span class="inline-flex items-center gap-1.5 py-1 px-2.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                <span class="w-1.5 h-1.5 rounded-full bg-green-400 inline-block"></span>Active
                            </span>
                        @else
                            <span class="inline-flex items-center gap-1.5 py-1 px-2.5 rounded-full text-xs font-medium bg-gray-100 text-gray-500 dark:bg-gray-700 dark:text-gray-400">
                                <span class="w-1.5 h-1.5 rounded-full bg-gray-400 inline-block"></span>Inactive
                            </span>
                        @endif
                    </td>
                    <td class="px-6 py-3 text-right whitespace-nowrap">
                        <div class="inline-flex items-center gap-1.5">
                            @can('budget.edit')
                            <button onclick="openEditModal({{ $officer->id }}, @json($officer->name), @json($officer->position), {{ $officer->fidelity_bond_amount }}, {{ $officer->is_active ? 1 : 0 }})"
                                class="btn btn-sm bg-primary/10 text-primary hover:bg-primary hover:text-white"><i class="mgc_edit_line"></i></button>
                            @endcan
                            @can('budget.delete')
                            <form method="POST" action="{{ route('budget.officers.destroy', $officer) }}"
                                onsubmit="return confirm('Remove {{ addslashes($officer->name) }}?')">
                                @csrf @method('DELETE')
                                <button type="submit" class="btn btn-sm bg-danger/25 text-danger hover:bg-danger hover:text-white"><i class="mgc_delete_2_line"></i></button>
                            </form>
                            @endcan
                            @canany(['budget.edit', 'budget.delete'])@else
                            <span class="text-xs text-gray-400">—</span>
                            @endcanany
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="6" class="text-center text-gray-400 py-12">
                        <i class="mgc_user_3_line text-4xl mb-2 block opacity-30"></i>
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
                    class="btn bg-dark/25 text-slate-900 dark:text-slate-200 hover:bg-dark hover:text-white">Cancel</button>
                <button type="submit" class="btn bg-primary text-white">Update</button>
            </div>
        </form>
    </div>
</div>

@endsection

@push('inline-scripts')
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
