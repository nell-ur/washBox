@props(['role' => 'staff'])

<aside class="sidebar shadow" id="sidebar">
    {{-- Desktop Toggle Button --}}
    <div class="sidebar-toggle-btn" id="desktopToggleBtn">
        <i class="bi bi-chevron-left"></i>
    </div>

    <div class="sidebar-brand">
        <div class="d-flex flex-column align-items-center text-center py-2 position-relative">
            {{-- Logo Container --}}
            <div class="logo-circle mb-2">
                <img src="{{ asset('images/logo.png') }}"
                     alt="WashBox Logo">
            </div>

            {{-- Brand Text --}}
            <div class="brand-text">
                <h3 class="text-white mb-0">WASHBOX</h3>
                @auth
                    @if(auth()->user()->branch)
                        <small class="text-white-50 d-block mt-1">
                            {{ auth()->user()->branch->name }} BRANCH
                        </small>
                    @endif
                @endauth
            </div>

            {{-- Mobile Close Button --}}
            <button class="btn btn-link text-white d-md-none p-0 position-absolute"
                    id="sidebarClose"
                    style="top: 10px; right: 15px;">
                <i class="bi bi-x-lg"></i>
            </button>
        </div>
    </div>

    <ul class="sidebar-menu">
        {{-- Dashboard --}}
        <li class="nav-item">
            <a href="{{ route('staff.dashboard') }}"
               class="nav-link {{ request()->routeIs('staff.dashboard') ? 'active' : '' }}">
                <i class="bi bi-speedometer2"></i>
                <span class="menu-text">Dashboard</span>
            </a>
        </li>

        {{-- Operations Section --}}
        <li class="nav-label">Operations</li>

        {{-- New Laundry --}}
        <li class="nav-item">
            <a href="{{ route('staff.laundries.create') }}"
               class="nav-link {{ request()->routeIs('staff.laundries.create') ? 'active' : '' }}">
                <i class="bi bi-plus-circle text-info"></i>
                <span class="menu-text">New Laundry</span>
            </a>
        </li>

        {{-- Laundries --}}
        <li class="nav-item">
            <a href="{{ route('staff.laundries.index') }}"
               class="nav-link {{ request()->routeIs('staff.laundries.*') && !request()->routeIs('staff.laundries.create') ? 'active' : '' }}">
                <i class="bi bi-basket"></i>
                <span class="menu-text">Laundries</span>
            </a>
        </li>

        {{-- Pickups --}}
        <li class="nav-item">
            <a href="{{ route('staff.pickups.index') }}"
               class="nav-link {{ request()->routeIs('staff.pickups.*') ? 'active' : '' }}">
                <i class="bi bi-truck"></i>
                <span class="menu-text">Pickups</span>
            </a>
        </li>

        {{-- Customers --}}
        <li class="nav-item">
            <a href="{{ route('staff.customers.index') }}"
               class="nav-link {{ request()->routeIs('staff.customers.*') ? 'active' : '' }}">
                <i class="bi bi-people"></i>
                <span class="menu-text">Customers</span>
            </a>
        </li>

        {{-- Services & Add-ons Dropdown --}}
        @php
            $isServicesActive = request()->routeIs('staff.services.*');
            $isAddonsActive = request()->routeIs('staff.addons.*');
            $isServicesDropdownActive = $isServicesActive || $isAddonsActive;
        @endphp

        <li class="nav-item dropdown-container {{ $isServicesDropdownActive ? 'active' : '' }}">
            <a href="javascript:void(0);"
               class="nav-link dropdown-toggle {{ $isServicesDropdownActive ? 'active' : '' }}"
               onclick="toggleServicesDropdown(this)">
                <i class="bi bi-grid-3x3-gap-fill text-primary"></i>
                <span class="menu-text">Services & Add-ons</span>
                <span class="badge-container">
                    <i class="bi bi-chevron-down dropdown-arrow {{ $isServicesDropdownActive ? 'rotated' : '' }}"></i>
                </span>
            </a>
            <ul class="dropdown-menu-items {{ $isServicesDropdownActive ? 'show' : '' }}" id="servicesDropdown">
                <li class="nav-item">
                    <a href="{{ route('staff.services.index') }}"
                       class="nav-link {{ $isServicesActive ? 'active' : '' }}">
                        <i class="bi bi-droplet"></i>
                        <span class="menu-text">Services</span>
                        @php
                            $servicesCount = \App\Models\Service::count();
                        @endphp
                        @if($servicesCount > 0)
                            <span class="badge bg-primary rounded-pill ms-auto">{{ $servicesCount }}</span>
                        @endif
                    </a>
                </li>
                <li class="nav-item">
                    <a href="{{ route('staff.addons.index') }}"
                       class="nav-link {{ $isAddonsActive ? 'active' : '' }}">
                        <i class="bi bi-plus-circle"></i>
                        <span class="menu-text">Add-ons</span>
                        @php
                            $addonsCount = \App\Models\AddOn::count();
                        @endphp
                        @if($addonsCount > 0)
                            <span class="badge bg-success rounded-pill ms-auto">{{ $addonsCount }}</span>
                        @endif
                    </a>
                </li>
            </ul>
        </li>

        {{-- Promotions --}}
        <li class="nav-item">
            <a href="{{ route('staff.promotions.index') }}"
               class="nav-link {{ request()->routeIs('staff.promotions.*') ? 'active' : '' }}">
                <i class="bi bi-megaphone text-warning"></i>
                <span class="menu-text">Promotions</span>
                @php
                    $activePromotions = \App\Models\Promotion::where('is_active', true)->count();
                @endphp
                @if($activePromotions > 0)
                    <span class="badge bg-warning text-dark rounded-pill ms-auto">{{ $activePromotions }}</span>
                @endif
            </a>
        </li>

        {{-- Unclaimed --}}
        <li class="nav-item">
            <a href="{{ route('staff.unclaimed.index') }}"
               class="nav-link {{ request()->routeIs('staff.unclaimed.*') ? 'active' : '' }}">
                <i class="bi bi-exclamation-octagon text-warning"></i>
                <span class="menu-text">Unclaimed</span>
                @php
                    $unclaimedCount = \App\Models\Laundry::where('status', 'ready_for_pickup')->count();
                @endphp
                @if($unclaimedCount > 0)
                    <span class="badge bg-danger rounded-pill ms-auto">{{ $unclaimedCount }}</span>
                @endif
            </a>
        </li>

        {{-- Analytics Section --}}
        <li class="nav-label">Insights</li>

        {{-- Analytics --}}
        <li class="nav-item">

        {{-- Branches (View Only) --}}
        <li class="nav-item">
            <a href="{{ route('staff.branches.index') }}"
               class="nav-link {{ request()->routeIs('staff.branches.*') ? 'active' : '' }}">
                <i class="bi bi-building text-info"></i>
                <span class="menu-text">Branches</span>
            </a>
        </li>

        <li class="nav-divider"><hr class="border-white opacity-10 mx-3"></li>

        {{-- Profile Section --}}
        <li class="nav-label">Account</li>

        {{-- Profile --}}
        <li class="nav-item">
            <a href="{{ route('staff.profile') }}"
               class="nav-link {{ request()->routeIs('staff.profile') ? 'active' : '' }}">
                <i class="bi bi-person-circle"></i>
                <span class="menu-text">Profile</span>
            </a>
        </li>

    </ul>
</aside>

