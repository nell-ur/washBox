<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="description" content="WashBox Staff Dashboard">
    <title>@yield('page-title', 'Dashboard') - WashBox Staff</title>

    <!-- Bootstrap CSS -->
    <link href="{{ asset('assets/bootstrap/css/bootstrap.min.css') }}" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="{{ asset('assets/bootstrap-icons/bootstrap-icons.css') }}">

    <!-- Leaflet.js for OpenStreetMap - Fixed paths -->
    <link rel="stylesheet" href="{{ asset('assets/leaflet/leaflet.css') }}">
    <script src="{{ asset('assets/leaflet/leaflet.js') }}"></script>
    <link rel="stylesheet" href="{{ asset('assets/leaflet/MarkerCluster.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/leaflet/MarkerCluster.Default.css') }}">
    <script src="{{ asset('assets/leaflet/leaflet.markercluster.js') }}"></script>

    <!-- Unified Layout CSS -->
    <link rel="stylesheet" href="{{ asset('assets/css/layout.css') }}">

    @stack('styles')
</head>

<body data-theme="light" data-role="staff">
    <!-- Hidden data container for JavaScript -->
    <div id="notificationSystem"
         data-unread-count-url="{{ route('staff.notifications.unread-count') }}"
         data-recent-url="{{ route('staff.notifications.recent') }}"
         data-mark-read-url="{{ url('staff/notifications') }}"
         data-mark-all-read-url="{{ route('staff.notifications.mark-all-read') }}"
         style="display: none;"></div>

    <!-- Sidebar -->
    @include('staff.components.staff-sidebar')

    <!-- Sidebar Overlay -->
    <div class="sidebar-overlay" id="sidebarOverlay" aria-hidden="true"></div>

    <!-- Main Content -->
    <div class="main-content" id="mainContent">
        <!-- Topbar -->
        <div class="topbar">
            <div class="topbar-left">
                <!-- Mobile Menu Toggle -->
                <button class="mobile-menu-btn" id="mobileMenuToggle" aria-label="Toggle sidebar">
                    <i class="bi bi-list"></i>
                </button>

                <!-- Page Title with Icon -->
                <h1 class="topbar-title">
                    <i class="bi @yield('page-icon', 'bi-speedometer2')"></i>
                    @yield('page-title', 'Dashboard')
                </h1>

                <!-- Breadcrumb Navigation -->
                <nav class="breadcrumb-nav" aria-label="breadcrumb">
                    <a href="{{ route('staff.dashboard') }}" class="breadcrumb-item">Dashboard</a>
                    @hasSection('breadcrumbs')
                        <span class="breadcrumb-divider">/</span>
                        @yield('breadcrumbs')
                    @endif
                </nav>
            </div>

            <div class="topbar-right">
                <!-- Theme Toggle -->
                <button class="theme-toggle" id="themeToggle" aria-label="Toggle theme">
                    <i class="bi bi-moon"></i>
                    <i class="bi bi-sun"></i>
                </button>

                <!-- Notification Bell -->
                <div class="notification-wrapper">
                    <button class="notification-bell" type="button" id="notificationDropdown"
                        data-bs-toggle="dropdown" data-bs-auto-close="outside" aria-expanded="false"
                        aria-label="Notifications">
                        <i class="bi bi-bell"></i>
                        <span class="notification-badge" id="notificationBadge" style="display: none;">0</span>
                    </button>

                    <!-- Notification Dropdown -->
                    <div class="dropdown-menu dropdown-menu-end notification-dropdown staff-enhanced p-0"
                        aria-labelledby="notificationDropdown">

                        <!-- Header -->
                        <div class="notification-dropdown-header">
                            <h6><i class="bi bi-bell-fill me-2"></i>Notifications</h6>
                            <button class="btn btn-sm btn-light rounded-pill px-3 mark-all-read-btn" id="markAllReadBtn"
                                    style="display: none;">
                                Mark all read
                            </button>
                        </div>

                        <!-- Notification List -->
                        <div class="notification-list" id="notificationList">
                            <!-- Loading State -->
                            <div class="notification-empty" id="notificationLoading">
                                <div class="spinner-border spinner-border-sm text-primary" role="status">
                                    <span class="visually-hidden">Loading...</span>
                                </div>
                                <p class="mt-2">Loading notifications...</p>
                            </div>

                            <!-- Empty State -->
                            <div class="notification-empty" id="notificationEmpty" style="display: none;">
                                <i class="bi bi-bell-slash"></i>
                                <p>No notifications yet</p>
                            </div>

                            <!-- Notifications will be rendered here -->
                            <div id="notificationContent"></div>
                        </div>

                        <!-- Footer -->
                        <div class="notification-dropdown-footer">
                            <a href="{{ route('staff.notifications.index') }}">
                                View All Notifications <i class="bi bi-arrow-right ms-1"></i>
                            </a>
                        </div>
                    </div>
                </div>

                <!-- User Menu -->
                <div class="user-menu-wrapper">
                    <div class="user-menu dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">
                        <div class="user-avatar" id="userAvatar">
                            {{ strtoupper(substr(auth()->user()->name ?? 'S', 0, 1)) }}
                        </div>
                        <div class="user-info">
                            <span class="user-name">{{ auth()->user()->name ?? 'Staff' }}</span>
                            <span class="user-role">Staff</span>
                        </div>
                        <i class="bi bi-chevron-down"></i>
                    </div>
                    <ul class="dropdown-menu dropdown-menu-end shadow border-0 rounded-3">
                        <li>
                            <a class="dropdown-item py-2" href="{{ route('staff.profile') }}">
                                <i class="bi bi-person"></i>Profile
                            </a>
                        </li>
                        <li>
                            <a class="dropdown-item py-2" href="{{ route('staff.notifications.index') }}">
                                <i class="bi bi-bell"></i>Notifications
                                <span class="badge bg-danger rounded-pill ms-auto notification-menu-badge" style="display: none;">0</span>
                            </a>
                        </li>
                        <li>
                            <hr class="dropdown-divider">
                        </li>
                        <li>
                            <form id="logoutForm" method="POST" action="{{ route('staff.logout') }}" class="mb-0">
                                @csrf
                                <button type="submit" class="dropdown-item py-2 text-danger w-100 text-start">
                                    <i class="bi bi-box-arrow-right"></i>Logout
                                </button>
                            </form>
                        </li>
                    </ul>
                </div>
            </div>
        </div>

        <!-- Content Area -->
        <div class="content-area">
            @yield('content')
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="{{ asset('assets/bootstrap/js/bootstrap.bundle.min.js') }}"></script>
    <!-- Unified Layout JS -->
    <script src="{{ asset('assets/js/layout.js') }}"></script>

    @stack('scripts')
</body>
</html>
