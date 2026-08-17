<!-- Topbar Start -->
<header class="app-header flex items-center px-4 gap-3">
    <!-- Sidenav Menu Toggle Button -->
    <button id="button-toggle-menu" class="nav-link p-2">
        <span class="sr-only">Menu Toggle Button</span>
        <span class="flex items-center justify-center h-6 w-6">
            <i class="mgc_menu_line text-xl"></i>
        </span>
    </button>

    <!-- Topbar Brand Logo -->
    <a href="" class="logo-box">
        <!-- Light Brand Logo -->
        <div class="logo-light">
            <img src="/images/logo-light.png" class="logo-lg h-6" alt="Light logo">
            <img src="/images/logo-sm.png" class="logo-sm" alt="Small logo">
        </div>

        <!-- Dark Brand Logo -->
        <div class="logo-dark">
            <img src="/images/logo-dark.png" class="logo-lg h-6" alt="Dark logo">
            <img src="/images/logo-sm.png" class="logo-sm" alt="Small logo">
        </div>
    </a>

    <!-- Topbar Search Modal Button -->
    <button type="button" data-fc-type="modal" data-fc-target="topbar-search-modal" class="nav-link p-2 me-auto">
        <span class="sr-only">Search</span>
        <span class="flex items-center justify-center h-6 w-6">
            <i class="mgc_search_line text-2xl"></i>
        </span>
    </button>

    <!-- Philippine Flag -->
    <div class="flex items-center p-2">
        <span class="flex items-center justify-center h-6 w-6">
            <img src="/images/flags/ph.svg" alt="Philippines" class="h-4 w-6">
        </span>
    </div>

    <!-- Fullscreen Toggle Button -->
    <div class="md:flex hidden">
        <button data-toggle="fullscreen" type="button" class="nav-link p-2">
            <span class="sr-only">Fullscreen Mode</span>
            <span class="flex items-center justify-center h-6 w-6">
                <i class="mgc_fullscreen_line text-2xl"></i>
            </span>
        </button>
    </div>

    <!-- Notification Bell Button -->
    <div class="relative md:flex hidden">
        <button data-fc-type="dropdown" data-fc-placement="bottom-end" type="button" class="nav-link p-2 relative">
            <span class="sr-only">View notifications</span>
            <span class="flex items-center justify-center h-6 w-6">
                <i class="mgc_notification_line text-2xl"></i>
            </span>
            @if(isset($lowStockItems) && $lowStockItems->count() > 0)
            <span class="absolute top-1 right-1 flex h-4 w-4 items-center justify-center rounded-full bg-red-500 text-white text-[10px] font-bold leading-none">
                {{ $lowStockItems->count() }}
            </span>
            @endif
        </button>
        <div class="fc-dropdown fc-dropdown-open:opacity-100 hidden opacity-0 w-80 z-50 mt-2 transition-[margin,opacity] duration-300 bg-white dark:bg-gray-800 shadow-lg border border-gray-200 dark:border-gray-700 rounded-lg">

            <div class="px-4 py-3 border-b border-dashed border-gray-200 dark:border-gray-700 flex items-center justify-between">
                <h6 class="text-sm font-semibold text-gray-800 dark:text-gray-100">Low Stock Alerts</h6>
                @if(isset($lowStockItems) && $lowStockItems->count() > 0)
                <span class="inline-flex items-center gap-1.5 py-1 px-2 rounded-full text-xs font-medium bg-red-100 text-red-800">
                    <span class="w-1.5 h-1.5 inline-block bg-red-400 rounded-full"></span>
                    {{ $lowStockItems->count() }} item(s)
                </span>
                @endif
            </div>

            <div class="py-2 max-h-80 overflow-y-auto" data-simplebar>
                @if(isset($lowStockItems) && $lowStockItems->count() > 0)
                    @foreach($lowStockItems as $lsi)
                    <a href="{{ route('inventory.items.edit', $lsi) }}"
                       class="flex items-center gap-3 px-4 py-3 hover:bg-gray-50 dark:hover:bg-gray-700 transition">
                        <div class="flex-shrink-0">
                            @if($lsi->image)
                                <img src="{{ asset('storage/' . $lsi->image) }}"
                                     class="w-9 h-9 rounded-lg object-cover">
                            @else
                                <div class="w-9 h-9 rounded-lg bg-red-100 flex items-center justify-center">
                                    <i class="mgc_box_3_line text-red-500"></i>
                                </div>
                            @endif
                        </div>
                        <div class="flex-1 min-w-0">
                            <p class="text-sm font-medium text-gray-800 dark:text-gray-100 truncate">{{ $lsi->name }}</p>
                            <p class="text-xs text-gray-400 truncate">{{ $lsi->category->name ?? '—' }}</p>
                        </div>
                        <div class="text-right shrink-0">
                            @if($lsi->stock == 0)
                                <span class="text-xs font-bold text-red-600">Out of stock</span>
                            @else
                                <span class="text-xs font-bold text-red-500">{{ $lsi->stock }} left</span>
                            @endif
                            <p class="text-xs text-gray-400">min {{ $lsi->low_stock_threshold }}</p>
                        </div>
                    </a>
                    @endforeach
                @else
                    <div class="px-4 py-8 text-center text-gray-400">
                        <i class="mgc_check_circle_line text-3xl mb-2 block text-green-400"></i>
                        <p class="text-sm">All stock levels are healthy.</p>
                    </div>
                @endif
            </div>

            <a href="{{ route('inventory.items.index') }}"
               class="px-4 py-2.5 border-t border-dashed border-gray-200 dark:border-gray-700 flex items-center justify-center gap-1.5 text-sm font-semibold text-primary hover:underline">
                View All Items <i class="mgc_arrow_right_line text-sm"></i>
            </a>
        </div>
    </div>

    <!-- Light/Dark Toggle Button -->
    <div class="flex">
        <button id="light-dark-mode" type="button" class="nav-link p-2">
            <span class="sr-only">Light/Dark Mode</span>
            <span class="flex items-center justify-center h-6 w-6">
                <i class="mgc_moon_line text-2xl"></i>
            </span>
        </button>
    </div>

    <!-- Profile Dropdown Button -->
    <div class="relative">
        <button data-fc-type="dropdown" data-fc-placement="bottom-end" type="button" class="nav-link">
            @php $authUser = auth()->user(); @endphp
            @if($authUser?->photo)
                <img src="{{ asset('storage/' . str_replace('public/', '', $authUser->photo)) }}"
                     alt="{{ $authUser->name }}" class="rounded-full h-10 w-10 object-cover">
            @else
                {{-- Initials avatar — matches the profile page identity (no stock face) --}}
                <span class="rounded-full h-10 w-10 flex items-center justify-center bg-primary/20 text-primary font-semibold text-sm uppercase">
                    {{ \Illuminate\Support\Str::of($authUser?->name ?? '')->trim()->explode(' ')->take(2)->map(fn($w) => mb_substr($w, 0, 1))->implode('') ?: 'U' }}
                </span>
            @endif
        </button>
        <div class="fc-dropdown fc-dropdown-open:opacity-100 hidden opacity-0 w-44 z-50 transition-[margin,opacity] duration-300 mt-2 bg-white shadow-lg border rounded-lg p-2 border-gray-200 dark:border-gray-700 dark:bg-gray-800">
            <a class="flex items-center py-2 px-3 rounded-md text-sm text-gray-800 hover:bg-gray-100 dark:text-gray-400 dark:hover:bg-gray-700 dark:hover:text-gray-300" href="{{ route('profile.edit') }}">
                <i class="mgc_user_3_line me-2"></i>
                <span>My Profile</span>
            </a>
            @if(auth()->user()?->canAny(['settings.view', 'settings.edit']))
            <a class="flex items-center py-2 px-3 rounded-md text-sm text-gray-800 hover:bg-gray-100 dark:text-gray-400 dark:hover:bg-gray-700 dark:hover:text-gray-300" href="{{ route('settings.index') }}">
                <i class="mgc_settings_4_line me-2"></i>
                <span>Settings</span>
            </a>
            @endif
            <hr class="my-2 -mx-2 border-gray-200 dark:border-gray-700">
            <a class="flex items-center py-2 px-3 rounded-md text-sm text-gray-800 hover:bg-gray-100 dark:text-gray-400 dark:hover:bg-gray-700 dark:hover:text-gray-300" href="#"
               onclick="event.preventDefault(); document.getElementById('topbar-logout-form').submit();">
                <i class="mgc_exit_line me-2"></i>
                <span>Log Out</span>
            </a>
            <form id="topbar-logout-form" action="{{ route('logout') }}" method="POST" class="hidden">
                @csrf
            </form>
        </div>
    </div>
</header>
<!-- Topbar End -->

<!-- Topbar Search Modal -->
<div>
    <div id="topbar-search-modal" class="fc-modal hidden w-full h-full fixed top-0 start-0 z-50">
        <div class="fc-modal-open:opacity-100 fc-modal-open:duration-500 opacity-0 transition-all sm:max-w-lg sm:w-full m-12 sm:mx-auto">
            <div class="mx-auto max-w-2xl overflow-hidden rounded-xl bg-white shadow-2xl transition-all dark:bg-slate-800">
                <div class="relative">
                    <div class="pointer-events-none absolute top-3.5 start-4 text-gray-900 text-opacity-40 dark:text-gray-200">
                        <i class="mgc_search_line text-xl"></i>
                    </div>
                    <input type="search" class="h-12 w-full border-0 bg-transparent ps-11 pe-4 text-gray-900 placeholder-gray-500 dark:placeholder-gray-300 dark:text-gray-200 focus:ring-0 sm:text-sm" placeholder="Search...">
                </div>
            </div>
        </div>
    </div>
</div>
