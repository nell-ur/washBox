<div class="topbar d-flex justify-content-between align-items-center">
    <div class="topbar-left d-flex align-items-center gap-3">
        {{-- Unified Mobile Menu Toggle --}}
        <button class="mobile-menu-btn" id="mobileMenuToggle" aria-label="Toggle sidebar">
            <i class="bi bi-list"></i>
        </button>

        {{-- Dynamic Page Title & Icon --}}
        <h1 class="topbar-title mb-0">
            <i class="bi @yield('page-icon', 'bi-speedometer2')"></i>
            @yield('page-title', 'Dashboard')
        </h1>

        {{-- Dynamic Breadcrumbs --}}
        <nav class="breadcrumb-nav d-none d-md-flex" aria-label="breadcrumb">
            <a href="{{ route(auth()->user()->role . '.dashboard') }}" class="breadcrumb-item">Dashboard</a>
            @hasSection('breadcrumbs')
                <span class="breadcrumb-divider">/</span>
                @yield('breadcrumbs')
            @endif
        </nav>
    </div>

    <div class="topbar-right d-flex align-items-center gap-3">
        {{-- Unified Theme Toggle (Dark Mode) --}}
        <button class="theme-toggle" id="themeToggle" aria-label="Toggle theme">
            <i class="bi bi-moon"></i>
            <i class="bi bi-sun"></i>
        </button>

        {{-- Unified Notification Bell --}}
        <div class="notification-wrapper dropdown">
            <button class="notification-bell" type="button" id="notificationDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                <i class="bi bi-bell"></i>
                <span class="badge rounded-pill bg-danger notification-badge" id="notificationBadge" style="display: none;">0</span>
            </button>
            <div class="dropdown-menu dropdown-menu-end notification-dropdown shadow-lg border-0 p-0" style="width: 380px;">
                {{-- This dynamically loads the correct content based on user role --}}
                @include(auth()->user()->role . '.components.notification-dropdown')
            </div>
        </div>

        {{-- Unified User Menu --}}
        <div class="user-menu-wrapper dropdown">
            <div class="user-menu d-flex align-items-center gap-2" data-bs-toggle="dropdown" aria-expanded="false" style="cursor: pointer;">
                <div class="user-avatar">
                    {{ strtoupper(substr(auth()->user()->name ?? 'U', 0, 1)) }}
                </div>
                <div class="user-info d-none d-lg-block text-start">
                    <span class="user-name d-block fw-bold">{{ auth()->user()->name ?? 'User' }}</span>
                    <span class="user-role text-muted small text-capitalize">{{ auth()->user()->role ?? 'Staff' }}</span>
                </div>
                <i class="bi bi-chevron-down small text-muted"></i>
            </div>
            <ul class="dropdown-menu dropdown-menu-end shadow border-0 mt-2">
                <li>
                    <a class="dropdown-item py-2" href="{{ route(auth()->user()->role . '.profile') }}">
                        <i class="bi bi-person me-2"></i>Profile
                    </a>
                </li>
                <li><hr class="dropdown-divider"></li>
                <li>
                    <form action="{{ route(auth()->user()->role . '.logout') }}" method="POST" class="mb-0">
                        @csrf
                        <button type="submit" class="dropdown-item py-2 text-danger">
                            <i class="bi bi-box-arrow-right me-2"></i>Logout
                        </button>
                    </form>
                </li>
            </ul>
        </div>
    </div>
</div>