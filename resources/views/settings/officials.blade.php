@extends('layouts.vertical', [
    'title'         => 'Officials',
    'sub_title'     => 'Settings',
    'sub_title_url' => route('settings.index'),
    'tagline'       => 'Manage barangay officials shown on documents and certificates.',
    'mode'          => $mode ?? '',
    'demo'          => $demo ?? '',
])

@section('content')

<div class="grid grid-cols-12 gap-6">

    {{-- ── Settings Sub-nav ── --}}
    <div class="col-span-12 lg:col-span-3 xl:col-span-2">
        @include('settings.partials.subnav')
    </div>

    {{-- ── Settings Content ── --}}
    <div class="col-span-12 lg:col-span-9 xl:col-span-10">

{{-- Flash messages --}}
@if(session('success'))
<div class="mb-4 p-4 rounded-lg bg-success/10 border border-success/30 flex gap-3">
    <i class="mgc_check_circle_line text-success text-xl mt-0.5 shrink-0"></i>
    <p class="text-sm text-success font-medium">{{ session('success') }}</p>
</div>
@endif

@if($errors->any())
<div class="mb-4 p-4 rounded-lg bg-danger/10 border border-danger/30 flex gap-3">
    <i class="mgc_alert_line text-danger text-xl mt-0.5 shrink-0"></i>
    <div>
        <p class="text-sm font-medium text-danger mb-1">Please fix the following errors:</p>
        <ul class="list-disc list-inside text-sm text-danger/80 space-y-0.5">
            @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
</div>
@endif

<div class="grid grid-cols-12 gap-6">

    {{-- ── Left: Officials Table ── --}}
    <div class="col-span-12 lg:col-span-8 flex flex-col gap-6">
        <div class="card">
            <div class="card-header flex items-center justify-between">
                <h5 class="card-title">
                    <i class="mgc_group_line me-2 text-primary"></i>
                    Barangay Officials
                    <span class="ms-2 text-xs font-normal text-gray-500">({{ $officials->count() }} total)</span>
                </h5>
            </div>

            @if($officials->isEmpty())
                <div class="p-12 text-center text-gray-400">
                    <i class="mgc_group_line text-4xl mb-3 block"></i>
                    <p class="text-sm">No officials added yet. Use the form on the right to add one.</p>
                </div>
            @else
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                        <thead class="bg-gray-50 dark:bg-gray-800">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider w-8">#</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Name</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Position</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider hidden md:table-cell">Description</th>
                                <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider w-20">Status</th>
                                <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider w-24">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white dark:bg-gray-900 divide-y divide-gray-100 dark:divide-gray-800">
                            @foreach($officials as $official)
                            <tr class="{{ $official->is_active ? '' : 'opacity-50' }}">
                                <td class="px-4 py-3 text-xs text-gray-400">{{ $official->sort_order }}</td>
                                <td class="px-4 py-3">
                                    <p class="text-sm font-medium text-gray-900 dark:text-gray-100">{{ $official->name }}</p>
                                </td>
                                <td class="px-4 py-3">
                                    <span class="text-sm text-gray-700 dark:text-gray-300">{{ $official->position }}</span>
                                </td>
                                <td class="px-4 py-3 hidden md:table-cell">
                                    <span class="text-xs text-gray-500">{{ $official->description ?: '—' }}</span>
                                </td>
                                <td class="px-4 py-3 text-center">
                                    @if($official->is_active)
                                        <span class="px-2 py-0.5 rounded-full text-xs bg-success/15 text-success font-medium">Active</span>
                                    @else
                                        <span class="px-2 py-0.5 rounded-full text-xs bg-gray-100 dark:bg-gray-800 text-gray-500 font-medium">Inactive</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-center">
                                    <div class="flex items-center justify-center gap-2">
                                        <button type="button"
                                            class="edit-btn text-primary hover:text-primary/80 text-sm"
                                            data-id="{{ $official->id }}"
                                            data-name="{{ $official->name }}"
                                            data-position="{{ $official->position }}"
                                            data-description="{{ $official->description }}"
                                            data-sort="{{ $official->sort_order }}"
                                            data-active="{{ $official->is_active ? '1' : '0' }}"
                                            title="Edit">
                                            <i class="mgc_edit_2_line text-base"></i>
                                        </button>
                                        <form action="{{ route('officials.destroy', $official) }}" method="POST"
                                            onsubmit="return confirm('Remove {{ addslashes($official->name) }} from officials?')">
                                            @csrf @method('DELETE')
                                            <button type="submit"
                                                class="text-danger hover:text-danger/80 text-sm"
                                                title="Delete">
                                                <i class="mgc_delete_2_line text-base"></i>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </div>

    {{-- ── Right: Add / Edit Form ── --}}
    <div class="col-span-12 lg:col-span-4 flex flex-col gap-6">

        {{-- Add Form --}}
        <div class="card" id="add-card">
            <div class="card-header">
                <h5 class="card-title"><i class="mgc_add_circle_line me-2 text-primary"></i>Add Official</h5>
            </div>
            <form action="{{ route('officials.store') }}" method="POST" class="p-6 flex flex-col gap-4">
                @csrf

                <div>
                    <label class="form-label">Name <span class="text-danger">*</span></label>
                    <input type="text" name="name" value="{{ old('name') }}"
                        class="form-input @error('name') border-danger @enderror"
                        placeholder="e.g. Maria Santos">
                    @error('name') <p class="text-danger text-xs mt-1">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="form-label">Position <span class="text-danger">*</span></label>
                    <input type="text" name="position" value="{{ old('position') }}"
                        class="form-input @error('position') border-danger @enderror"
                        placeholder="e.g. Barangay Secretary">
                    @error('position') <p class="text-danger text-xs mt-1">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="form-label">Description</label>
                    <textarea name="description" rows="2"
                        class="form-input @error('description') border-danger @enderror"
                        placeholder="Optional short description">{{ old('description') }}</textarea>
                    @error('description') <p class="text-danger text-xs mt-1">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="form-label">Sort Order</label>
                    <input type="number" name="sort_order" value="{{ old('sort_order', 0) }}"
                        min="0" class="form-input" placeholder="0">
                    <p class="text-xs text-gray-400 mt-1">Lower numbers appear first.</p>
                </div>

                <button type="submit" class="btn bg-primary text-white w-full">
                    <i class="mgc_add_line me-1"></i> Add Official
                </button>
            </form>
        </div>

        {{-- Tip card --}}
        <div class="card bg-primary/5 border border-primary/20">
            <div class="p-4 flex gap-3">
                <i class="mgc_information_line text-primary text-lg shrink-0 mt-0.5"></i>
                <p class="text-xs text-gray-600 dark:text-gray-400">
                    Officials listed here appear on printed documents and certificates.
                    Use <strong>Sort Order</strong> to control their display sequence.
                    Inactive officials are hidden from documents.
                </p>
            </div>
        </div>

    </div>
</div>

    </div>

</div>

{{-- Edit Modal --}}
<div id="edit-modal" class="fixed inset-0 z-50 hidden flex items-center justify-center">
    <div class="absolute inset-0 bg-black/50" id="edit-modal-backdrop"></div>
    <div class="relative bg-white dark:bg-gray-900 rounded-xl shadow-xl w-full max-w-md mx-4 z-10">

        <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200 dark:border-gray-700">
            <h5 class="font-semibold text-gray-800 dark:text-gray-100">Edit Official</h5>
            <button type="button" id="close-edit-modal"
                class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-200">
                <i class="mgc_close_line text-xl"></i>
            </button>
        </div>

        <form id="edit-form" method="POST" class="p-6 flex flex-col gap-4">
            @csrf @method('PUT')

            <div>
                <label class="form-label">Name <span class="text-danger">*</span></label>
                <input type="text" id="edit-name" name="name"
                    class="form-input" required>
            </div>

            <div>
                <label class="form-label">Position <span class="text-danger">*</span></label>
                <input type="text" id="edit-position" name="position"
                    class="form-input" required>
            </div>

            <div>
                <label class="form-label">Description</label>
                <textarea id="edit-description" name="description" rows="2" class="form-input"
                    placeholder="Optional"></textarea>
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="form-label">Sort Order</label>
                    <input type="number" id="edit-sort" name="sort_order" min="0" class="form-input">
                </div>
                <div>
                    <label class="form-label">Status</label>
                    <select id="edit-active" name="is_active" class="form-select">
                        <option value="1">Active</option>
                        <option value="0">Inactive</option>
                    </select>
                </div>
            </div>

            <div class="flex gap-3 pt-2">
                <button type="submit" class="btn bg-primary text-white flex-1">
                    <i class="mgc_save_line me-1"></i> Save Changes
                </button>
                <button type="button" id="cancel-edit"
                    class="btn border-gray-300 dark:border-gray-600 text-gray-600 dark:text-gray-300">
                    Cancel
                </button>
            </div>
        </form>
    </div>
</div>

@endsection

@section('script')
<script>
window.addEventListener('DOMContentLoaded', () => {
    const modal    = document.getElementById('edit-modal');
    const form     = document.getElementById('edit-form');
    const backdrop = document.getElementById('edit-modal-backdrop');

    function openModal() { modal.classList.remove('hidden'); }
    function closeModal() { modal.classList.add('hidden'); }

    document.getElementById('close-edit-modal').addEventListener('click', closeModal);
    document.getElementById('cancel-edit').addEventListener('click', closeModal);
    backdrop.addEventListener('click', closeModal);
    document.addEventListener('keydown', e => { if (e.key === 'Escape') closeModal(); });

    document.querySelectorAll('.edit-btn').forEach(btn => {
        btn.addEventListener('click', () => {
            const id = btn.dataset.id;
            form.action = `/settings/officials/${id}`;

            document.getElementById('edit-name').value        = btn.dataset.name;
            document.getElementById('edit-position').value    = btn.dataset.position;
            document.getElementById('edit-description').value = btn.dataset.description || '';
            document.getElementById('edit-sort').value        = btn.dataset.sort;
            document.getElementById('edit-active').value      = btn.dataset.active;

            openModal();
        });
    });
});
</script>
@endsection
