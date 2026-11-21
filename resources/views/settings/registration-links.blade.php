@extends('layouts.vertical', [
    'title'         => 'Registration Links',
    'sub_title'     => 'Settings',
    'sub_title_url' => route('settings.index'),
    'tagline'       => 'Links residents use to register themselves online. Every registration waits for approval in Citizens → Registrations.',
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

        {{-- New link --}}
        <div class="card">
            <div class="card-header">
                <h4 class="card-title">Create a registration link</h4>
            </div>
            <form method="POST" action="{{ route('settings.registration-links.store') }}" class="p-6">
                @csrf
                <div class="grid md:grid-cols-4 gap-4 items-end">
                    <div class="md:col-span-2">
                        <label class="form-label">Label <span class="text-danger">*</span></label>
                        <input type="text" name="label" value="{{ old('label') }}" maxlength="150" required
                               placeholder="e.g. Zone 3 house-to-house, Facebook page, Barangay hall kiosk"
                               class="form-input w-full">
                    </div>
                    <div>
                        <label class="form-label">Expires</label>
                        <input type="datetime-local" name="expires_at" value="{{ old('expires_at') }}" class="form-input w-full">
                        <p class="text-xs text-gray-400 mt-1">Leave empty for no expiry.</p>
                    </div>
                    <div>
                        <label class="form-label">Max registrations</label>
                        <input type="number" name="max_uses" value="{{ old('max_uses') }}" min="1" class="form-input w-full" placeholder="No limit">
                        <p class="text-xs text-gray-400 mt-1">Use 1 for a one-person link.</p>
                    </div>
                </div>
                <div class="flex justify-end mt-4">
                    <button type="submit" class="btn bg-primary text-white"><i class="mgc_add_line"></i> Create link</button>
                </div>
            </form>
        </div>

        {{-- Links --}}
        <div class="card">
            <div class="card-header">
                <div class="flex justify-between items-center">
                    <h4 class="card-title">Links</h4>
                    @can('citizens.approve')
                    <a href="{{ route('citizens.registrations.index') }}" class="text-sm text-primary hover:underline">Review registrations →</a>
                    @endcan
                </div>
            </div>
            <div class="overflow-x-auto">
                <table class="table min-w-full text-sm">
                    <thead class="bg-gray-50 dark:bg-gray-700">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase dark:text-gray-400">Label</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase dark:text-gray-400">Status</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase dark:text-gray-400">Used</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase dark:text-gray-400">Expires</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase dark:text-gray-400">Link</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase dark:text-gray-400">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                        @forelse($tokens as $t)
                        @php
                            $state = $t->state();
                            [$bg, $dot, $label] = match($state) {
                                'active'   => ['bg-green-100 text-green-800', 'bg-green-400', 'Active'],
                                'expired'  => ['bg-yellow-100 text-yellow-800', 'bg-yellow-400', 'Expired'],
                                'full'     => ['bg-yellow-100 text-yellow-800', 'bg-yellow-400', 'Limit reached'],
                                default    => ['bg-gray-100 text-gray-800', 'bg-gray-400', 'Off'],
                            };
                        @endphp
                        <tr>
                            <td class="px-5 py-3 text-gray-800 dark:text-gray-200">
                                <p class="font-medium">{{ $t->label }}</p>
                                <p class="text-xs text-gray-400">
                                    Created {{ $t->created_at->format('M d, Y') }}{{ $t->createdBy ? ' by ' . $t->createdBy->name : '' }}
                                    @if($t->pending_count) · <span class="text-warning font-medium">{{ $t->pending_count }} waiting for review</span>@endif
                                </p>
                            </td>
                            <td class="px-5 py-3">
                                <span class="inline-flex items-center gap-1.5 py-1.5 px-3 rounded-full text-xs font-medium {{ $bg }}">
                                    <span class="w-1.5 h-1.5 inline-block {{ $dot }} rounded-full"></span>{{ $label }}
                                </span>
                            </td>
                            <td class="px-5 py-3 text-gray-800 dark:text-gray-200 whitespace-nowrap">
                                {{ $t->uses }}{{ $t->max_uses !== null ? ' / ' . $t->max_uses : '' }}
                            </td>
                            <td class="px-5 py-3 text-gray-800 dark:text-gray-200 whitespace-nowrap">
                                {{ $t->expires_at ? $t->expires_at->format('M d, Y g:i A') : 'Never' }}
                            </td>
                            <td class="px-5 py-3">
                                <div class="flex items-center gap-2">
                                    <input type="text" readonly value="{{ $t->url() }}" id="link-{{ $t->id }}"
                                           class="form-input text-xs w-56" onclick="this.select()">
                                    <button type="button" class="btn btn-sm bg-primary/25 text-primary hover:bg-primary hover:text-white"
                                            onclick="copyLink({{ $t->id }}, this)" title="Copy link"><i class="mgc_copy_2_line"></i></button>
                                    <button type="button" class="btn btn-sm bg-primary/25 text-primary hover:bg-primary hover:text-white"
                                            data-fc-type="modal" data-fc-target="qr-{{ $t->id }}" title="QR code"><i class="mgc_qrcode_line"></i></button>
                                </div>
                            </td>
                            <td class="px-5 py-3 text-right whitespace-nowrap">
                                <form method="POST" action="{{ route('settings.registration-links.toggle', $t) }}" class="inline">
                                    @csrf @method('PATCH')
                                    <button type="submit" class="btn btn-sm bg-dark/25 text-slate-900 dark:text-slate-200 hover:bg-dark hover:text-white">
                                        {{ $t->is_active ? 'Turn off' : 'Turn on' }}
                                    </button>
                                </form>
                                <form method="POST" action="{{ route('settings.registration-links.destroy', $t) }}" class="inline" id="del-link-{{ $t->id }}">
                                    @csrf @method('DELETE')
                                    <button type="button" class="btn btn-sm bg-danger/25 text-danger hover:bg-danger hover:text-white"
                                            onclick="deleteLink({{ $t->id }}, @js($t->label))"><i class="mgc_delete_line"></i></button>
                                </form>
                            </td>
                        </tr>

                        @empty
                        <tr><td colspan="6" class="text-center py-12 text-gray-400">
                            <i class="mgc_link_line text-4xl mb-2 block opacity-30"></i>No registration links yet.
                        </td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        {{-- QR pop-ups (outside the table) --}}
        @foreach($tokens as $t)
                        <div id="qr-{{ $t->id }}" class="fc-modal hidden w-full h-full fixed top-0 start-0 z-50">
                            <div class="fc-modal-open:opacity-100 fc-modal-open:duration-500 opacity-0 transition-all sm:max-w-sm sm:w-full m-3 sm:mx-auto">
                                <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg">
                                    <div class="flex justify-between items-center py-3 px-4 border-b dark:border-gray-700">
                                        <h3 class="font-bold text-gray-800 dark:text-white">{{ $t->label }}</h3>
                                        <button data-fc-dismiss="modal" type="button"><i class="mgc_close_line text-xl"></i></button>
                                    </div>
                                    <div class="p-6 text-center">
                                        <div class="inline-block bg-white p-3 rounded-lg">{!! $t->qrSvg(220) !!}</div>
                                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-3 break-all">{{ $t->url() }}</p>
                                        <p class="text-xs text-gray-400 mt-2">Print or show this so residents can scan it with their phone.</p>
                                    </div>
                                </div>
                            </div>
                        </div>
        @endforeach

    </div>
</div>

@endsection

@push('inline-scripts')
<script>
function copyLink(id, btn) {
    const input = document.getElementById('link-' + id);
    const done = () => { btn.innerHTML = '<i class="mgc_check_line"></i>'; setTimeout(() => btn.innerHTML = '<i class="mgc_copy_2_line"></i>', 1500); };
    if (navigator.clipboard) {
        navigator.clipboard.writeText(input.value).then(done, () => { input.select(); document.execCommand('copy'); done(); });
    } else {
        input.select(); document.execCommand('copy'); done();
    }
}

function deleteLink(id, label) {
    Swal.fire({
        title: 'Delete this link?',
        text: `"${label}" will stop working. Registrations already sent through it are kept.`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Yes, delete',
        reverseButtons: true,
        didOpen: () => {
            document.querySelector('.swal2-confirm').style.setProperty('background-color', '#fa5c7c', 'important');
        },
    }).then(r => { if (r.isConfirmed) document.getElementById('del-link-' + id).submit(); });
}
</script>
@endpush
