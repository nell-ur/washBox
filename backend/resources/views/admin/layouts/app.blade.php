<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="description" content="WashBox Admin Dashboard">
    <title>@yield('title', 'Dashboard') - WashBox Admin</title>

    <!-- Bootstrap CSS -->
    <link href="{{ asset('assets/bootstrap/css/bootstrap.min.css') }}" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="{{ asset('assets/bootstrap-icons/bootstrap-icons.css') }}">
    <!-- Unified Layout CSS -->
    <link rel="stylesheet" href="{{ asset('assets/css/layout.css') }}">

    @stack('styles')
</head>

<body data-theme="light" data-role="admin">
    <!-- Toast Container -->
    <div class="toast-container" id="toastContainer"></div>

    <!-- Sidebar Overlay -->
    <div class="sidebar-overlay" id="sidebarOverlay" aria-hidden="true"></div>

    <!-- Sidebar -->
    <x-sidebar role="{{ auth()->user()->role ?? 'admin' }}" />

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
                    <a href="{{ route('admin.dashboard') }}" class="breadcrumb-item">Dashboard</a>
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
                    <button class="notification-bell" type="button" id="notificationDropdown" data-bs-toggle="dropdown" data-bs-auto-close="outside" aria-expanded="false" aria-label="Notifications">
                        <i class="bi bi-bell"></i>
                        <span class="badge rounded-pill bg-danger notification-badge" id="notificationBadge" style="display: none;">
                            0
                        </span>
                    </button>

                    <div class="dropdown-menu dropdown-menu-end notification-dropdown rounded-3 p-0" aria-labelledby="notificationDropdown">
                        <!-- Header -->
                        <div class="notification-header">
                            <h6 class="mb-0">
                                <i class="bi bi-bell me-2 text-primary"></i>Notifications
                            </h6>
                            <div class="notification-actions">
                                <button class="btn btn-sm btn-outline-secondary" onclick="markAllNotificationsRead()">
                                    <i class="bi bi-check-all me-1"></i>Mark all read
                                </button>
                                <button class="btn btn-sm btn-outline-secondary" onclick="clearAllNotifications()">
                                    <i class="bi bi-trash me-1"></i>Clear all
                                </button>
                            </div>
                        </div>

                        <!-- Notifications List -->
                        <div class="notification-dropdown-list" id="notificationDropdownList">
                            <!-- Loading skeleton will be inserted here -->
                        </div>

                        <!-- Footer -->
                        <div class="border-top px-3 py-2 text-center">
                            <a href="{{ route('admin.notifications.index') }}" class="text-decoration-none small fw-semibold text-primary">
                                View All Notifications <i class="bi bi-arrow-right ms-1"></i>
                            </a>
                        </div>
                    </div>
                </div>

                <!-- User Menu -->
                <div class="user-menu-wrapper">
                    <div class="user-menu dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">
                        <div class="user-avatar" id="userAvatar">
                            {{ strtoupper(substr(auth()->user()->name ?? 'A', 0, 1)) }}
                        </div>
                        <div class="user-info">
                            <span class="user-name">{{ auth()->user()->name ?? 'Admin' }}</span>
                            <span class="user-role">{{ ucfirst(auth()->user()->role ?? 'Administrator') }}</span>
                        </div>
                        <i class="bi bi-chevron-down"></i>
                    </div>
                    <ul class="dropdown-menu dropdown-menu-end shadow border-0 rounded-3">
                        <li>
                            <a class="dropdown-item py-2" href="{{ route('admin.profile') }}">
                                <i class="bi bi-person"></i>Profile
                            </a>
                        </li>
                        <li>
                            <a class="dropdown-item py-2" href="{{ route('admin.settings') }}">
                                <i class="bi bi-gear"></i>Settings
                            </a>
                        </li>
                        <li>
                            <hr class="dropdown-divider">
                        </li>
                        <li>
                            <form id="logoutForm" action="{{ route('admin.logout') }}" method="POST" class="mb-0">
                                @csrf
                                <button type="submit" class="dropdown-item py-2 text-danger">
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
            <x-alert />
            @yield('content')
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="{{ asset('assets/bootstrap/js/bootstrap.bundle.min.js') }}"></script>
    <!-- jQuery -->
    <script src="{{ asset('assets/jquery/jquery-3.7.0.min.js') }}"></script>
    <!-- Unified Layout JS -->
    <script src="{{ asset('assets/js/layout.js') }}"></script>

    @stack('scripts')
</body>
</html>
