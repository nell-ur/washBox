@extends('staff.layouts.staff')

@section('page-title', 'Dashboard Overview')

@push('styles')
    {{-- Leaflet CSS - Note the correct path to leaflet.css in leaflet directory --}}
    <link rel="stylesheet" href="{{ asset('assets/leaflet/leaflet.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/leaflet/MarkerCluster.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/leaflet/MarkerCluster.Default.css') }}">
    {{-- Your local CSS --}}
    <link rel="stylesheet" href="{{ asset('assets/css/staff.css') }}">
@endpush

@section('content')
    <div class="container-fluid px-4 py-4 dashboard-modern-wrapper">

        {{-- Enhanced Dashboard Header --}}
        <div class="glass-header mb-4 shadow-sm">
            <div class="row align-items-center">
                <div class="col-lg-8">
                    <div class="d-flex align-items-center gap-3">
                        <div>
                            <h2 class="fw-bold text-dark mb-1">Dashboard Overview</h2>
                            <p class="text-muted small mb-0 d-flex align-items-center gap-2">
                                <span class="badge-status-live">
                                    <span class="pulse-dot"></span> LIVE
                                </span>
                                <span class="v-divider"></span>
                                <i class="bi bi-calendar-check text-primary-blue"></i>
                                <span id="current-date" class="fw-600">{{ now()->format('l, F j, Y') }}</span>
                                <span class="v-divider"></span>
                                <span class="text-success fw-bold" style="font-size: 0.75rem;">
                                    <i class="bi bi-arrow-repeat me-1"></i><span id="last-sync">Live Sync</span>
                                </span>
                            </p>
                        </div>
                    </div>
                </div>
                <div class="col-lg-4 mt-3 mt-lg-0">
                    <div class="d-flex gap-2 justify-content-lg-end align-items-center flex-wrap">
                        <button onclick="window.print()"
                            class="btn btn-sm rounded-pill btn-outline-primary d-flex align-items-center">
                            <i class="bi bi-printer me-2"></i>
                            <span>Print Report</span>
                        </button>
                        <a href="{{ route('staff.dashboard.export') }}"
                            class="btn btn-sm rounded-pill btn-primary d-flex align-items-center">
                            <i class="bi bi-download me-2"></i>
                            Export Data
                        </a>
                    </div>
                </div>
            </div>
        </div>

        {{-- Services Carousel Section --}}
        <div class="services-carousel-container shadow-sm">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <div>
                    <h5 class="fw-bold mb-1" style="color: var(--primary-color);">
                        <i class="bi bi-grid-3x3-gap-fill me-2"></i>Our Services
                    </h5>
                    <p class="text-muted small mb-0">Quick access to all laundry services and add-ons</p>
                </div>
                <div class="carousel-controls">
                    <button class="carousel-btn" onclick="document.querySelector('.services-carousel').scrollBy({left: -250, behavior: 'smooth'})">
                        <i class="bi bi-chevron-left"></i>
                    </button>
                    <button class="carousel-btn" onclick="document.querySelector('.services-carousel').scrollBy({left: 250, behavior: 'smooth'})">
                        <i class="bi bi-chevron-right"></i>
                    </button>
                </div>
            </div>

            <div class="services-carousel">
                @php
                    $allServices = $services ?? \App\Models\Service::withCount('laundries')->take(8)->get();
                    $allAddons = $addons ?? \App\Models\AddOn::withCount('laundries')->take(4)->get();
                @endphp

                @foreach($allServices as $service)
                    <div class="service-card-carousel" onclick="window.location.href='{{ route('staff.services.show', $service) }}'">
                        <span class="service-badge {{ $service->pricing_type === 'per_load' ? 'per-load' : 'per-kg' }}">
                            {{ $service->pricing_type === 'per_load' ? 'Per Load' : 'Per Kg' }}
                        </span>
                        <div class="service-icon-wrapper">
                            <i class="bi bi-droplet"></i>
                        </div>
                        <h6 class="fw-bold mb-1">{{ Str::limit($service->name, 20) }}</h6>
                        <div class="service-price">
                            @if($service->pricing_type === 'per_load')
                                ₱{{ number_format($service->price_per_load, 2) }}/load
                            @else
                                ₱{{ number_format($service->price_per_piece, 2) }}/piece
                            @endif
                        </div>
                        <div class="service-description">
                            {{ $service->description ?? 'No description available' }}
                        </div>
                        <div class="service-stats">
                            <div class="service-stat-item">
                                <div class="service-stat-value">{{ $service->laundries_count ?? 0 }}</div>
                                <div class="text-muted">Used</div>
                            </div>
                            <div class="service-stat-item">
                                <div class="service-stat-value">{{ $service->turnaround_time }}h</div>
                                <div class="text-muted">Turnaround</div>
                            </div>
                        </div>
                    </div>
                @endforeach

                @foreach($allAddons as $addon)
                    <div class="service-card-carousel" onclick="window.location.href='{{ route('staff.addons.show', $addon) }}'">
                        <span class="service-badge per-load" style="background: #f0fdf4; color: #166534;">
                            Add-on
                        </span>
                        <div class="service-icon-wrapper" style="background: linear-gradient(135deg, #10B981 0%, #34D399 100%);">
                            <i class="bi bi-plus-circle"></i>
                        </div>
                        <h6 class="fw-bold mb-1">{{ Str::limit($addon->name, 20) }}</h6>
                        <div class="service-price text-success">
                            ₱{{ number_format($addon->price, 2) }}
                        </div>
                        <div class="service-description">
                            {{ $addon->description ?? 'No description available' }}
                        </div>
                        <div class="service-stats">
                            <div class="service-stat-item">
                                <div class="service-stat-value">{{ $addon->laundries_count ?? 0 }}</div>
                                <div class="text-muted">Used</div>
                            </div>
                            <div class="service-stat-item">
                                <div class="service-stat-value">
                                    <i class="bi bi-plus text-success"></i>
                                </div>
                                <div class="text-muted">Add-on</div>
                            </div>
                        </div>
                    </div>
                @endforeach

                <div class="service-card-carousel" style="background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%); border: 2px dashed var(--primary-color);" onclick="window.location.href='{{ route('staff.services.index') }}'">
                    <div class="service-icon-wrapper" style="background: var(--primary-color); opacity: 0.8;">
                        <i class="bi bi-arrow-right"></i>
                    </div>
                    <h6 class="fw-bold mb-1 text-center">View All Services</h6>
                    <div class="service-description text-center">
                        Browse all available laundry services and add-ons
                    </div>
                    <div class="text-center mt-3">
                        <span class="badge bg-primary">View All →</span>
                    </div>
                </div>
            </div>
        </div>

        {{-- Filters Card --}}
        <div class="modern-card shadow-sm mb-4">
            <div class="card-body-modern">
                <form method="GET" action="{{ route('staff.dashboard') }}" class="row g-3 align-items-end">
                    <div class="col-md-4">
                        <label for="date-range-select" class="form-label fw-600 text-slate-700">
                            <i class="bi bi-calendar me-1"></i>Date Range
                        </label>
                        <select id="date-range-select" name="date_range" class="form-select modern-select" onchange="this.form.submit()">
                            <option value="today" {{ $current_filters['date_range'] == 'today' ? 'selected' : '' }}>Today
                            </option>
                            <option value="yesterday" {{ $current_filters['date_range'] == 'yesterday' ? 'selected' : '' }}>
                                Yesterday</option>
                            <option value="last_7_days" {{ $current_filters['date_range'] == 'last_7_days' ? 'selected' : '' }}>Last 7 Days</option>
                            <option value="last_30_days" {{ $current_filters['date_range'] == 'last_30_days' ? 'selected' : '' }}>Last 30 Days</option>
                            <option value="this_month" {{ $current_filters['date_range'] == 'this_month' ? 'selected' : '' }}>
                                This Month</option>
                            <option value="last_month" {{ $current_filters['date_range'] == 'last_month' ? 'selected' : '' }}>
                                Last Month</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label for="branch-select" class="form-label fw-600 text-slate-700">
                            <i class="bi bi-shop me-1"></i>Branch
                        </label>
                        <select id="branch-select" name="branch_id" class="form-select modern-select" onchange="this.form.submit()">
                            <option value="">All Branches</option>
                            @foreach($branchOptions as $branch)
                                <option value="{{ $branch->id }}" {{ $current_filters['branch_id'] == $branch->id ? 'selected' : '' }}>
                                    {{ $branch->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-4">
                        @if($current_filters['date_range'] != 'last_30_days' || $current_filters['branch_id'])
                            <a href="{{ route('staff.dashboard') }}"
                                class="btn btn-secondary btn-sm w-100 d-flex align-items-center justify-content-center">
                                <i class="bi bi-arrow-clockwise me-2"></i>Reset Filters
                            </a>
                        @endif
                    </div>
                </form>
            </div>
        </div>

        {{-- Alerts Section --}}
        @if(count($alerts) > 0)
            <div class="row g-4 mb-4">
                @foreach($alerts as $alert)
                    <div class="col-12">
                        <div class="alert-modern alert-{{ $alert['type'] }} shadow-sm">
                            <div class="d-flex align-items-center">
                                <div class="alert-icon-modern">
                                    <i class="bi bi-{{ $alert['icon'] }}"></i>
                                </div>
                                <div class="flex-grow-1 ms-3">
                                    <h6 class="alert-title-modern mb-1">{{ $alert['title'] }}</h6>
                                    <p class="alert-text-modern mb-0">{{ $alert['message'] }}</p>
                                </div>
                                @if(isset($alert['action']))
                                    <div class="ms-3">
                                        <a href="{{ $alert['action'] }}" class="btn btn-sm btn-{{ $alert['type'] }}">
                                            {{ $alert['action_text'] ?? 'View Details' }}
                                        </a>
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif

        {{-- Main KPI Cards --}}
        <div class="row g-4 mb-6">
            {{-- Today's Revenue --}}
            <div class="col-md-6 col-lg-3" data-kpi-card="revenue">
                <div class="kpi-card-modern shadow-sm">
                    <div class="kpi-card-inner">
                        <div class="d-flex justify-content-between">
                            <div class="kpi-icon-glow icon-blue">
                                <i class="bi bi-peso text-primary-blue"></i>
                            </div>
                            <div class="dropdown">
                                <button class="btn btn-link p-0" type="button" data-bs-toggle="dropdown" aria-label="Revenue options">
                                    <i class="bi bi-three-dots-vertical text-muted"></i>
                                </button>
                                <ul class="dropdown-menu">
                                    <li><a class="dropdown-item"
                                            href="{{ route('staff.laundries.index') }}?date={{ now()->format('Y-m-d') }}">View
                                            Today's Laundries</a></li>
                                    <li><a class="dropdown-item"
                                            href="{{ route('staff.reports.index') }}?period=today">Today's Report</a></li>
                                </ul>
                            </div>
                        </div>
                        <div class="mt-3">
                            <span class="kpi-label">Today's Revenue</span>
                            <h2 class="kpi-value text-slate-800" data-kpi="todayRevenue">
                                ₱{{ number_format($kpis['today_revenue']['value'], 2) }}</h2>
                            <div class="kpi-trend {{ $kpis['today_revenue']['change'] >= 0 ? 'up' : 'down' }}">
                                <i
                                    class="bi {{ $kpis['today_revenue']['change'] >= 0 ? 'bi-arrow-up' : 'bi-arrow-down' }} me-1"></i>
                                <span>{{ number_format(abs($kpis['today_revenue']['change']), 1) }}% vs
                                    {{ $kpis['today_revenue']['vs'] }}</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Monthly Revenue --}}
            <div class="col-md-6 col-lg-3" data-kpi-card="monthly">
                <div class="kpi-card-modern shadow-sm">
                    <div class="kpi-card-inner">
                        <div class="d-flex justify-content-between">
                            <div class="kpi-icon-glow icon-green">
                                <i class="bi bi-graph-up-arrow text-success"></i>
                            </div>
                            <div class="dropdown">
                                <button class="btn btn-link p-0" type="button" data-bs-toggle="dropdown" aria-label="Monthly options">
                                    <i class="bi bi-three-dots-vertical text-muted"></i>
                                </button>
                                <ul class="dropdown-menu">
                                    <li><a class="dropdown-item"
                                            href="{{ route('staff.reports.index') }}?period=month">Monthly Report</a></li>
                                    <li><a class="dropdown-item"
                                            href="{{ route('staff.laundries.index') }}?status=paid">View Paid Laundries</a>
                                    </li>
                                </ul>
                            </div>
                        </div>
                        <div class="mt-3">
                            <span class="kpi-label">This Month</span>
                            <h2 class="kpi-value text-slate-800">₱{{ number_format($kpis['monthly_revenue']['value'], 2) }}
                            </h2>
                            <div class="kpi-trend {{ $kpis['monthly_revenue']['change'] >= 0 ? 'up' : 'down' }}">
                                <i
                                    class="bi {{ $kpis['monthly_revenue']['change'] >= 0 ? 'bi-arrow-up' : 'bi-arrow-down' }} me-1"></i>
                                <span>{{ number_format(abs($kpis['monthly_revenue']['change']), 1) }}% vs
                                    {{ $kpis['monthly_revenue']['vs'] }}</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Unclaimed Value --}}
            @if($unclaimed['total_value'] > 0)
                <div class="col-md-6 col-lg-3" data-kpi-card="unclaimed">
                    <a href="{{ route('staff.unclaimed.index') }}" class="text-decoration-none">
                        <div class="kpi-card-modern shadow-sm border-danger-soft">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <div class="kpi-icon-glow icon-red">
                                    <i class="bi bi-exclamation-triangle text-danger"></i>
                                </div>
                                <span class="badge rounded-pill bg-danger-soft text-danger">RISK</span>
                            </div>
                            <span class="kpi-label">Unclaimed Value</span>
                            <h2 class="kpi-value text-danger">₱{{ number_format($unclaimed['total_value'], 2) }}</h2>
                            <div class="kpi-trend down">
                                <i class="bi bi-exclamation-circle me-1"></i>
                                <span>{{ $unclaimed['total_count'] }} laundries at risk</span>
                            </div>
                            <small class="text-muted d-block mt-1">Click to manage unclaimed items</small>
                        </div>
                    </a>
                </div>
            @endif

            {{-- Active Laundries --}}
            <div class="col-md-6 col-lg-3" data-kpi-card="laundries">
                <div class="kpi-card-modern shadow-sm">
                    <div class="kpi-card-inner">
                        <div class="d-flex justify-content-between">
                            <div class="kpi-icon-glow icon-indigo">
                                <i class="bi bi-box-seam text-indigo"></i>
                            </div>
                            <div class="dropdown">
                                <button class="btn btn-link p-0" type="button" data-bs-toggle="dropdown" aria-label="Laundries options">
                                    <i class="bi bi-three-dots-vertical text-muted"></i>
                                </button>
                                <ul class="dropdown-menu">
                                    <li><a class="dropdown-item"
                                            href="{{ route('staff.laundries.index') }}?status=active">View Active
                                            Laundries</a></li>
                                    <li><a class="dropdown-item" href="{{ route('staff.laundries.create') }}">Create New
                                            Laundry</a></li>
                                </ul>
                            </div>
                        </div>
                        <div class="mt-3">
                            <span class="kpi-label">Active Laundries</span>
                            <h2 class="kpi-value text-slate-800">{{ $kpis['active_laundries']['value'] }}</h2>
                            <div class="kpi-trend up">
                                <i class="bi bi-arrow-up-right me-1"></i>
                                <span>Across all branches</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Ready for Pickup --}}
            <div class="col-md-6 col-lg-3" data-kpi-card="ready">
                <div class="kpi-card-modern shadow-sm kpi-warning">
                    <div class="kpi-card-inner">
                        <div class="d-flex justify-content-between">
                            <div class="kpi-icon-glow icon-orange">
                                <i class="bi bi-bag-check text-warning"></i>
                            </div>
                            <div class="dropdown">
                                <button class="btn btn-link p-0" type="button" data-bs-toggle="dropdown" aria-label="Ready for pickup options">
                                    <i class="bi bi-three-dots-vertical text-muted"></i>
                                </button>
                                <ul class="dropdown-menu">
                                    <li><a class="dropdown-item"
                                            href="{{ route('staff.laundries.index') }}?status=ready">View Ready
                                            Laundries</a></li>
                                    <li><a class="dropdown-item" href="{{ route('staff.pickups.index') }}">Manage
                                            Pickups</a></li>
                                </ul>
                            </div>
                        </div>
                        <div class="mt-3">
                            <span class="kpi-label">Ready for Pickup</span>
                            <h2 class="kpi-value text-slate-800">{{ $kpis['ready_for_pickup']['value'] }}</h2>
                            <div class="kpi-trend down">
                                <i class="bi bi-clock me-1"></i>
                                <span>Avg: {{ number_format($kpis['ready_for_pickup']['avg_wait_days'], 1) }} days
                                    old</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Completed Today --}}
            <div class="col-md-6 col-lg-3" data-kpi-card="completed">
                <div class="kpi-card-modern shadow-sm kpi-success">
                    <div class="kpi-card-inner">
                        <div class="d-flex justify-content-between">
                            <div class="kpi-icon-glow icon-green">
                                <i class="bi bi-check2-all text-success"></i>
                            </div>
                            <div class="dropdown">
                                <button class="btn btn-link p-0" type="button" data-bs-toggle="dropdown" aria-label="Completed options">
                                    <i class="bi bi-three-dots-vertical text-muted"></i>
                                </button>
                                <ul class="dropdown-menu">
                                    <li><a class="dropdown-item"
                                            href="{{ route('staff.laundries.index') }}?status=completed&date={{ now()->format('Y-m-d') }}">View
                                            Today's Completed</a></li>
                                </ul>
                            </div>
                        </div>
                        <div class="mt-3">
                            <span class="kpi-label">Completed Today</span>
                            <h2 class="kpi-value text-slate-800">{{ $kpis['completed_today']['value'] }}</h2>
                            <div class="kpi-trend up">
                                <i class="bi bi-check-circle me-1"></i>
                                <span>Successfully delivered</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Total Customers --}}
            <div class="col-md-6 col-lg-3" data-kpi-card="customers">
                <div class="kpi-card-modern shadow-sm">
                    <div class="kpi-card-inner">
                        <div class="d-flex justify-content-between">
                            <div class="kpi-icon-glow icon-purple">
                                <i class="bi bi-people text-purple"></i>
                            </div>
                            <div class="dropdown">
                                <button class="btn btn-link p-0" type="button" data-bs-toggle="dropdown" aria-label="Customers options">
                                    <i class="bi bi-three-dots-vertical text-muted"></i>
                                </button>
                                <ul class="dropdown-menu">
                                    <li><a class="dropdown-item" href="{{ route('staff.customers.index') }}">View All
                                            Customers</a></li>
                                    <li><a class="dropdown-item" href="{{ route('staff.customers.create') }}">Add New
                                            Customer</a></li>
                                </ul>
                            </div>
                        </div>
                        <div class="mt-3">
                            <span class="kpi-label">Total Customers</span>
                            <h2 class="kpi-value text-slate-800">{{ number_format($kpis['total_customers']['value']) }}</h2>
                            <div class="kpi-trend up">
                                <i class="bi bi-plus-circle me-1"></i>
                                <span>+{{ $kpis['total_customers']['new_this_month'] }} this month</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Pending Pickups --}}
            <div class="col-md-6 col-lg-3" data-kpi-card="pickups">
                <a href="{{ route('staff.pickups.index') }}" class="text-decoration-none">
                    <div class="kpi-card-modern shadow-sm">
                        <div class="kpi-card-inner">
                            <div class="d-flex justify-content-between">
                                <div class="kpi-icon-glow icon-pink">
                                    <i class="bi bi-truck text-pink"></i>
                                </div>
                                <div class="dropdown">
                                    <button class="btn btn-link p-0" type="button" data-bs-toggle="dropdown" aria-label="Pickups options">
                                        <i class="bi bi-three-dots-vertical text-muted"></i>
                                    </button>
                                    <ul class="dropdown-menu">
                                        <li><a class="dropdown-item" href="{{ route('staff.pickups.index') }}">View All
                                                Pickups</a></li>
                                    </ul>
                                </div>
                            </div>
                            <div class="mt-3">
                                <span class="kpi-label">Pending Pickups</span>
                                <h2 class="kpi-value text-slate-800">{{ $kpis['pending_pickups']['value'] }}</h2>
                                <div class="kpi-trend up">
                                    <i class="bi bi-arrow-right-circle me-1"></i>
                                    <span>Click to manage</span>
                                </div>
                                <small class="text-muted d-block mt-1">
                                    <a href="{{ route('staff.pickups.index') }}" class="text-decoration-none">View Map →</a>
                                </small>
                            </div>
                        </div>
                    </div>
                </a>
            </div>
        </div>

        {{-- Enhanced Quick Actions Grid --}}
        <div class="modern-card shadow-sm mb-6">
            <div class="card-header-modern border-0 d-flex justify-content-between align-items-center">
                <div>
                    <h6 class="mb-0 fw-800 text-slate-800">Quick Actions</h6>
                    <small class="text-muted">Frequently accessed functions</small>
                </div>
                <span class="badge bg-primary-blue bg-opacity-10 text-primary-blue">
                    <i class="bi bi-lightning me-1"></i>Instant Access
                </span>
            </div>
            <div class="card-body-modern">
                <div class="row g-3">
                    @php
                        $quickActions = [
                            ['route' => 'staff.laundries.create', 'icon' => 'bi-plus-lg', 'label' => 'Create Laundry', 'desc' => 'New laundry', 'color' => 'blue'],
                            ['route' => 'staff.customers.create', 'icon' => 'bi-person-plus', 'label' => 'New Customer', 'desc' => 'Register', 'color' => 'indigo'],
                            ['route' => 'staff.pickups.index', 'icon' => 'bi-truck', 'label' => 'Pickups', 'desc' => 'Delivery', 'color' => 'cyan'],
                            ['route' => 'staff.laundries.index', 'icon' => 'bi-list-check', 'label' => 'All Laundries', 'desc' => 'Manage', 'color' => 'green'],
                            ['route' => 'staff.reports.index', 'icon' => 'bi-graph-up', 'label' => 'Reports', 'desc' => 'Analytics', 'color' => 'purple'],
                            ['route' => 'staff.branches.index', 'icon' => 'bi-shop', 'label' => 'Staff Branches', 'desc' => 'Manage Locations', 'color' => 'orange'],
                        ];
                    @endphp
                    @foreach($quickActions as $action)
                        @if(Route::has($action['route']))
                            <div class="col-6 col-md-4 col-lg-3 col-xl-2">
                                <a href="{{ route($action['route']) }}" class="launch-action-btn {{ $action['color'] }}">
                                    <div class="launch-icon shadow-sm"><i class="bi {{ $action['icon'] }}"></i></div>
                                    <h6 class="action-label mb-1">{{ $action['label'] }}</h6>
                                    <small class="text-muted">{{ $action['desc'] }}</small>
                                </a>
                            </div>
                        @endif
                    @endforeach
                </div>
            </div>
        </div>

        {{-- Enhanced Dashboard Tabs Navigation --}}
        <div class="modern-tabs-container mb-4">
            <ul class="nav nav-segmented shadow-sm" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link active" id="overview-tab" data-bs-toggle="pill" data-bs-target="#overview"
                        type="button">
                        <i class="bi bi-speedometer2 me-2"></i>Overview
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="revenue-tab" data-bs-toggle="pill" data-bs-target="#revenue" type="button">
                        <i class="bi bi-graph-up me-2"></i>Revenue
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="branches-tab" data-bs-toggle="pill" data-bs-target="#branches"
                        type="button">
                        <i class="bi bi-shop me-2"></i>Branches
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="laundries-tab" data-bs-toggle="pill" data-bs-target="#laundries"
                        type="button">
                        <i class="bi bi-basket me-2"></i>Laundries
                    </button>
                </li>
            </ul>
        </div>

        {{-- Tabs Content --}}
        <div class="tab-content">
            {{-- Enhanced Overview Tab --}}
            <div class="tab-pane fade show active" id="overview" role="tabpanel">
                <div class="row g-4">
                    {{-- Revenue Trend Chart --}}
                    <div class="col-lg-8">
                        <div class="modern-card shadow-sm h-100">
                            <div
                                class="card-header-modern bg-transparent border-0 d-flex justify-content-between align-items-center">
                                <div>
                                    <h6 class="mb-0 fw-800 text-slate-800">Revenue Trend</h6>
                                    <small class="text-muted">Last
                                        {{ $current_filters['date_range'] == 'last_30_days' ? '30 days' : '7 days' }}
                                        performance</small>
                                </div>
                                <div class="d-flex gap-2">
                                    <button class="btn btn-sm btn-outline-primary" onclick="location.reload()">
                                        <i class="bi bi-arrow-clockwise"></i>
                                    </button>
                                    <button class="btn btn-sm btn-outline-primary">
                                        <i class="bi bi-download"></i>
                                    </button>
                                </div>
                            </div>
                            <div class="card-body-modern">
                                <div class="chart-container">
                                    <canvas id="revenueTrendChart" height="80"></canvas>
                                </div>
                                <div class="row mt-4 text-center">
                                    <div class="col-4">
                                        <small class="text-muted">Average Daily</small>
                                        <h6 class="mb-0 fw-800">₱{{ number_format($revenue['per_day'], 2) }}</h6>
                                    </div>
                                    <div class="col-4">
                                        <small class="text-muted">Highest Day</small>
                                        <h6 class="mb-0 fw-800">₱{{ number_format($revenue['highest'], 2) }}</h6>
                                    </div>
                                    <div class="col-4">
                                        <small class="text-muted">Total Laundries</small>
                                        <h6 class="mb-0 fw-800">{{ number_format($revenue['laundries']) }}</h6>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Laundry Status Pipeline --}}
                    <div class="col-lg-4">
                        <div class="modern-card shadow-sm h-100">
                            <div class="card-header-modern bg-transparent border-0">
                                <h6 class="mb-0 fw-800 text-slate-800">Laundry Status Pipeline</h6>
                                <small class="text-muted">Current status distribution</small>
                            </div>
                            <div class="card-body-modern">
                                <div class="pipeline-modern">
                                    <div class="pipeline-item-modern mb-3">
                                        <div class="d-flex align-items-center justify-content-between mb-2">
                                            <div class="d-flex align-items-center">
                                                <span class="status-dot-modern" style="background: #3b82f6;"></span>
                                                <span class="pipeline-label-modern">Received</span>
                                            </div>
                                            <span class="pipeline-value-modern">{{ $pipeline['received'] ?? 0 }}
                                                laundries</span>
                                        </div>
                                        <div class="progress-bar-modern">
                                            <div class="progress-fill-modern"
                                                style="width: {{ $pipeline['received'] ?? 0 > 0 ? '100%' : '0%' }}; background: #3b82f6;">
                                            </div>
                                        </div>
                                    </div>

                                    <div class="pipeline-item-modern mb-3">
                                        <div class="d-flex align-items-center justify-content-between mb-2">
                                            <div class="d-flex align-items-center">
                                                <span class="status-dot-modern" style="background: #f59e0b;"></span>
                                                <span class="pipeline-label-modern">Ready</span>
                                            </div>
                                            <span class="pipeline-value-modern">{{ $pipeline['ready'] ?? 0 }}
                                                laundries</span>
                                        </div>
                                        <div class="progress-bar-modern">
                                            <div class="progress-fill-modern"
                                                style="width: {{ $pipeline['ready'] ?? 0 > 0 ? '100%' : '0%' }}; background: #f59e0b;">
                                            </div>
                                        </div>
                                    </div>

                                    <div class="pipeline-item-modern mb-3">
                                        <div class="d-flex align-items-center justify-content-between mb-2">
                                            <div class="d-flex align-items-center">
                                                <span class="status-dot-modern" style="background: #10b981;"></span>
                                                <span class="pipeline-label-modern">Paid</span>
                                            </div>
                                            <span class="pipeline-value-modern">{{ $pipeline['paid'] ?? 0 }}
                                                laundries</span>
                                        </div>
                                        <div class="progress-bar-modern">
                                            <div class="progress-fill-modern"
                                                style="width: {{ $pipeline['paid'] ?? 0 > 0 ? '100%' : '0%' }}; background: #10b981;">
                                            </div>
                                        </div>
                                    </div>

                                    <div class="pipeline-item-modern">
                                        <div class="d-flex align-items-center justify-content-between mb-2">
                                            <div class="d-flex align-items-center">
                                                <span class="status-dot-modern" style="background: #6366f1;"></span>
                                                <span class="pipeline-label-modern">Completed Today</span>
                                            </div>
                                            <span class="pipeline-value-modern">{{ $pipeline['completed'] ?? 0 }}
                                                laundries</span>
                                        </div>
                                        <div class="progress-bar-modern">
                                            <div class="progress-fill-modern"
                                                style="width: {{ $pipeline['completed'] ?? 0 > 0 ? '100%' : '0%' }}; background: #6366f1;">
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Pickup Management Panel + Logistics Map --}}
                    <div class="col-lg-5">
                        <div class="modern-card shadow-sm h-100">
                            <div
                                class="card-header-modern bg-transparent border-0 d-flex justify-content-between align-items-center">
                                <div>
                                    <h6 class="mb-0 fw-800 text-slate-800">Pickup Management</h6>
                                    <small class="text-muted">Select pickups for optimized routing</small>
                                </div>
                                <span id="selectedPickupCount" class="badge bg-purple" style="display:none;">0</span>
                            </div>
                            <div class="card-body-modern">
                                {{-- Multi-Route Buttons --}}
                                <div id="multiRouteBtn" class="d-grid mb-3" style="display:none;">
                                    <button class="btn btn-purple shadow-sm" onclick="window.getOptimizedMultiRoute()">
                                        <i class="bi bi-route me-2"></i>Optimize Route (<span id="selectedCount">0</span>
                                        selected)
                                    </button>
                                </div>
                                <div class="d-grid mb-3">
                                    <button class="btn btn-primary btn-sm shadow-sm" onclick="window.autoRouteAllVisible()">
                                        <i class="bi bi-magic me-2"></i>Auto-Optimize All Pending
                                    </button>
                                </div>
                                <div class="d-flex gap-2 mb-3">
                                    <button class="btn btn-sm btn-outline-purple flex-fill"
                                        onclick="window.selectAllPending()">
                                        <i class="bi bi-check-square me-1"></i>Select All
                                    </button>
                                    <button class="btn btn-sm btn-outline-danger flex-fill"
                                        onclick="window.clearSelections()">
                                        <i class="bi bi-x-circle me-1"></i>Clear
                                    </button>
                                </div>

                                {{-- Pickup List --}}
                                <h6 class="mb-2 fw-700 text-slate-600" style="font-size:0.8rem;">Active Pickups</h6>
                                <div style="max-height:320px;overflow-y:auto;" id="pickupListContainer">
                                    @forelse($pickupLocations as $pickup)
                                        <label class="pickup-list-item d-flex align-items-center gap-2 p-2 mb-2 rounded border"
                                            for="chk-{{ $pickup->id }}" style="cursor:pointer;font-size:0.82rem;">
                                            <input type="checkbox" class="form-check-input pickup-checkbox m-0"
                                                id="chk-{{ $pickup->id }}" style="min-width:16px;"
                                                onclick="window.togglePickupSelection({{ $pickup->id }})">
                                            <div class="flex-grow-1">
                                                <div class="fw-600">{{ $pickup->customer->name ?? 'Customer' }}</div>
                                                <small class="text-muted">{{ $pickup->customer->phone ?? '' }}</small>
                                            </div>
                                            <span class="badge bg-{{ $pickup->status === 'en_route' ? 'warning' : 'danger' }}"
                                                style="font-size:0.65rem;">{{ ucfirst(str_replace('_', ' ', $pickup->status)) }}</span>
                                        </label>
                                    @empty
                                        <div class="text-center text-muted py-4">
                                            <i class="bi bi-inbox" style="font-size:2rem;"></i>
                                            <p class="mt-2 mb-0" style="font-size:0.85rem;">No active pickup requests</p>
                                        </div>
                                    @endforelse
                                </div>

                                {{-- Pickup Status Summary --}}
                                <hr class="my-3">
                                <div class="d-flex justify-content-between text-center" style="font-size:0.75rem;">
                                    <div>
                                        <div class="fw-800 fs-5">{{ $pickups['pending'] ?? 0 }}</div><small
                                            class="text-muted">Pending</small>
                                    </div>
                                    <div>
                                        <div class="fw-800 fs-5">{{ $pickups['en_route'] ?? 0 }}</div><small
                                            class="text-muted">En Route</small>
                                    </div>
                                    <div>
                                        <div class="fw-800 fs-5">{{ $pickups['picked_up'] ?? 0 }}</div><small
                                            class="text-muted">Picked Up</small>
                                    </div>
                                    <div>
                                        <div class="fw-800 fs-5">{{ $pickups['my_assigned'] ?? 0 }}</div><small
                                            class="text-muted">My Tasks</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-lg-7">
                        <div class="modern-card shadow-sm h-100">
                            <div
                                class="card-header-modern bg-transparent border-0 d-flex justify-content-between align-items-center">
                                <h6 class="mb-0 fw-800 text-slate-800">Logistics Map</h6>
                                <div class="d-flex gap-2 align-items-center">
                                    <button class="btn btn-sm btn-purple" id="multiRouteTopBtn" style="display:none;"
                                        onclick="window.getOptimizedMultiRoute()">
                                        <i class="bi bi-route"></i> Optimize (<span id="selectedCountTop">0</span>)
                                    </button>
                                    <button class="btn btn-sm btn-outline-primary" onclick="window.refreshMapMarkers()">
                                        <i class="bi bi-arrow-clockwise"></i>
                                    </button>
                                    <button class="btn btn-sm btn-primary" data-bs-toggle="modal"
                                        data-bs-target="#mapModal">
                                        <i class="bi bi-arrows-fullscreen"></i> Fullscreen
                                    </button>
                                </div>
                            </div>
                            <div class="card-body-modern p-0 position-relative">
                                {{-- Address Search Overlay --}}
                                <div id="address-search-overlay"
                                    style="position:absolute;top:12px;right:12px;z-index:1000;max-width:340px;">
                                    <div class="card shadow-lg border-0" style="border-radius:10px;">
                                        <div class="card-body p-2">
                                            <div class="input-group input-group-sm">
                                                <input type="text" id="map-address-search" class="form-control"
                                                    placeholder="Search address..." style="font-size:12px;">
                                                <button class="btn btn-primary" onclick="window.searchMapAddress()"><i
                                                        class="bi bi-geo-alt-fill"></i></button>
                                            </div>
                                            <div id="search-result-display" class="mt-2" style="display:none;">
                                                <div class="alert alert-success mb-0 py-1 px-2 small">
                                                    <div class="d-flex justify-content-between">
                                                        <div><strong id="result-address-text"></strong><br><small
                                                                id="result-coords-text" class="text-muted"></small></div>
                                                        <button class="btn btn-sm btn-link p-0"
                                                            onclick="document.getElementById('search-result-display').style.display='none'"><i
                                                                class="bi bi-x-lg"></i></button>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                {{-- Map Controls --}}
                                <div id="map-controls-container" style="position:absolute;top:12px;left:12px;z-index:1000;">
                                    <div class="route-controls" style="display:none;">
                                        <button class="btn btn-sm btn-outline-danger bg-white shadow-sm"
                                            onclick="window.clearRoute()">
                                            <i class="bi bi-x-circle me-1"></i>Clear Route
                                        </button>
                                    </div>
                                </div>

                                <div id="branchMap" style="height:480px;width:100%;border-radius:0 0 12px 12px;"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Revenue Tab --}}
            <div class="tab-pane fade" id="revenue" role="tabpanel">
                <div class="row g-4">
                    {{-- Revenue Breakdown --}}
                    <div class="col-lg-6">
                        <div class="modern-card shadow-sm h-100">
                            <div class="card-header-modern bg-transparent border-0">
                                <h6 class="mb-0 fw-800 text-slate-800">Revenue Breakdown</h6>
                                <small class="text-muted">Detailed revenue analysis</small>
                            </div>
                            <div class="card-body-modern">
                                <div class="revenue-stats-modern">
                                    <div class="revenue-stat-modern mb-3">
                                        <div class="d-flex justify-content-between align-items-center">
                                            <span class="revenue-label-modern">Total Revenue</span>
                                            <span
                                                class="revenue-value-modern">₱{{ number_format($revenue['total'] ?? 0, 2) }}</span>
                                        </div>
                                        <div class="progress-bar-modern mt-2">
                                            <div class="progress-fill-modern"
                                                style="width: 100%; background: linear-gradient(to right, #2D2B5F, #FF5C35);">
                                            </div>
                                        </div>
                                    </div>
                                    <div class="revenue-stat-modern mb-3">
                                        <div class="d-flex justify-content-between align-items-center">
                                            <span class="revenue-label-modern">Average Laundry Value</span>
                                            <span
                                                class="revenue-value-modern">₱{{ number_format($revenue['average_laundry'] ?? 0, 2) }}</span>
                                        </div>
                                        <div class="progress-bar-modern mt-2">
                                            <div class="progress-fill-modern"
                                                style="width: 75%; background: linear-gradient(to right, #3b82f6, #8b5cf6);">
                                            </div>
                                        </div>
                                    </div>
                                    <div class="revenue-stat-modern">
                                        <div class="d-flex justify-content-between align-items-center">
                                            <span class="revenue-label-modern">Laundries Processed</span>
                                            <span
                                                class="revenue-value-modern">{{ number_format($revenue['laundries']) }}</span>
                                        </div>
                                        <div class="progress-bar-modern mt-2">
                                            <div class="progress-fill-modern"
                                                style="width: 90%; background: linear-gradient(to right, #10b981, #3b82f6);">
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Performance Metrics --}}
                    <div class="col-lg-6">
                        <div class="modern-card shadow-sm h-100">
                            <div class="card-header-modern bg-transparent border-0">
                                <h6 class="mb-0 fw-800 text-slate-800">Performance Metrics</h6>
                                <small class="text-muted">Key performance indicators</small>
                            </div>
                            <div class="card-body-modern">
                                <div class="row g-3">
                                    <div class="col-6">
                                        <div class="metric-tile-modern shadow-sm border-blue">
                                            <div class="m-icon-modern icon-blue"><i class="bi bi-arrow-up-right-circle"></i>
                                            </div>
                                            <div class="mt-3">
                                                <small class="text-muted">Growth Rate</small>
                                                <h4 class="mb-0 fw-800">
                                                    {{ $kpis['monthly_revenue']['change'] >= 0 ? '+' : '' }}{{ number_format($kpis['monthly_revenue']['change'], 1) }}%
                                                </h4>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-6">
                                        <div class="metric-tile-modern shadow-sm border-green">
                                            <div class="m-icon-modern icon-green"><i class="bi bi-check2-all"></i></div>
                                            <div class="mt-3">
                                                <small class="text-muted">Completion Rate</small>
                                                <h4 class="mb-0 fw-800">
                                                    {{ $kpis['completed_today']['value'] > 0 ? '95%' : '0%' }}</h4>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-6">
                                        <div class="metric-tile-modern shadow-sm border-indigo">
                                            <div class="m-icon-modern icon-indigo"><i class="bi bi-clock-history"></i></div>
                                            <div class="mt-3">
                                                <small class="text-muted">Avg Processing</small>
                                                <h4 class="mb-0 fw-800">
                                                    {{ number_format($kpis['ready_for_pickup']['avg_wait_days'], 1) }} days
                                                </h4>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-6">
                                        <div class="metric-tile-modern shadow-sm border-cyan">
                                            <div class="m-icon-modern icon-cyan"><i class="bi bi-person-plus"></i></div>
                                            <div class="mt-3">
                                                <small class="text-muted">New Customers</small>
                                                <h4 class="mb-0 fw-800">+{{ $kpis['total_customers']['new_this_month'] }}
                                                </h4>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Branches Tab --}}
            <div class="tab-pane fade" id="branches" role="tabpanel">
                <div class="row g-4">
                    {{-- Branch Performance --}}
                    <div class="col-lg-12">
                        <div class="modern-card shadow-sm">
                            <div class="card-header-modern bg-transparent border-0">
                                <h6 class="mb-0 fw-800 text-slate-800">Branch Performance</h6>
                                <small class="text-muted">Revenue contribution by branch</small>
                            </div>
                            <div class="card-body-modern">
                                @foreach($branchPerformance as $branch)
                                    <div class="progress-item-modern mb-3">
                                        <div class="d-flex justify-content-between align-items-center mb-2">
                                            <div class="d-flex align-items-center">
                                                <div class="branch-icon-modern me-3">
                                                    <i class="bi bi-shop"></i>
                                                </div>
                                                <div>
                                                    <h6 class="mb-0">{{ $branch['name'] }}</h6>
                                                    <small class="text-muted">{{ $branch['laundries_count'] ?? 0 }}
                                                        laundries</small>
                                                </div>
                                            </div>
                                            <div class="text-end">
                                                <h6 class="mb-0">₱{{ number_format($branch['revenue'], 2) }}</h6>
                                                <small class="text-muted">{{ $branch['percentage'] }}% of total</small>
                                            </div>
                                        </div>
                                        <div class="progress-bar-modern">
                                            <div class="progress-fill-modern"
                                                style="width: {{ $branch['percentage'] }}%; background: linear-gradient(to right, #2D2B5F, #FF5C35);">
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Laundries Tab --}}
            <div class="tab-pane fade" id="laundries" role="tabpanel">
                <div class="row g-4">
                    {{-- Recent Laundries Table --}}
                    <div class="col-lg-12">
                        <div class="modern-card shadow-sm">
                            <div
                                class="card-header-modern bg-transparent border-0 d-flex justify-content-between align-items-center">
                                <div>
                                    <h6 class="mb-0 fw-800 text-slate-800">Recent Laundries</h6>
                                    <small class="text-muted">Latest laundry activities</small>
                                </div>
                                <a href="{{ route('staff.laundries.index') }}" class="btn btn-sm btn-outline-primary">
                                    View All Laundries <i class="bi bi-arrow-right ms-1"></i>
                                </a>
                            </div>
                            <div class="card-body-modern">
                                <div class="table-responsive">
                                    <table class="table table-modern">
                                        <thead>
                                            <tr>
                                                <th>Tracking #</th>
                                                <th>Customer</th>
                                                <th>Branch</th>
                                                <th>Service</th>
                                                <th>Weight</th>
                                                <th>Amount</th>
                                                <th>Status</th>
                                                <th>Date</th>
                                                <th>Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @forelse($recent_laundries as $laundry)
                                                <tr>
                                                    <td>
                                                        <strong
                                                            class="tracking-number-modern">{{ $laundry->tracking_number }}</strong>
                                                    </td>
                                                    <td>
                                                        <div class="customer-info-modern">
                                                            <div class="customer-name-modern">{{ $laundry->customer->name }}
                                                            </div>
                                                        </div>
                                                    </td>
                                                    <td>
                                                        <span class="branch-badge-modern">{{ $laundry->branch->name }}</span>
                                                    </td>
                                                    <td>
                                                        @if($laundry->service)
                                                            {{ $laundry->service->name }}
                                                        @elseif($laundry->promotion)
                                                            <span class="text-success">
                                                                <i class="bi bi-tag-fill me-1"></i>
                                                                {{ $laundry->promotion->name ?? 'Promotion' }}
                                                            </span>
                                                        @else
                                                            <span class="text-muted">N/A</span>
                                                        @endif
                                                    </td>
                                                    <td>{{ number_format($laundry->weight, 2) }} kg</td>
                                                    <td>
                                                        <strong
                                                            class="amount-modern">₱{{ number_format($laundry->total_amount, 2) }}</strong>
                                                    </td>
                                                    <td>
                                                        <span class="status-badge-modern status-{{ $laundry->status }}">
                                                            {{ ucfirst($laundry->status) }}
                                                        </span>
                                                    </td>
                                                    <td>
                                                        <span
                                                            class="date-text-modern">{{ $laundry->created_at->format('M d, Y') }}</span>
                                                        <span
                                                            class="time-text-modern">{{ $laundry->created_at->format('h:i A') }}</span>
                                                    </td>
                                                    <td>
                                                        <div class="action-buttons-modern">
                                                            <a href="{{ route('staff.laundries.show', $laundry->id) }}"
                                                                class="btn-icon-modern" title="View">
                                                                <i class="bi bi-eye"></i>
                                                            </a>
                                                            <a href="{{ route('staff.laundries.edit', $laundry->id) }}"
                                                                class="btn-icon-modern" title="Edit">
                                                                <i class="bi bi-pencil"></i>
                                                            </a>
                                                        </div>
                                                    </td>
                                                </tr>
                                            @empty
                                                <tr>
                                                    <td colspan="9" class="text-center py-5">
                                                        <div class="empty-state-modern">
                                                            <i class="bi bi-inbox fs-1 text-muted mb-3"></i>
                                                            <p class="text-muted">No laundries found</p>
                                                        </div>
                                                    </td>
                                                </tr>
                                            @endforelse
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Fullscreen Map Modal --}}
        <div class="modal fade" id="mapModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-fullscreen">
                <div class="modal-content">
                    <div class="modal-header border-bottom shadow-sm bg-navy text-white py-2">
                        <h5 class="modal-title fw-bold"><i class="bi bi-map me-2"></i>Logistics Map</h5>
                        <div class="d-flex gap-2 align-items-center">
                            <button class="btn btn-sm btn-outline-light" onclick="window.refreshModalMap()">
                                <i class="bi bi-arrow-clockwise"></i>
                            </button>
                            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                    </div>
                    <div class="modal-body p-0 position-relative">
                        <div id="modalBranchMap" style="height:100%;width:100%;"></div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Route Details Panel (Fixed Position) --}}
        <div id="routeDetailsPanel" class="route-details-panel" style="display:none;"></div>

    </div>
@endsection

@push('scripts')
    {{-- Chart.js - Note the correct path with .min.js --}}
    <script src="{{ asset('assets/chart.js/chart.umd.min.js') }}"></script>

    {{-- jQuery (if needed) --}}
    <script src="{{ asset('assets/jquery/jquery-3.7.0.min.js') }}"></script>

    {{-- Leaflet - Note the correct paths --}}
    <script src="{{ asset('assets/leaflet/leaflet.js') }}"></script>
    <script src="{{ asset('assets/leaflet/leaflet.markercluster.js') }}"></script>

    {{-- Pass PHP data to JavaScript --}}
    <script>
        // Set global variables for the dashboard JS
        window.REVENUE_TREND_DATA = @json($charts['revenue_trend'] ?? []);
        window.DASHBOARD_CONFIG = {
            autoRefresh: true,
            refreshInterval: 30000,
            cacheKey: 'staff_dashboard_active_tab'
        };

        // Services data for carousel
        window.SERVICES_DATA = @json($services ?? []);
        window.ADDONS_DATA = @json($addons ?? []);
    </script>

    {{-- Main dashboard JavaScript - Note the correct path --}}
    <script src="{{ asset('assets/js/staff.js') }}"></script>

    {{-- Initialize dashboard with server data --}}
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Initialize dashboard data
            if (typeof window.initializeDashboardData === 'function') {
                window.initializeDashboardData(
                    @json($allBranches ?? []),
                    @json($pickupLocations ?? []),
                    {{ $current_filters['branch_id'] ?? 'null' }}
                );
            }
        });
    </script>
@endpush
