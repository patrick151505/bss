@php
    $settingsNav = [
        ['route' => 'settings.index',            'pattern' => 'settings.index',            'icon' => 'mgc_building_2_line',   'label' => 'General Info'],
        ['route' => 'officials.index',           'pattern' => 'officials.*',               'icon' => 'mgc_group_line',        'label' => 'Officials'],
        ['route' => 'addresses.index',           'pattern' => 'addresses.*',                'icon' => 'mgc_map_line',          'label' => 'Zones & Addresses'],
        ['route' => 'users.index',               'pattern' => 'users.*',                    'icon' => 'mgc_user_security_line','label' => 'Users'],
        ['route' => 'roles.index',               'pattern' => 'roles.*',                    'icon' => 'mgc_shield_line',       'label' => 'Roles'],
        ['route' => 'documents.templates.index', 'pattern' => 'documents.templates.*',      'icon' => 'mgc_paper_line',        'label' => 'Paper Templates'],
    ];
@endphp
<div class="card p-2">
    <ul class="space-y-1">
        @foreach($settingsNav as $item)
        <li>
            <a href="{{ route($item['route']) }}"
                class="flex items-center gap-3 px-4 py-2.5 rounded-lg text-sm font-medium transition
                    {{ request()->routeIs($item['pattern']) ? 'bg-primary/10 text-primary' : 'text-gray-600 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-800' }}">
                <i class="{{ $item['icon'] }} text-lg"></i>
                {{ $item['label'] }}
            </a>
        </li>
        @endforeach
    </ul>
</div>
