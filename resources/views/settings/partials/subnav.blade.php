@php
    $settingsNav = [
        ['route' => 'settings.index',            'pattern' => 'settings.index',            'icon' => 'mgc_building_2_line',   'label' => 'General Info',       'can' => 'settings.view'],
        ['route' => 'officials.index',           'pattern' => 'officials.*',               'icon' => 'mgc_group_line',        'label' => 'Officials',          'can' => 'settings.view'],
        ['route' => 'addresses.index',           'pattern' => 'addresses.*',                'icon' => 'mgc_map_line',          'label' => 'Zones & Addresses',  'can' => 'addresses.view'],
        ['route' => 'users.index',               'pattern' => 'users.*',                    'icon' => 'mgc_user_4_line'       ,'label' => 'Users',              'can' => 'users.view'],
        ['route' => 'roles.index',               'pattern' => 'roles.*',                    'icon' => 'mgc_shield_line',       'label' => 'Roles',              'can' => 'roles.view'],
        ['route' => 'documents.templates.index', 'pattern' => 'documents.templates.*',      'icon' => 'mgc_paper_line',        'label' => 'Paper Templates',    'can' => 'documents.view'],
        ['route' => 'settings.print-paper',      'pattern' => 'settings.print-paper*',      'icon' => 'mgc_print_line',        'label' => 'Print Paper',        'can' => 'settings.view'],
        ['route' => 'citizens.ids.template.designer', 'pattern' => 'citizens.ids.template.*', 'icon' => 'mgc_IDcard_line',    'label' => 'ID Template',        'can' => 'citizen_ids.template'],
        ['route' => 'settings.updates',          'pattern' => 'settings.updates',           'icon' => 'mgc_rocket_line',       'label' => 'Updates',            'can' => 'settings.view', 'badge' => true],
    ];
    $appVersion = config('app.version');
@endphp
<div class="card p-2">
    <ul class="space-y-1">
        @foreach($settingsNav as $item)
        @if(isset($item['can']) && !auth()->user()?->can($item['can']))
            @continue
        @endif
        <li>
            <a href="{{ route($item['route']) }}"
                class="flex items-center gap-3 px-4 py-2.5 rounded-lg text-sm font-medium transition
                    {{ request()->routeIs($item['pattern']) ? 'bg-primary/10 text-primary' : 'text-gray-600 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-800' }}">
                <i class="{{ $item['icon'] }} text-lg shrink-0"></i>
                <span class="flex-1 min-w-0">{{ $item['label'] }}</span>
                @if(!empty($item['badge']))
                {{-- "New" until this user opens the page for the current version (per browser) --}}
                <span class="updates-new-dot hidden shrink-0 py-0.5 px-1.5 rounded-full text-[10px] font-semibold bg-primary text-white" data-version="{{ $appVersion }}">New</span>
                @endif
            </a>
        </li>
        @endforeach
    </ul>
    @can('settings.view')
    <a href="{{ route('settings.updates') }}" class="block mt-2 px-4 py-2.5 border-t border-gray-100 dark:border-gray-700 text-xs text-gray-400 hover:text-primary">
        Version {{ $appVersion }}
    </a>
    @endcan
</div>
@push('inline-scripts')
<script>
try {
    document.querySelectorAll('.updates-new-dot').forEach(d => {
        if (localStorage.getItem('updatesSeen') !== d.dataset.version) d.classList.remove('hidden');
    });
} catch (e) {}
</script>
@endpush
