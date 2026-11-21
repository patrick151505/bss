@extends('layouts.vertical', [
    'title'         => 'Kiosks',
    'sub_title'     => 'Settings',
    'sub_title_url' => route('settings.index'),
    'tagline'       => 'Self-service screens at the barangay hall: residents scan their Barangay ID to request a document, or register. Staff approve and print at the counter.',
    'mode'          => $mode ?? '',
    'demo'          => $demo ?? '',
])

@section('content')

<div class="grid grid-cols-12 gap-6">

    <div class="col-span-12 lg:col-span-3 xl:col-span-2">
        @include('settings.partials.subnav')
    </div>

    <div class="col-span-12 lg:col-span-9 xl:col-span-10 space-y-6">

        @if(session('success'))
        <div class="p-4 rounded-lg bg-success/10 border border-success/30 flex gap-3">
            <i class="mgc_check_circle_line text-success text-xl mt-0.5 shrink-0"></i>
            <p class="text-sm text-success font-medium">{{ session('success') }}</p>
        </div>
        @endif

        @if($errors->any())
        <div class="p-4 rounded-lg bg-danger/10 border border-danger/30 flex gap-3">
            <i class="mgc_alert_line text-danger text-xl mt-0.5 shrink-0"></i>
            <ul class="list-disc list-inside text-sm text-danger/80 space-y-0.5">
                @foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach
            </ul>
        </div>
        @endif

        {{-- Who can use the kiosk --}}
        <div class="card">
            <div class="card-header flex items-center justify-between gap-3 flex-wrap">
                <h4 class="card-title">Kiosk accounts</h4>
                @can('users.view')
                <a href="{{ route('users.index') }}" class="btn btn-sm bg-primary text-white"><i class="mgc_user_add_line"></i> Manage users</a>
                @endcan
            </div>
            <div class="p-6 border-b border-gray-100 dark:border-gray-700 space-y-3">
                <p class="text-sm text-gray-600 dark:text-gray-300">
                    Any tablet or computer can be the kiosk. Open the kiosk address and log in with an account that has the
                    <strong>Kiosk</strong> role (the <em>kiosk.use</em> permission). No pairing is needed.
                </p>
                <div class="flex items-center gap-2 max-w-xl">
                    <input type="text" readonly value="{{ route('kiosk.home') }}" id="kiosk-url" class="form-input text-xs w-full" onclick="this.select()">
                    <button type="button" class="btn btn-sm bg-primary text-white shrink-0" onclick="copyUrl(this)"><i class="mgc_copy_2_line"></i> Copy</button>
                </div>
            </div>
            <div class="overflow-x-auto">
                <table class="table min-w-full text-sm">
                    <thead class="bg-gray-50 dark:bg-gray-700">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase dark:text-gray-400">Account</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase dark:text-gray-400">Role</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase dark:text-gray-400">Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                        @forelse($accounts as $u)
                        <tr>
                            <td class="px-5 py-3 text-gray-800 dark:text-gray-200">
                                <p class="font-medium">{{ $u->name }}</p>
                                <p class="text-xs text-gray-400">{{ $u->username ?? $u->email }}</p>
                            </td>
                            <td class="px-5 py-3 text-gray-800 dark:text-gray-200">{{ $u->roles->pluck('name')->join(', ') ?: '—' }}</td>
                            <td class="px-5 py-3">
                                @if($u->is_active)
                                <span class="inline-flex items-center gap-1.5 py-1.5 px-3 rounded-full text-xs font-medium bg-green-100 text-green-800"><span class="w-1.5 h-1.5 inline-block bg-green-400 rounded-full"></span>Active</span>
                                @else
                                <span class="inline-flex items-center gap-1.5 py-1.5 px-3 rounded-full text-xs font-medium bg-red-100 text-red-800"><span class="w-1.5 h-1.5 inline-block bg-red-400 rounded-full"></span>Inactive</span>
                                @endif
                            </td>
                        </tr>
                        @empty
                        <tr><td colspan="3" class="text-center py-12 text-gray-400">
                            <i class="mgc_computer_line text-4xl mb-2 block opacity-30"></i>No kiosk accounts yet. Add a user with the <strong>Kiosk</strong> role.
                        </td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="grid lg:grid-cols-2 gap-6">
            {{-- Documents offered --}}
            <div class="card p-6 space-y-3">
                <h4 class="card-title">Documents offered at the kiosk</h4>
                @if($kioskTypes->isEmpty())
                <p class="text-sm text-warning">None yet — residents will only be able to register.</p>
                @else
                <div class="flex flex-wrap gap-2">
                    @foreach($kioskTypes as $t)
                    <span class="inline-flex items-center gap-1.5 py-1.5 px-3 rounded-full text-xs font-medium bg-primary/10 text-primary">
                        {{ $t->name }} · {{ $t->is_paid ? '₱' . number_format($t->fee, 2) : 'Free' }}
                    </span>
                    @endforeach
                </div>
                @endif
                <p class="text-xs text-gray-400">
                    Turn on <strong>Offer at the kiosk</strong> when editing a document type.
                    @can('documents.view')<a href="{{ route('documents.types.index') }}" class="text-primary hover:underline">Open Document Types →</a>@endcan
                </p>
            </div>

            {{-- Setup tips --}}
            <div class="card p-6 space-y-2">
                <h4 class="card-title">Setting up the device</h4>
                <ol class="list-decimal list-inside text-sm text-gray-600 dark:text-gray-300 space-y-1">
                    <li>On the device, open the kiosk address and log in with an account that has the <strong>Kiosk</strong> role (only the <em>kiosk.use</em> permission) and tick <strong>Remember me</strong> so it stays logged in. Set its landing page to <em>Kiosk screen</em> in Users.</li>
                    <li>Plug in a USB QR scanner set to <strong>keyboard mode</strong>, sending <strong>Enter</strong> after each scan.</li>
                    <li>Run the browser full screen (Chrome: <em>F11</em>, or the <em>--kiosk</em> start option) and turn off screen sleep.</li>
                    <li>Kiosk address: <code class="text-xs">{{ route('kiosk.home') }}</code></li>
                </ol>
            </div>
        </div>

        {{-- Start screen pictures --}}
        <div class="card">
            <div class="card-header">
                <h4 class="card-title">Start screen pictures</h4>
            </div>
            <form method="POST" action="{{ route('settings.kiosk-slides.store') }}" enctype="multipart/form-data" class="p-6 border-b border-gray-100 dark:border-gray-700">
                @csrf
                <div class="grid md:grid-cols-3 gap-4 items-end">
                    <div>
                        <label class="form-label">Pictures <span class="text-danger">*</span></label>
                        <input type="file" name="images[]" accept="image/jpeg,image/png,image/webp" multiple required class="form-input w-full">
                    </div>
                    <div>
                        <label class="form-label">Caption</label>
                        <input type="text" name="caption" maxlength="200" placeholder="e.g. Free vaccination this Saturday" class="form-input w-full">
                    </div>
                    <div>
                        <button type="submit" class="btn bg-primary text-white"><i class="mgc_upload_2_line"></i> Upload</button>
                    </div>
                </div>
                <p class="text-xs text-gray-400 mt-2">Landscape pictures look best (1920 × 1080). Up to 10 at a time, 5 MB each. They play in order, one every 7 seconds.</p>
            </form>
            <div class="p-6">
                @if($slides->isEmpty())
                <p class="text-center py-8 text-gray-400"><i class="mgc_pic_line text-4xl mb-2 block opacity-30"></i>No pictures yet — the start screen shows the barangay name and logo.</p>
                @else
                <div class="grid sm:grid-cols-2 xl:grid-cols-3 gap-4">
                    @foreach($slides as $i => $s)
                    <div class="rounded-lg border border-gray-200 dark:border-gray-700 overflow-hidden {{ $s->is_active ? '' : 'opacity-60' }}">
                        <div class="relative bg-gray-100 dark:bg-gray-700" style="aspect-ratio:16/9">
                            <img src="{{ $s->url }}" alt="" class="w-full h-full object-cover">
                            <span class="absolute top-2 start-2 py-0.5 px-2 rounded-full text-xs font-medium bg-dark/70 text-white">{{ $i + 1 }}</span>
                        </div>
                        <form method="POST" action="{{ route('settings.kiosk-slides.update', $s) }}" class="p-3 space-y-2">
                            @csrf @method('PATCH')
                            <input type="text" name="caption" value="{{ $s->caption }}" maxlength="200" placeholder="Caption (optional)" class="form-input w-full text-sm">
                            <div class="flex items-center justify-between gap-2">
                                <label class="flex items-center gap-2 text-sm text-gray-600 dark:text-gray-300">
                                    <input type="checkbox" name="is_active" value="1" class="form-checkbox rounded text-primary" @checked($s->is_active)> Show
                                </label>
                                <div class="flex items-center gap-1">
                                    <button type="submit" name="move" value="up" class="btn btn-sm bg-dark/25 text-slate-900 dark:text-slate-200" title="Move earlier" @disabled($i === 0)><i class="mgc_arrow_left_line"></i></button>
                                    <button type="submit" name="move" value="down" class="btn btn-sm bg-dark/25 text-slate-900 dark:text-slate-200" title="Move later" @disabled($i === $slides->count() - 1)><i class="mgc_arrow_right_line"></i></button>
                                    <button type="submit" class="btn btn-sm bg-primary/25 text-primary hover:bg-primary hover:text-white">Save</button>
                                    <button type="button" class="btn btn-sm bg-danger/25 text-danger hover:bg-danger hover:text-white"
                                            onclick="confirmForm('del-slide-{{ $s->id }}', 'Remove this picture?', 'It will no longer show on the kiosk.')"><i class="mgc_delete_line"></i></button>
                                </div>
                            </div>
                        </form>
                        <form method="POST" action="{{ route('settings.kiosk-slides.destroy', $s) }}" id="del-slide-{{ $s->id }}" class="hidden">
                            @csrf @method('DELETE')
                        </form>
                    </div>
                    @endforeach
                </div>
                @endif
            </div>
        </div>

    </div>
</div>

@endsection

@push('inline-scripts')
<script>
function copyUrl(btn) {
    const input = document.getElementById('kiosk-url');
    const done = () => { btn.innerHTML = '<i class="mgc_check_line"></i> Copied'; setTimeout(() => btn.innerHTML = '<i class="mgc_copy_2_line"></i> Copy', 1500); };
    if (navigator.clipboard) {
        navigator.clipboard.writeText(input.value).then(done, () => { input.select(); document.execCommand('copy'); done(); });
    } else {
        input.select(); document.execCommand('copy'); done();
    }
}

function confirmForm(id, title, text) {
    Swal.fire({
        title, text,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Yes, continue',
        reverseButtons: true,
        didOpen: () => {
            document.querySelector('.swal2-confirm').style.setProperty('background-color', '#fa5c7c', 'important');
        },
    }).then(r => { if (r.isConfirmed) document.getElementById(id).submit(); });
}
</script>
@endpush
