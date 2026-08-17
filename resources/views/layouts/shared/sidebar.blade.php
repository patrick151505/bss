<div class="app-menu">

    <!-- Sidenav Brand Logo -->
    <a href="" class="logo-box">
        <!-- Light Brand Logo -->
        <div class="logo-light">
            <img src="/images/logo-light.png" class="logo-lg" style="height:45px" alt="Light logo">
            <img src="/images/logo-sm.png" class="logo-sm" alt="Small logo">
        </div>

        <!-- Dark Brand Logo -->
        <div class="logo-dark">
            <img src="/images/logo-dark.png" class="logo-lg" style="height:45px" alt="Dark logo">
            <img src="/images/logo-sm.png" class="logo-sm" alt="Small logo">
        </div>
    </a>

    <!-- Sidenav Menu Toggle Button -->
    <button id="button-hover-toggle" class="absolute top-5 end-2 rounded-full p-1.5">
        <span class="sr-only">Menu Toggle Button</span>
        <i class="mgc_round_line text-xl"></i>
    </button>

    <!--- Menu -->
    <div class="srcollbar" data-simplebar>
        <ul class="menu" data-fc-type="accordion">
            <li class="menu-title">Dashboard</li>

            @can('citizens.view')
            <li class="menu-item {{ request()->routeIs('citizens.demographics') ? 'active' : '' }}">
                <a href="{{ route('citizens.demographics') }}" class="menu-link">
                    <span class="menu-icon"><i class="mgc_chart_pie_line"></i></span>
                    <span class="menu-text">Demographics</span>
                </a>
            </li>
            @endcan

            <li class="menu-item {{ request()->routeIs('dashboard.activity') ? 'active' : '' }}">
                <a href="{{ route('dashboard.activity') }}" class="menu-link">
                    <span class="menu-icon"><i class="mgc_chart_bar_line"></i></span>
                    <span class="menu-text">Activity</span>
                </a>
            </li>

            @php
                $canCitizens   = auth()->user()?->can('citizens.view');
                $canHouseholds = auth()->user()?->can('households.view');
                $canTags       = auth()->user()?->can('tags.view');
                $canBlotter    = auth()->user()?->can('blotter.view');
                $citizenGroupVisible = $canCitizens || $canHouseholds || $canTags;
                $citizenGroupActive = request()->routeIs('citizens.*') || request()->routeIs('tags.*') || request()->routeIs('birthdays.index') || request()->routeIs('households.*');
                // The Barangay section covers the Citizen group + Blotter — show its
                // title only if the user can access at least one of those.
                $barangaySectionVisible = $citizenGroupVisible || $canBlotter;
            @endphp

            @if($barangaySectionVisible)
            <li class="menu-title">Barangay</li>
            @endif
            @if($citizenGroupVisible)
            <li class="menu-item {{ $citizenGroupActive ? 'active open' : '' }}">
                <a href="javascript:void(0)" data-fc-type="collapse" class="menu-link">
                    <span class="menu-icon"><i class="mgc_group_line"></i></span>
                    <span class="menu-text">Citizen</span>
                    <span class="menu-arrow"></span>
                </a>
                <ul class="sub-menu {{ $citizenGroupActive ? '' : 'hidden' }}">
                    @if($canCitizens)
                    <li class="menu-item {{ request()->routeIs('citizens.index') || (request()->routeIs('citizens.*') && !request()->routeIs('citizens.ids.*') && !request()->routeIs('citizens.demographics')) ? 'active' : '' }}">
                        <a href="{{ route('citizens.index') }}" class="menu-link">
                            <span class="menu-text">Citizens</span>
                        </a>
                    </li>
                    <li class="menu-item {{ request()->routeIs('citizens.ids.*') ? 'active' : '' }}">
                        <a href="{{ route('citizens.ids.index') }}" class="menu-link">
                            <span class="menu-text">Barangay IDs</span>
                        </a>
                    </li>
                    <li class="menu-item {{ request()->routeIs('birthdays.index') ? 'active' : '' }}">
                        <a href="{{ route('birthdays.index') }}" class="menu-link">
                            <span class="menu-text">Birthdays</span>
                        </a>
                    </li>
                    @endif
                    @if($canTags)
                    <li class="menu-item {{ request()->routeIs('tags.*') ? 'active' : '' }}">
                        <a href="{{ route('tags.index') }}" class="menu-link">
                            <span class="menu-text">Tags</span>
                        </a>
                    </li>
                    @endif
                    @if($canHouseholds)
                    <li class="menu-item {{ request()->routeIs('households.*') ? 'active' : '' }}">
                        <a href="{{ route('households.index') }}" class="menu-link">
                            <span class="menu-text">Household</span>
                        </a>
                    </li>
                    @endif
                </ul>
            </li>
            @endif

            @can('blotter.view')
            <li class="menu-item {{ request()->routeIs('blotters.*') ? 'active' : '' }}">
                <a href="{{ route('blotters.index') }}" class="menu-link">
                    <span class="menu-icon"><i class="mgc_document_2_line"></i></span>
                    <span class="menu-text">Blotter</span>
                </a>
            </li>
            @endcan

            @if(auth()->user()?->canAny(['events.view', 'documents.view', 'inventory.view']))
            <li class="menu-title">Services</li>
            @endif

            @can('events.view')
            <li class="menu-item {{ request()->routeIs('events.*') ? 'active' : '' }}">
                <a href="{{ route('events.index') }}" class="menu-link">
                    <span class="menu-icon"><i class="mgc_calendar_line"></i></span>
                    <span class="menu-text">Events</span>
                </a>
            </li>
            @endcan

            @can('documents.view')
            <li class="menu-item {{ request()->routeIs('documents.requests.*') || request()->routeIs('documents.types.*') || request()->routeIs('documents.dashboard') ? 'active open' : '' }}">
                <a href="javascript:void(0)" data-fc-type="collapse" class="menu-link">
                    <span class="menu-icon"><i class="mgc_document_2_line"></i></span>
                    <span class="menu-text">Documents</span>
                    <span class="menu-arrow"></span>
                </a>
                <ul class="sub-menu {{ request()->routeIs('documents.requests.*') || request()->routeIs('documents.types.*') || request()->routeIs('documents.dashboard') ? '' : 'hidden' }}">
                    <li class="menu-item {{ request()->routeIs('documents.dashboard') ? 'active' : '' }}">
                        <a href="{{ route('documents.dashboard') }}" class="menu-link">
                            <span class="menu-text">Dashboard</span>
                        </a>
                    </li>
                    <li class="menu-item {{ request()->routeIs('documents.requests.*') ? 'active' : '' }}">
                        <a href="{{ route('documents.requests.index') }}" class="menu-link">
                            <span class="menu-text">Requests</span>
                        </a>
                    </li>
                    <li class="menu-item {{ request()->routeIs('documents.types.*') ? 'active' : '' }}">
                        <a href="{{ route('documents.types.index') }}" class="menu-link">
                            <span class="menu-text">Document Types</span>
                        </a>
                    </li>
                </ul>
            </li>
            @endcan

            @can('inventory.view')
            <li class="menu-item {{ request()->routeIs('inventory.*') ? 'active open' : '' }}">
                <a href="javascript:void(0)" data-fc-type="collapse" class="menu-link">
                    <span class="menu-icon"><i class="mgc_box_3_line"></i></span>
                    <span class="menu-text">Inventory</span>
                    <span class="menu-arrow"></span>
                </a>
                <ul class="sub-menu {{ request()->routeIs('inventory.*') ? '' : 'hidden' }}">
                    <li class="menu-item {{ request()->routeIs('inventory.releases.*') ? 'active' : '' }}">
                        <a href="{{ route('inventory.releases.index') }}" class="menu-link">
                            <span class="menu-text">Releases</span>
                        </a>
                    </li>
                    <li class="menu-item {{ request()->routeIs('inventory.items.*') ? 'active' : '' }}">
                        <a href="{{ route('inventory.items.index') }}" class="menu-link">
                            <span class="menu-text">Items</span>
                        </a>
                    </li>
                    <li class="menu-item {{ request()->routeIs('inventory.categories.*') ? 'active' : '' }}">
                        <a href="{{ route('inventory.categories.index') }}" class="menu-link">
                            <span class="menu-text">Categories</span>
                        </a>
                    </li>
                </ul>
            </li>
            @endcan

            @can('budget.view')
            <li class="menu-title">Finance</li>

            <li class="menu-item {{ request()->routeIs('budget.*') ? 'active' : '' }}">
                <a href="javascript:void(0)" data-fc-type="collapse" class="menu-link">
                    <span class="menu-icon"><i class="mgc_wallet_3_line"></i></span>
                    <span class="menu-text">Budget</span>
                    <span class="menu-arrow"></span>
                </a>
                <ul class="sub-menu {{ request()->routeIs('budget.*') ? '' : 'hidden' }}">
                    <li class="menu-item {{ request()->routeIs('budget.index') ? 'active' : '' }}">
                        <a href="{{ route('budget.index') }}" class="menu-link">
                            <span class="menu-text">Dashboard</span>
                        </a>
                    </li>
                    <li class="menu-item {{ request()->routeIs('budget.income-estimates.*') ? 'active' : '' }}">
                        <a href="{{ route('budget.income-estimates.index') }}" class="menu-link">
                            <span class="menu-text">Income Estimates</span>
                        </a>
                    </li>
                    <li class="menu-item {{ request()->routeIs('budget.programs.*') ? 'active' : '' }}">
                        <a href="{{ route('budget.programs.index') }}" class="menu-link">
                            <span class="menu-text">Programs (PPA)</span>
                        </a>
                    </li>
                    <li class="menu-item {{ request()->routeIs('budget.line-items.*') ? 'active' : '' }}">
                        <a href="{{ route('budget.line-items.index') }}" class="menu-link">
                            <span class="menu-text">Line Items</span>
                        </a>
                    </li>
                    <li class="menu-item {{ request()->routeIs('budget.allocations.*') ? 'active' : '' }}">
                        <a href="{{ route('budget.allocations.index') }}" class="menu-link">
                            <span class="menu-text">Budget Matrix</span>
                        </a>
                    </li>
                    <li class="menu-item {{ request()->routeIs('budget.transactions.*') ? 'active' : '' }}">
                        <a href="{{ route('budget.transactions.index') }}" class="menu-link">
                            <span class="menu-text">Vouchers</span>
                        </a>
                    </li>
                    <li class="menu-item {{ request()->routeIs('budget.officers.*') ? 'active' : '' }}">
                        <a href="{{ route('budget.officers.index') }}" class="menu-link">
                            <span class="menu-text">Accountable Officers</span>
                        </a>
                    </li>
                    <li class="menu-item {{ request()->routeIs('budget.cash-advances.*') || request()->routeIs('budget.liquidations.*') ? 'active' : '' }}">
                        <a href="{{ route('budget.cash-advances.index') }}" class="menu-link">
                            <span class="menu-text">Cash Advances</span>
                            @php
                                $overdueCaCount = \App\Models\CashAdvance::where('status','open')
                                    ->where('deadline_date','<',now()->toDateString())->count();
                            @endphp
                            @if($overdueCaCount > 0)
                                <span class="menu-badge bg-danger text-white text-[10px] px-1.5 py-0.5 rounded-full ms-auto">{{ $overdueCaCount }}</span>
                            @endif
                        </a>
                    </li>
                    <li class="menu-item {{ request()->routeIs('budget.logs.*') ? 'active' : '' }}">
                        <a href="{{ route('budget.logs.index') }}" class="menu-link">
                            <span class="menu-text">Audit Log</span>
                        </a>
                    </li>
                    <li class="menu-item {{ request()->routeIs('budget.suppliers.*') ? 'active' : '' }}">
                        <a href="{{ route('budget.suppliers.index') }}" class="menu-link">
                            <span class="menu-text">Suppliers / Payees</span>
                        </a>
                    </li>
                    <li class="menu-item {{ request()->routeIs('budget.settings.*') ? 'active' : '' }}">
                        <a href="{{ route('budget.settings.index') }}" class="menu-link">
                            <span class="menu-text">Budget Settings</span>
                        </a>
                    </li>
                </ul>
            </li>
            @endcan

            @php
                $canSystem = auth()->user()?->canAny(['settings.view', 'users.view', 'roles.view', 'addresses.view']);
                $canActivityLogs = auth()->user()?->can('activity_logs.view');
            @endphp
            @if($canSystem || $canActivityLogs)
            <li class="menu-title">System</li>
            @endif

            @if($canSystem)
            <li class="menu-item {{ request()->routeIs('settings.index') || request()->routeIs('settings.update') || request()->routeIs('officials.*') || request()->routeIs('addresses.*') || request()->routeIs('users.*') || request()->routeIs('roles.*') || request()->routeIs('documents.templates.*') ? 'active' : '' }}">
                <a href="{{ route('settings.index') }}" class="menu-link">
                    <span class="menu-icon"><i class="mgc_settings_4_line"></i></span>
                    <span class="menu-text">Settings</span>
                </a>
            </li>
            @endif

            @if($canActivityLogs)
            <li class="menu-item {{ request()->routeIs('activity-logs*') ? 'active' : '' }}">
                <a href="{{ route('activity-logs.index') }}" class="menu-link">
                    <span class="menu-icon"><i class="mgc_history_line"></i></span>
                    <span class="menu-text">Activity Logs</span>
                </a>
            </li>
            @endif

        </ul>
    </div>
</div>
<!-- Sidenav Menu End  -->
