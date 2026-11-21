@extends('layouts.vertical', [
    'title'         => 'Users',
    'sub_title'     => 'Settings',
    'sub_title_url' => route('settings.index'),
    'tagline'       => 'Manage system users and their role assignments.',
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
@if(session('reset_link'))
<div class="mb-4 p-4 rounded-lg bg-warning/10 border border-warning/30 flex gap-3">
    <i class="mgc_key_2_line text-warning text-xl mt-0.5 shrink-0"></i>
    <div class="flex-1 min-w-0 space-y-2">
        <p class="text-sm text-gray-800 dark:text-gray-100 font-medium">
            The email to {{ session('reset_link.name') }} could not be sent (email is not set up on this server).
            Give them this link instead — it works once, for 60 minutes:
        </p>
        <div class="flex items-center gap-2">
            <input type="text" readonly value="{{ session('reset_link.url') }}" id="reset-url" class="form-input text-xs w-full" onclick="this.select()">
            <button type="button" class="btn btn-sm bg-primary text-white shrink-0" onclick="copyResetLink(this)"><i class="mgc_copy_2_line"></i> Copy</button>
        </div>
        <p class="text-xs text-gray-500 dark:text-gray-400">Don't post it anywhere public — whoever opens it can set the password.</p>
    </div>
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

    {{-- ── Left: Users Table ── --}}
    <div class="col-span-12 lg:col-span-8 flex flex-col gap-6">
        <div class="card">
            <div class="card-header flex items-center justify-between">
                <h5 class="card-title"><i class="mgc_user_3_line me-2 text-primary"></i>Users</h5>
                <span class="text-xs text-gray-400">{{ $users->count() }} users</span>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700 text-sm">
                    <thead class="bg-gray-50 dark:bg-gray-700">
                        <tr>
                            <th class="px-5 py-3 text-left text-xs font-medium text-gray-500 uppercase dark:text-gray-400">Name / Email</th>
                            <th class="px-5 py-3 text-left text-xs font-medium text-gray-500 uppercase dark:text-gray-400 w-32">Role</th>
                            <th class="px-5 py-3 text-center text-xs font-medium text-gray-500 uppercase dark:text-gray-400 w-24">Status</th>
                            <th class="px-5 py-3 text-right text-xs font-medium text-gray-500 uppercase dark:text-gray-400 w-24">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                        @forelse($users as $user)
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/40 {{ !$user->is_active ? 'opacity-50' : '' }}">
                            <td class="px-5 py-3 whitespace-nowrap">
                                <p class="font-medium text-gray-800 dark:text-gray-100">{{ $user->name }}</p>
                                <p class="text-xs text-gray-400">{{ $user->email }}</p>
                            </td>
                            <td class="px-5 py-3 whitespace-nowrap">
                                @php $role = $user->roles->first(); @endphp
                                @if($role)
                                    <span class="inline-flex px-2 py-0.5 rounded-full text-xs font-semibold bg-primary/10 text-primary">
                                        {{ $role->name }}
                                    </span>
                                @else
                                    <span class="text-xs text-gray-400">No role</span>
                                @endif
                            </td>
                            <td class="px-5 py-3 text-center whitespace-nowrap">
                                @if($user->is_active)
                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-semibold bg-success/10 text-success">
                                        <span class="w-1.5 h-1.5 rounded-full bg-success inline-block"></span> Active
                                    </span>
                                @else
                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-semibold bg-gray-100 text-gray-500 dark:bg-gray-700 dark:text-gray-400">
                                        <span class="w-1.5 h-1.5 rounded-full bg-gray-400 inline-block"></span> Inactive
                                    </span>
                                @endif
                            </td>
                            <td class="px-5 py-3 text-right whitespace-nowrap">
                                <div class="flex justify-end gap-1">
                                    @php $lockedForMe = !$canAssignRoles && $role?->name === $roleGiver; @endphp
                                    @if($lockedForMe)
                                    <span class="p-1.5 text-gray-300 dark:text-gray-600" title="Only a Super Admin can change this account"><i class="mgc_lock_line text-base"></i></span>
                                    @endif
                                    @if(!$lockedForMe && auth()->user()->can('users.edit'))
                                    <button onclick="editUser({{ $user->id }}, {{ json_encode($user->name) }}, {{ json_encode($user->email) }}, {{ json_encode($role?->name ?? '') }}, {{ json_encode($user->landing_route ?? '') }})"
                                            class="p-1.5 rounded text-gray-400 hover:text-primary hover:bg-primary/10 transition" title="Edit">
                                        <i class="mgc_edit_line text-base"></i>
                                    </button>
                                    @endif
                                    @if(!$lockedForMe && auth()->user()->can('users.reset'))
                                    <form action="{{ route('users.reset-password', $user) }}" method="POST" id="reset-{{ $user->id }}">
                                        @csrf
                                        <button type="button" onclick="confirmReset({{ $user->id }}, @js($user->name), @js($user->email))"
                                                class="p-1.5 rounded text-gray-400 hover:text-info hover:bg-info/10 transition" title="Reset password">
                                            <i class="mgc_key_2_line text-base"></i>
                                        </button>
                                    </form>
                                    @endif
                                    @if(!$lockedForMe && $user->id !== auth()->id() && auth()->user()->can('users.edit'))
                                    <form action="{{ route('users.toggle', $user) }}" method="POST">
                                        @csrf @method('PATCH')
                                        <button type="submit"
                                                class="p-1.5 rounded transition {{ $user->is_active ? 'text-gray-400 hover:text-warning hover:bg-warning/10' : 'text-gray-400 hover:text-success hover:bg-success/10' }}"
                                                title="{{ $user->is_active ? 'Deactivate' : 'Activate' }}">
                                            <i class="{{ $user->is_active ? 'mgc_eye_close_line' : 'mgc_eye_line' }} text-base"></i>
                                        </button>
                                    </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="4" class="px-5 py-12 text-center text-gray-400">
                                <i class="mgc_user_3_line text-4xl mb-2 block opacity-30"></i>
                                No users found.
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    {{-- ── Right: Create / Edit Form ── --}}
    {{-- Only rendered for users who may actually create or edit an account;
         the listing alone needs just users.view. --}}
    @canany(['users.create', 'users.edit'])
    <div class="col-span-12 lg:col-span-4 flex flex-col gap-6">
        <div class="card" id="user-form-card">
            <div class="card-header flex items-center justify-between">
                <h5 class="card-title" id="user-form-title">
                    <i class="mgc_add_circle_line me-2 text-primary"></i>Add User
                </h5>
                <button onclick="cancelUserEdit()" id="user-cancel-btn"
                        class="hidden text-xs text-gray-400 hover:text-gray-600 flex items-center gap-1">
                    <i class="mgc_close_line"></i> Cancel
                </button>
            </div>
            <div class="p-5">
                <form id="user-form" action="{{ route('users.store') }}" method="POST" class="space-y-4">
                    @csrf
                    <input type="hidden" name="_method" id="user-form-method" value="POST">

                    <div>
                        <label class="form-label">Full Name <span class="text-danger">*</span></label>
                        <input type="text" name="name" id="inp-user-name"
                               class="form-input" placeholder="Juan Dela Cruz"
                               required maxlength="255">
                    </div>

                    <div>
                        <label class="form-label">Email Address <span class="text-danger">*</span></label>
                        <input type="email" name="email" id="inp-user-email"
                               class="form-input" placeholder="juan@example.com"
                               required maxlength="255">
                    </div>

                    <div>
                        <label class="form-label" id="pw-label">
                            Password <span class="text-danger">*</span>
                        </label>
                        <input type="password" name="password" id="inp-user-password"
                               class="form-input" placeholder="Min. 8 characters"
                               minlength="8">
                        <p class="text-xs text-gray-400 mt-1" id="pw-hint"></p>
                    </div>

                    <div>
                        <label class="form-label" id="pw-confirm-label">
                            Confirm Password <span class="text-danger">*</span>
                        </label>
                        <input type="password" name="password_confirmation" id="inp-user-password-confirm"
                               class="form-input" placeholder="Repeat password">
                    </div>

                    <div>
                        <label class="form-label">Role</label>
                        @if($canAssignRoles)
                        <select name="role" id="inp-user-role" class="form-select">
                            <option value="">— No Role —</option>
                            @foreach($roles as $role)
                            <option value="{{ $role->name }}">{{ $role->name }}</option>
                            @endforeach
                        </select>
                        @else
                        {{-- Not sent (disabled); only drives the landing-page list and the label below. --}}
                        <input type="hidden" id="inp-user-role" value="" disabled>
                        <div class="form-input bg-gray-50 dark:bg-gray-800 text-gray-500 dark:text-gray-400 flex items-center gap-2">
                            <i class="mgc_lock_line"></i><span id="inp-user-role-label">No role</span>
                        </div>
                        <p class="text-xs text-gray-400 mt-1">Only the Super Admin can give or change roles.</p>
                        @endif
                    </div>

                    <div>
                        <label class="form-label">Landing Page After Login</label>
                        <select name="landing_route" id="inp-user-landing" class="form-select">
                            <option value="">— Default (Home) —</option>
                            @foreach($landingRoutes as $routeName => $opt)
                            <option value="{{ $routeName }}" data-permission="{{ $opt['permission'] ?? '' }}">{{ $opt['label'] }}</option>
                            @endforeach
                        </select>
                        <p class="text-xs text-gray-400 mt-1" id="landing-hint">Where this user lands right after they log in. Only pages the selected role can access are listed.</p>
                    </div>

                    <button type="submit" id="user-submit-btn" class="btn bg-primary text-white w-full">
                        <i class="mgc_add_circle_line me-1"></i> Add User
                    </button>
                </form>
            </div>
        </div>

        <div class="card mt-4 p-5">
            <p class="text-xs font-semibold text-gray-500 uppercase mb-2">Tip</p>
            <p class="text-xs text-gray-400">
                Assign a role to give the user access to specific modules.
                Manage what each role can do on the
                <a href="{{ route('roles.index') }}" class="text-primary hover:underline">Roles page</a>.
            </p>
        </div>
    </div>
    @endcanany

</div>

    </div>

</div>

@endsection

@push('inline-scripts')
<script>
let editingUserId = null;

// Reset password: confirm, then email the user a link to set a new password.
function confirmReset(id, name, email) {
    Swal.fire({
        title: 'Reset password?',
        html: `We'll email <b>${name.replace(/</g, '&lt;')}</b> a link to set a new password:<br>`
            + `<span style="color:#3e60d5">${email.replace(/</g, '&lt;')}</span><br><br>`
            + `<small>Their current password keeps working until they set the new one. The link works for 60 minutes.</small>`,
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: '<i class="mgc_mail_send_line"></i> Send reset email',
        cancelButtonText: 'Cancel',
        reverseButtons: true,
        didOpen: () => {
            document.querySelector('.swal2-confirm').style.setProperty('background-color', '#3e60d5', 'important');
            document.querySelector('.swal2-cancel').style.setProperty('background-color', '#6c757d', 'important');
        },
    }).then(r => {
        if (!r.isConfirmed) return;
        Swal.fire({ title: 'Sending…', allowOutsideClick: false, didOpen: () => Swal.showLoading() });
        document.getElementById('reset-' + id).submit();
    });
}

function copyResetLink(btn) {
    const input = document.getElementById('reset-url');
    const done = () => { btn.innerHTML = '<i class="mgc_check_line"></i> Copied'; setTimeout(() => btn.innerHTML = '<i class="mgc_copy_2_line"></i> Copy', 1500); };
    if (navigator.clipboard) navigator.clipboard.writeText(input.value).then(done, () => { input.select(); document.execCommand('copy'); done(); });
    else { input.select(); document.execCommand('copy'); done(); }
}

// role name => permission names it grants
const ROLE_PERMISSIONS = @json($rolePermissions);

/**
 * Hide landing-page options the selected role has no permission for.
 * If the currently selected page becomes unavailable, fall back to Default.
 */
function filterLandingOptions() {
    const roleEl  = document.getElementById('inp-user-role');
    const landing = document.getElementById('inp-user-landing');
    if (!roleEl || !landing) return;   // form not rendered (view-only user)

    const role    = roleEl.value;
    const granted = ROLE_PERMISSIONS[role] ?? [];

    let selectedHidden = false;

    Array.from(landing.options).forEach(opt => {
        if (!opt.value) return;                  // always keep "Default (Home)"
        const needed  = opt.dataset.permission;
        const allowed = !needed || granted.includes(needed);

        opt.hidden   = !allowed;
        opt.disabled = !allowed;
        if (!allowed && opt.selected) selectedHidden = true;
    });

    if (selectedHidden) landing.value = '';

    const hint = document.getElementById('landing-hint');
    hint.textContent = role
        ? 'Where this user lands right after they log in. Only pages the selected role can access are listed.'
        : (roleEl.type === 'hidden'
            ? 'This user has no role yet — more pages appear once the Super Admin gives one.'
            : 'Select a role first — pages requiring permissions will appear once a role is chosen.');
}

// The form is only rendered for users who can create/edit accounts, so bind
// optionally — a view-only user has no form and would otherwise hit a
// TypeError that stops the rest of this script.
document.getElementById('inp-user-role')?.addEventListener('change', filterLandingOptions);
document.addEventListener('DOMContentLoaded', () => {
    if (document.getElementById('inp-user-role')) filterLandingOptions();
});

function editUser(id, name, email, role, landingRoute) {
    editingUserId = id;

    const form = document.getElementById('user-form');
    form.action = `{{ url('users') }}/${id}`;
    document.getElementById('user-form-method').value = 'PUT';

    document.getElementById('inp-user-name').value  = name;
    document.getElementById('inp-user-email').value = email;
    document.getElementById('inp-user-password').value = '';
    document.getElementById('inp-user-password-confirm').value = '';
    document.getElementById('inp-user-role').value = role ?? '';
    const roleLabel = document.getElementById('inp-user-role-label');
    if (roleLabel) roleLabel.textContent = role || 'No role';
    document.getElementById('inp-user-landing').value = landingRoute ?? '';
    // Re-filter for this user's role; clears the landing page if the role lost access to it.
    filterLandingOptions();

    // Password optional on edit
    document.getElementById('inp-user-password').removeAttribute('required');
    document.getElementById('inp-user-password-confirm').removeAttribute('required');
    document.getElementById('pw-label').innerHTML = 'Password <span class="text-xs text-gray-400 font-normal">(leave blank to keep)</span>';
    document.getElementById('pw-confirm-label').innerHTML = 'Confirm Password';

    document.getElementById('user-form-title').innerHTML = '<i class="mgc_edit_line me-2 text-warning"></i>Edit User';
    document.getElementById('user-submit-btn').innerHTML = '<i class="mgc_save_line me-1"></i> Save Changes';
    document.getElementById('user-submit-btn').className = 'btn bg-warning text-white w-full';
    document.getElementById('user-cancel-btn').classList.remove('hidden');

    document.getElementById('user-form-card').scrollIntoView({ behavior: 'smooth', block: 'start' });
}

function cancelUserEdit() {
    editingUserId = null;
    const form = document.getElementById('user-form');
    form.action = '{{ route('users.store') }}';
    document.getElementById('user-form-method').value = 'POST';
    form.reset();
    document.getElementById('inp-user-role').value = '';
    const roleLabel = document.getElementById('inp-user-role-label');
    if (roleLabel) roleLabel.textContent = 'No role';
    filterLandingOptions();

    document.getElementById('inp-user-password').setAttribute('required', true);
    document.getElementById('pw-label').innerHTML = 'Password <span class="text-danger">*</span>';
    document.getElementById('pw-confirm-label').innerHTML = 'Confirm Password <span class="text-danger">*</span>';

    document.getElementById('user-form-title').innerHTML = '<i class="mgc_add_circle_line me-2 text-primary"></i>Add User';
    document.getElementById('user-submit-btn').innerHTML = '<i class="mgc_add_circle_line me-1"></i> Add User';
    document.getElementById('user-submit-btn').className = 'btn bg-primary text-white w-full';
    document.getElementById('user-cancel-btn').classList.add('hidden');
}
</script>
@endpush
