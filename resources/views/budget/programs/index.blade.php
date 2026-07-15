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
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase dark:text-gray-400 w-12">#</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase dark:text-gray-400">Program Name</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase dark:text-gray-400 w-48">Type</th>
                            <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase dark:text-gray-400 w-24">Status</th>
                            <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase dark:text-gray-400 w-28">Action</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                        @foreach($programs as $prog)
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-800 {{ !$prog->is_active ? 'opacity-50' : '' }}">
                            <td class="px-4 py-3 text-gray-500 dark:text-gray-400 tabular-nums">{{ $prog->sort_order }}</td>
                            <td class="px-4 py-3 font-medium text-gray-800 dark:text-gray-200">{{ $prog->name }}</td>
                            <td class="px-4 py-3">
                                @if($prog->special_fund)
                                    <span class="inline-flex items-center gap-1.5 py-1.5 px-3 rounded-full text-xs font-medium bg-orange-100 text-orange-800">
                                        <span class="w-1.5 h-1.5 inline-block bg-orange-400 rounded-full"></span>
                                        {{ \App\Models\BudgetProgram::SPECIAL_FUNDS[$prog->special_fund] }}
                                    </span>
                                @else
                                    <span class="inline-flex items-center gap-1.5 py-1.5 px-3 rounded-full text-xs font-medium bg-gray-100 text-gray-800">
                                        <span class="w-1.5 h-1.5 inline-block bg-gray-400 rounded-full"></span>
                                        Discretionary
                                    </span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-center">
                                @if($prog->is_active)
                                    <span class="inline-flex items-center gap-1.5 py-1.5 px-3 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                        <span class="w-1.5 h-1.5 inline-block bg-green-400 rounded-full"></span>
                                        Active
                                    </span>
                                @else
                                    <span class="inline-flex items-center gap-1.5 py-1.5 px-3 rounded-full text-xs font-medium bg-gray-100 text-gray-800">
                                        <span class="w-1.5 h-1.5 inline-block bg-gray-400 rounded-full"></span>
                                        Inactive
                                    </span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-right whitespace-nowrap">
                                <button class="btn btn-sm bg-dark/25 text-slate-900 dark:text-slate-200 hover:bg-dark hover:text-white"
                                    onclick="openEdit({{ $prog->id }}, @js($prog->name), {{ $prog->sort_order }}, {{ $prog->is_active ? 'true' : 'false' }})">
                                    <i class="mgc_edit_line"></i>
                                </button>
                                @if(!$prog->isMandatory())
                                <form id="del-prog-{{ $prog->id }}" method="POST" action="{{ route('budget.programs.destroy', $prog) }}" class="inline">
                                    @csrf @method('DELETE')
                                    <button type="button" class="btn btn-sm bg-danger/25 text-danger hover:bg-danger hover:text-white"
                                        onclick="swalDeleteProgram({{ $prog->id }}, @js($prog->name))">
                                        <i class="mgc_delete_line"></i>
                                    </button>
                                </form>
                                @endif
                            </td>
                        </tr>
                        @endforeach
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
                        <label class="text-gray-800 text-sm font-medium inline-block mb-2">Sort Order</label>
                        <input type="number" name="sort_order" class="form-input w-full" min="0" value="{{ old('sort_order', 99) }}">
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
                    <label class="text-gray-800 text-sm font-medium inline-block mb-2">Program Name</label>
                    <input type="text" name="name" id="edit_name" class="form-input w-full" required>
                </div>
                <div>
                    <label class="text-gray-800 text-sm font-medium inline-block mb-2">Sort Order</label>
                    <input type="number" name="sort_order" id="edit_sort_order" class="form-input w-full" min="0">
                </div>
                <div>
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
function openEdit(id, name, sort, isActive) {
    document.getElementById('edit-form').action = `{{ url('budget/programs') }}/${id}`;
    document.getElementById('edit_name').value = name;
    document.getElementById('edit_sort_order').value = sort;
    document.getElementById('edit_is_active').checked = isActive;
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
