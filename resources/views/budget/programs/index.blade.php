@extends('layouts.vertical', [
    'title'         => 'Budget Programs',
    'sub_title'     => 'Budget',
    'sub_title_url' => route('budget.index'),
    'tagline'       => 'Manage Programs / Projects / Activities (PPA)',
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

<div class="grid grid-cols-1 xl:grid-cols-3 gap-6">

    {{-- Program list --}}
    <div class="xl:col-span-2">
        <div class="card">
            <div class="card-header">
                <h4 class="card-title">Programs / PPAs</h4>
            </div>
            <div class="overflow-x-auto">
                <table class="table min-w-full text-sm">
                    <thead class="bg-gray-50 dark:bg-gray-700">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase dark:text-gray-400 w-16">Sec.</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase dark:text-gray-400">Program Name</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase dark:text-gray-400 w-48">Type</th>
                            <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase dark:text-gray-400 w-24">Status</th>
                            <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase dark:text-gray-400 w-28">Action</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                        @forelse($programs as $prog)
                            @include('budget.programs._row', ['prog' => $prog, 'depth' => 0])
                            @foreach($prog->children as $child)
                                @include('budget.programs._row', ['prog' => $child, 'depth' => 1])
                            @endforeach
                        @empty
                        <tr><td colspan="5" class="text-center py-12 text-gray-400">
                            <i class="mgc_box_3_line text-4xl mb-2 block opacity-30"></i>No programs yet.
                        </td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    {{-- Add Program --}}
    <div class="space-y-4">
        <div class="card">
            <div class="card-header">
                <h4 class="card-title">Add Program</h4>
            </div>
            <div class="p-4">
                <form method="POST" action="{{ route('budget.programs.store') }}">
                    @csrf
                    <div class="mb-4">
                        <label class="text-gray-800 text-sm font-medium inline-block mb-2">Program Name <span class="text-danger">*</span></label>
                        <input type="text" name="name" class="form-input w-full" required
                            placeholder="e.g. Environmental Services"
                            value="{{ old('name') }}">
                        @error('name')
                        <p class="text-danger text-xs mt-1">{{ $message }}</p>
                        @enderror
                    </div>
                    <div class="mb-4">
                        <label class="text-gray-800 text-sm font-medium inline-block mb-2">Parent Program</label>
                        <select name="parent_id" class="form-select w-full">
                            <option value="">— None (top-level program) —</option>
                            @foreach($parents as $parent)
                                <option value="{{ $parent->id }}" {{ old('parent_id') == $parent->id ? 'selected' : '' }}>
                                    {{ $parent->section_label ? $parent->section_label.'. ' : '' }}{{ $parent->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-4 flex gap-3">
                        <div class="w-24 shrink-0">
                            <label class="text-gray-800 text-sm font-medium inline-block mb-2">Section</label>
                            <input type="text" name="section_label" class="form-input w-full" maxlength="8" placeholder="A" value="{{ old('section_label') }}">
                        </div>
                        <div class="flex-1">
                            <label class="text-gray-800 text-sm font-medium inline-block mb-2">Sort Order</label>
                            <input type="number" name="sort_order" class="form-input w-full" min="0" value="{{ old('sort_order', 99) }}">
                        </div>
                    </div>
                    <button type="submit" class="btn bg-primary text-white w-full">
                        <i class="mgc_add_line me-1"></i> Add Program
                    </button>
                </form>
            </div>
        </div>

        <div class="bg-primary/10 text-sm text-primary rounded-md p-4 space-y-2">
            <p><strong>Mandatory programs</strong> (Dev Fund, SK, Calamity, GAD) cannot be deleted — their amounts are auto-computed from income estimates. You can rename them.</p>
            <p><strong>Discretionary programs</strong> can be freely added, renamed, or removed as long as they have no existing allocations.</p>
        </div>
    </div>
</div>

{{-- Edit Modal --}}
<div id="edit-modal" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/50 backdrop-blur-sm">
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg w-full max-w-md mx-4">
        <div class="flex justify-between items-center py-3 px-4 border-b dark:border-gray-700">
            <h3 class="font-bold text-gray-800 dark:text-white">Edit Program</h3>
            <button onclick="closeEdit()" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">
                <i class="mgc_close_line text-xl"></i>
            </button>
        </div>
        <form method="POST" id="edit-form" action="">
            @csrf @method('PUT')
            <div class="p-4 space-y-3">
                <div>
                    <label class="text-gray-800 dark:text-gray-200 text-sm font-medium inline-block mb-2">Program Name <span class="text-danger">*</span></label>
                    <input type="text" name="name" id="edit_name" class="form-input w-full" required>
                </div>
                <div>
                    <label class="text-gray-800 dark:text-gray-200 text-sm font-medium inline-block mb-2">Parent Program</label>
                    <select name="parent_id" id="edit_parent_id" class="form-select w-full">
                        <option value="">— None (top-level program) —</option>
                        @foreach($parents as $parent)
                            <option value="{{ $parent->id }}">{{ $parent->section_label ? $parent->section_label.'. ' : '' }}{{ $parent->name }}</option>
                        @endforeach
                    </select>
                    <p id="edit_parent_hint" class="text-xs text-gray-400 mt-1 hidden">
                        This program has sub-programs, so it must stay top-level.
                    </p>
                </div>
                <div class="flex gap-3">
                    <div class="w-28 shrink-0">
                        <label class="text-gray-800 dark:text-gray-200 text-sm font-medium inline-block mb-2">Section</label>
                        <input type="text" name="section_label" id="edit_section_label" class="form-input w-full" maxlength="8" placeholder="A">
                    </div>
                    <div class="flex-1">
                        <label class="text-gray-800 dark:text-gray-200 text-sm font-medium inline-block mb-2">Sort Order</label>
                        <input type="number" name="sort_order" id="edit_sort_order" class="form-input w-full" min="0">
                    </div>
                </div>
                <div>
                    {{-- Unchecked checkboxes are not posted, so send an explicit 0. --}}
                    <input type="hidden" name="is_active" value="0">
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input type="checkbox" name="is_active" id="edit_is_active" value="1" class="form-checkbox">
                        <span class="text-sm text-gray-800 dark:text-gray-200">Active</span>
                    </label>
                </div>
            </div>
            <div class="flex justify-end gap-2 py-3 px-4 border-t dark:border-gray-700">
                <button type="button" onclick="closeEdit()" class="btn bg-dark/25 text-slate-900 dark:text-slate-200 hover:bg-dark hover:text-white">Cancel</button>
                <button type="submit" class="btn bg-primary text-white">Update Program</button>
            </div>
        </form>
    </div>
</div>

@endsection

@push('inline-scripts')
<script>
function swalDeleteProgram(id, name) {
    Swal.fire({
        title: 'Delete Program?',
        text: `"${name}" will be permanently deleted.`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Yes, delete it',
        cancelButtonText: 'Cancel',
        confirmButtonColor: '#fa5c7c',
    }).then(result => {
        if (result.isConfirmed) document.getElementById('del-prog-' + id).submit();
    });
}
function openEdit(id, name, parentId, section, sort, isActive, hasChildren) {
    document.getElementById('edit-form').action = `{{ url('budget/programs') }}/${id}`;
    document.getElementById('edit_name').value          = name;
    document.getElementById('edit_section_label').value = section || '';
    document.getElementById('edit_sort_order').value    = sort;
    document.getElementById('edit_is_active').checked   = isActive;

    // Only two levels are supported: a program with sub-programs cannot
    // itself become one, and no program may be its own parent.
    const parentSel = document.getElementById('edit_parent_id');
    [...parentSel.options].forEach(opt => {
        opt.disabled = opt.value !== '' && (hasChildren || Number(opt.value) === id);
    });
    parentSel.value    = parentId === null ? '' : String(parentId);
    parentSel.disabled = hasChildren;
    document.getElementById('edit_parent_hint').classList.toggle('hidden', !hasChildren);

    const modal = document.getElementById('edit-modal');
    modal.classList.remove('hidden');
    modal.classList.add('flex');
}
function closeEdit() {
    const modal = document.getElementById('edit-modal');
    modal.classList.add('hidden');
    modal.classList.remove('flex');
}
document.getElementById('edit-modal').addEventListener('click', function(e) {
    if (e.target === this) closeEdit();
});
</script>
@endpush
