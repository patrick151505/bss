@extends('layouts.vertical', [
    'title'         => 'Roles',
    'sub_title'     => 'Settings',
    'sub_title_url' => route('settings.index'),
    'tagline'       => 'Create roles and assign permissions per module.',
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
@if($errors->any())
<div class="mb-4 p-4 rounded-lg bg-danger/10 border border-danger/30 flex gap-3">
    <i class="mgc_alert_line text-danger text-xl mt-0.5 shrink-0"></i>
    <div>
        <p class="text-sm font-medium text-danger mb-1">Please fix the following errors:</p>
        <ul class="list-disc list-inside text-sm text-danger/80 space-y-0.5">
            @foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach
        </ul>
    </div>
</div>
@endif

<div class="grid grid-cols-12 gap-6">

    {{-- ── Left: Roles Table ── --}}
    <div class="col-span-12 lg:col-span-5">
        <div class="card">
            <div class="card-header flex items-center justify-between">
                <h5 class="card-title"><i class="mgc_user_security_line me-2 text-primary"></i>Roles</h5>
                <span class="text-xs text-gray-400">{{ $roles->count() }} roles</span>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700 text-sm">
                    <thead class="bg-gray-50 dark:bg-gray-700">
                        <tr>
                            <th class="px-5 py-3 text-left text-xs font-medium text-gray-500 uppercase dark:text-gray-400">Role Name</th>
                            <th class="px-5 py-3 text-center text-xs font-medium text-gray-500 uppercase dark:text-gray-400 w-24">Perms</th>
                            <th class="px-5 py-3 text-center text-xs font-medium text-gray-500 uppercase dark:text-gray-400 w-20">Users</th>
                            <th class="px-5 py-3 text-right text-xs font-medium text-gray-500 uppercase dark:text-gray-400 w-20">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                        @forelse($roles as $role)
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/40" id="role-row-{{ $role->id }}">
                            <td class="px-5 py-3 font-medium text-gray-800 dark:text-gray-100">
                                {{ $role->name }}
                            </td>
                            <td class="px-5 py-3 text-center">
                                <span class="inline-flex px-2 py-0.5 rounded-full text-xs font-semibold bg-primary/10 text-primary">
                                    {{ $role->permissions->count() }}
                                </span>
                            </td>
                            <td class="px-5 py-3 text-center">
                                <span class="text-sm {{ $role->users_count > 0 ? 'font-semibold text-gray-700 dark:text-gray-200' : 'text-gray-400' }}">
                                    {{ $role->users_count }}
                                </span>
                            </td>
                            <td class="px-5 py-3 text-right">
                                <div class="flex justify-end gap-1">
                                    <button onclick="editRole({{ $role->id }}, {{ json_encode($role->name) }}, {{ json_encode($role->permissions->pluck('name')) }})"
                                            class="p-1.5 rounded text-gray-400 hover:text-primary hover:bg-primary/10 transition" title="Edit Role">
                                        <i class="mgc_edit_line text-base"></i>
                                    </button>
                                    @if($role->users_count === 0)
                                    <form action="{{ route('roles.destroy', $role) }}" method="POST"
                                          onsubmit="return confirm('Delete role \'{{ $role->name }}\'?')">
                                        @csrf @method('DELETE')
                                        <button type="submit"
                                                class="p-1.5 rounded text-gray-400 hover:text-danger hover:bg-danger/10 transition" title="Delete Role">
                                            <i class="mgc_delete_line text-base"></i>
                                        </button>
                                    </form>
                                    @else
                                    <span class="p-1.5 text-gray-200 dark:text-gray-700 cursor-not-allowed" title="Cannot delete — users assigned">
                                        <i class="mgc_delete_line text-base"></i>
                                    </span>
                                    @endif
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="4" class="px-5 py-12 text-center text-gray-400">
                                <i class="mgc_user_security_line text-4xl mb-2 block opacity-30"></i>
                                No roles yet. Create one on the right.
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    {{-- ── Right: Create / Edit Form ── --}}
    <div class="col-span-12 lg:col-span-7">
        <div class="card" id="role-form-card">
            <div class="card-header flex items-center justify-between">
                <h5 class="card-title" id="role-form-title">
                    <i class="mgc_add_circle_line me-2 text-primary"></i>Create Role
                </h5>
                <button onclick="cancelRoleEdit()" id="role-cancel-btn"
                        class="hidden text-xs text-gray-400 hover:text-gray-600 flex items-center gap-1">
                    <i class="mgc_close_line"></i> Cancel
                </button>
            </div>
            <div class="p-5">
                <form id="role-form" action="{{ route('roles.store') }}" method="POST" class="space-y-5">
                    @csrf
                    <input type="hidden" name="_method" id="role-form-method" value="POST">

                    <div>
                        <label class="form-label">Role Name <span class="text-danger">*</span></label>
                        <input type="text" name="name" id="inp-role-name"
                               class="form-input" placeholder="e.g. Secretary, Treasurer"
                               required maxlength="100">
                    </div>

                    <div>
                        <label class="form-label mb-2 block">Permissions</label>
                        <div class="flex gap-2 mb-3">
                            <button type="button" onclick="selectAll(true)"
                                    class="text-xs px-3 py-1.5 rounded border border-primary text-primary hover:bg-primary/10 transition">
                                Select All
                            </button>
                            <button type="button" onclick="selectAll(false)"
                                    class="text-xs px-3 py-1.5 rounded border border-gray-300 text-gray-500 hover:bg-gray-50 dark:hover:bg-gray-800 transition">
                                Deselect All
                            </button>
                        </div>

                        <div class="space-y-4 max-h-[480px] overflow-y-auto pr-1">
                            @foreach($groups as $moduleName => $permissions)
                            <div class="border border-gray-200 dark:border-gray-700 rounded-lg overflow-hidden">
                                <div class="flex items-center justify-between px-4 py-2.5 bg-gray-50 dark:bg-gray-700/50">
                                    <p class="text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase tracking-wide">
                                        {{ $moduleName }}
                                    </p>
                                    <button type="button"
                                            onclick="toggleModule('{{ Str::slug($moduleName) }}')"
                                            class="text-xs text-primary hover:underline">toggle</button>
                                </div>
                                <div class="px-4 py-3 flex flex-wrap gap-3">
                                    @foreach($permissions as $perm)
                                    @php $action = explode('.', $perm->name)[1]; @endphp
                                    <label class="perm-module-{{ Str::slug($moduleName) }} flex items-center gap-2 cursor-pointer select-none">
                                        <input type="checkbox"
                                               name="permissions[]"
                                               value="{{ $perm->name }}"
                                               class="perm-checkbox rounded border-gray-300 text-primary focus:ring-primary"
                                               id="perm-{{ $perm->id }}">
                                        <span class="text-sm text-gray-700 dark:text-gray-200 capitalize">
                                            {{ $action }}
                                        </span>
                                    </label>
                                    @endforeach
                                </div>
                            </div>
                            @endforeach
                        </div>
                    </div>

                    <button type="submit" id="role-submit-btn" class="btn bg-primary text-white w-full">
                        <i class="mgc_add_circle_line me-1"></i> Create Role
                    </button>
                </form>
            </div>
        </div>
    </div>

</div>

    </div>

</div>

@endsection

@push('inline-scripts')
<script>
let editingRoleId = null;

function editRole(id, name, perms) {
    editingRoleId = id;

    const form = document.getElementById('role-form');
    form.action = `{{ url('roles') }}/${id}`;
    document.getElementById('role-form-method').value = 'PUT';
    document.getElementById('inp-role-name').value = name;

    // Reset + set permissions
    document.querySelectorAll('.perm-checkbox').forEach(cb => {
        cb.checked = perms.includes(cb.value);
    });

    document.getElementById('role-form-title').innerHTML = '<i class="mgc_edit_line me-2 text-warning"></i>Edit Role';
    document.getElementById('role-submit-btn').innerHTML = '<i class="mgc_save_line me-1"></i> Save Changes';
    document.getElementById('role-submit-btn').className = 'btn bg-warning text-white w-full';
    document.getElementById('role-cancel-btn').classList.remove('hidden');

    document.getElementById('role-form-card').scrollIntoView({ behavior: 'smooth', block: 'start' });
}

function cancelRoleEdit() {
    editingRoleId = null;
    const form = document.getElementById('role-form');
    form.action = '{{ route('roles.store') }}';
    document.getElementById('role-form-method').value = 'POST';
    form.reset();

    document.getElementById('role-form-title').innerHTML = '<i class="mgc_add_circle_line me-2 text-primary"></i>Create Role';
    document.getElementById('role-submit-btn').innerHTML = '<i class="mgc_add_circle_line me-1"></i> Create Role';
    document.getElementById('role-submit-btn').className = 'btn bg-primary text-white w-full';
    document.getElementById('role-cancel-btn').classList.add('hidden');
}

function selectAll(state) {
    document.querySelectorAll('.perm-checkbox').forEach(cb => cb.checked = state);
}

function toggleModule(slug) {
    const checkboxes = document.querySelectorAll(`.perm-module-${slug} .perm-checkbox`);
    const anyUnchecked = [...checkboxes].some(cb => !cb.checked);
    checkboxes.forEach(cb => cb.checked = anyUnchecked);
}
</script>
@endpush
