@extends('admin.layouts.app')

@section('title', 'Analytics Dashboard')
@section('page-title', 'Analytics Dashboard')

@section('content')
<div class="container-fluid px-4 py-4">
    {{-- Header with Date Range --}}
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="fw-bold mb-1">Analytics Dashboard</h4>
            <p class="text-muted mb-0 small">Comprehensive business insights and performance metrics</p>
        </div>
        <div>
            <button class="btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#dateRangeModal">
                <i class="bi bi-calendar-range me-2"></i>{{ \Carbon\Carbon::parse($startDate)->format('M d') }} - {{ \Carbon\Carbon::parse($endDate)->format('M d, Y') }}
            </button>
        </div>
    </div>

    {{-- Key Metrics Row --}}
    <div class="row g-3 mb-4">
        <div class="col-xl-3 col-md-6">
            <div class="card border-0 shadow-sm h-100" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
                <div class="card-body p-4 text-white">
                    <div class="d-flex justify-content-between align-items-start mb-3">
                        <div class="bg-white bg-opacity-20 p-3 rounded-3">
                            <i class="bi bi-cash-stack fs-3"></i>
                        </div>
                        @if($revenueAnalytics['growth_percentage'] != 0)
                        <span class="badge {{ $revenueAnalytics['growth_percentage'] > 0 ? 'bg-success' : 'bg-danger' }}">
                            <i class="bi bi-arrow-{{ $revenueAnalytics['growth_percentage'] > 0 ? 'up' : 'down' }}"></i>
                            {{ abs($revenueAnalytics['growth_percentage']) }}%
                        </span>
                        @endif
                    </div>
                    <h6 class="mb-2 opacity-75">Total Revenue</h6>
                    <h2 class="fw-bold mb-0">₱{{ number_format($revenueAnalytics['total'], 2) }}</h2>
                    <small class="opacity-75">vs previous period</small>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body p-4">
                    <div class="d-flex justify-content-between align-items-start mb-3">
                        <div class="bg-primary bg-opacity-10 p-3 rounded-3">
                            <i class="bi bi-box-seam fs-3 text-primary"></i>
                        </div>
                    </div>
                    <h6 class="text-muted mb-2">Total Laundries</h6>
                    <h2 class="fw-bold mb-2">{{ number_format($laundryAnalytics['total']) }}</h2>
                    <div class="progress" style="height: 6px; border-radius: 10px;">
                        <div class="progress-bar bg-primary" style="width: {{ $laundryAnalytics['completion_rate'] ?? 0 }}%; border-radius: 10px;"></div>
                    </div>
                    <small class="text-muted">{{ $laundryAnalytics['completion_rate'] ?? 0 }}% completion rate</small>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body p-4">
                    <div class="d-flex justify-content-between align-items-start mb-3">
                        <div class="bg-success bg-opacity-10 p-3 rounded-3">
                            <i class="bi bi-people fs-3 text-success"></i>
                        </div>
                    </div>
                    <h6 class="text-muted mb-2">Total Customers</h6>
                    <h2 class="fw-bold mb-2">{{ number_format($customerAnalytics['total']) }}</h2>
                    <small class="text-success fw-semibold">+{{ number_format($customerAnalytics['new']) }} new</small>
                    <small class="text-muted"> this period</small>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body p-4">
                    <div class="d-flex justify-content-between align-items-start mb-3">
                        <div class="bg-warning bg-opacity-10 p-3 rounded-3">
                            <i class="bi bi-graph-up-arrow fs-3 text-warning"></i>
                        </div>
                    </div>
                    <h6 class="text-muted mb-2">Avg Laundry Value</h6>
                    <h2 class="fw-bold mb-2">₱{{ number_format($revenueAnalytics['average_laundry_value'] ?? 0, 2) }}</h2>
                    <small class="text-muted">Per laundry value</small>
                </div>
            </div>
        </div>
    </div>

    {{-- Tabs Navigation (matching dashboard style) --}}
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body p-0">
            <ul class="nav nav-pills nav-fill border-bottom" id="analyticsTabs" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link active" id="overview-tab" data-bs-toggle="pill" data-bs-target="#overview" type="button">
                        <i class="bi bi-graph-up me-2"></i>Overview
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="branches-tab" data-bs-toggle="pill" data-bs-target="#branches" type="button">
                        <i class="bi bi-building me-2"></i>Branches
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="services-tab" data-bs-toggle="pill" data-bs-target="#services" type="button">
                        <i class="bi bi-star me-2"></i>Services
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="customers-tab" data-bs-toggle="pill" data-bs-target="#customers" type="button">
                        <i class="bi bi-people me-2"></i>Customers
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="promotions-tab" data-bs-toggle="pill" data-bs-target="#promotions" type="button">
                        <i class="bi bi-megaphone me-2"></i>Promotions
                    </button>
                </li>
            </ul>

            <div class="tab-content p-4" id="analyticsTabsContent">
                {{-- OVERVIEW TAB --}}
                <div class="tab-pane fade show active" id="overview" role="tabpanel">
                    <div class="row g-4">
                        {{-- Revenue Trend --}}
                        <div class="col-lg-8">
                            <div class="card border-0 shadow-sm">
                                <div class="card-header bg-white border-bottom">
                                    <h6 class="mb-0 fw-bold">
                                        <i class="bi bi-graph-up me-2 text-primary"></i>Revenue Trend
                                    </h6>
                                </div>
                                <div class="card-body">
                                    <canvas id="revenueChart" height="80"></canvas>
                                </div>
                            </div>
                        </div>

                        {{-- Laundry Status --}}
                        <div class="col-lg-4">
                            <div class="card border-0 shadow-sm">
                                <div class="card-header bg-white border-bottom">
                                    <h6 class="mb-0 fw-bold">
                                        <i class="bi bi-pie-chart me-2 text-primary"></i>Laundry Status
                                    </h6>
                                </div>
                                <div class="card-body">
                                    <canvas id="laundryStatusChart" height="200"></canvas>
                                    <div class="mt-3 pt-3 border-top">
                                        <div class="row g-2 text-center">
                                            <div class="col-6">
                                                <div class="p-2 bg-light rounded">
                                                    <small class="text-muted d-block">Completed</small>
                                                    <strong class="text-success">{{ $laundryAnalytics['completed'] ?? 0 }}</strong>
                                                </div>
                                            </div>
                                            <div class="col-6">
                                                <div class="p-2 bg-light rounded">
                                                    <small class="text-muted d-block">Pending</small>
                                                    <strong class="text-warning">{{ ($laundryAnalytics['total'] ?? 0) - ($laundryAnalytics['completed'] ?? 0) }}</strong>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        {{-- Performance Metrics --}}
                        <div class="col-12">
                            <div class="row g-3">
                                <div class="col-md-3">
                                    <div class="card border-0 shadow-sm text-center">
                                        <div class="card-body p-4">
                                            <div class="bg-info bg-opacity-10 p-3 rounded-3 d-inline-block mb-3">
                                                <i class="bi bi-clock-history fs-1 text-info"></i>
                                            </div>
                                            <h6 class="text-muted mb-2">Avg Processing Time</h6>
                                            <h2 class="fw-bold mb-0">{{ $laundryAnalytics['avg_processing_time_hours'] ?? 0 }}</h2>
                                            <small class="text-muted">hours</small>
                                        </div>
                                    </div>
                                </div>

                                <div class="col-md-3">
                                    <div class="card border-0 shadow-sm text-center">
                                        <div class="card-body p-4">
                                            <div class="bg-success bg-opacity-10 p-3 rounded-3 d-inline-block mb-3">
                                                <i class="bi bi-check-circle fs-1 text-success"></i>
                                            </div>
                                            <h6 class="text-muted mb-2">Completion Rate</h6>
                                            <h2 class="fw-bold mb-0">{{ $laundryAnalytics['completion_rate'] ?? 0 }}%</h2>
                                            <div class="progress mt-2" style="height: 6px;">
                                                <div class="progress-bar bg-success" style="width: {{ $laundryAnalytics['completion_rate'] ?? 0 }}%"></div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="col-md-3">
                                    <div class="card border-0 shadow-sm text-center">
                                        <div class="card-body p-4">
                                            <div class="bg-primary bg-opacity-10 p-3 rounded-3 d-inline-block mb-3">
                                                <i class="bi bi-receipt fs-1 text-primary"></i>
                                            </div>
                                            <h6 class="text-muted mb-2">Avg Laundry/Customer</h6>
                                            <h2 class="fw-bold mb-0">{{ $customerAnalytics['avg_laundries_per_customer'] ?? 0 }}</h2>
                                            <small class="text-muted">laundries</small>
                                        </div>
                                    </div>
                                </div>

                                <div class="col-md-3">
                                    <div class="card border-0 shadow-sm text-center">
                                        <div class="card-body p-4">
                                            <div class="bg-warning bg-opacity-10 p-3 rounded-3 d-inline-block mb-3">
                                                <i class="bi bi-arrow-up-right-circle fs-1 text-warning"></i>
                                            </div>
                                            <h6 class="text-muted mb-2">Revenue Growth</h6>
                                            <h2 class="fw-bold mb-0 {{ ($revenueAnalytics['growth_percentage'] ?? 0) > 0 ? 'text-success' : 'text-danger' }}">
                                                {{ ($revenueAnalytics['growth_percentage'] ?? 0) > 0 ? '+' : '' }}{{ $revenueAnalytics['growth_percentage'] ?? 0 }}%
                                            </h2>
                                            <small class="text-muted">vs previous</small>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- BRANCHES TAB --}}
                <div class="tab-pane fade" id="branches" role="tabpanel">
                    <div class="card border-0 shadow-sm">
                        <div class="card-header bg-white border-bottom">
                            <h6 class="mb-0 fw-bold">
                                <i class="bi bi-building me-2 text-primary"></i>Branch Performance
                            </h6>
                        </div>
                        <div class="card-body">
                            <canvas id="branchRevenueChart" height="80"></canvas>
                        </div>
                        <div class="card-footer bg-light">
                            <div class="row g-3">
                                @if(isset($branchPerformance['branches']) && count($branchPerformance['branches']) > 0)
                                    @foreach($branchPerformance['branches'] as $branch)
                                    <div class="col-md-4">
                                        <div class="text-center">
                                            <small class="text-muted d-block">{{ $branch['name'] ?? 'Unknown' }}</small>
                                            <strong class="d-block">₱{{ number_format($branch['revenue'] ?? 0, 0) }}</strong>
                                            <small class="text-muted">{{ $branch['laundries'] ?? 0 }} laundries</small>
                                        </div>
                                    </div>
                                    @endforeach
                                @else
                                    <div class="col-12 text-center py-3">
                                        <p class="text-muted mb-0">No branch data available</p>
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>

                {{-- SERVICES TAB --}}
                <div class="tab-pane fade" id="services" role="tabpanel">
                    <div class="card border-0 shadow-sm">
                        <div class="card-header bg-white border-bottom">
                            <h6 class="mb-0 fw-bold">
                                <i class="bi bi-star me-2 text-primary"></i>Service Popularity
                            </h6>
                        </div>
                        <div class="card-body">
                            @if(isset($servicePopularity['services']) && count($servicePopularity['services']) > 0)
                                @foreach($servicePopularity['services'] as $service)
                                <div class="mb-4 {{ $loop->last ? '' : 'pb-3 border-bottom' }}">
                                    <div class="d-flex justify-content-between align-items-center mb-2">
                                        <div>
                                            <strong>{{ $service['name'] ?? 'Unknown Service' }}</strong>
                                        </div>
                                        <span class="badge bg-light text-dark border">{{ $service['laundries'] ?? 0 }} laundries</span>
                                    </div>
                                    <div class="progress mb-2" style="height: 10px;">
                                        @php
                                            $maxLaundries = $servicePopularity['services'][0]['laundries'] ?? 1;
                                            $percentage = $maxLaundries > 0
                                                ? (($service['laundries'] ?? 0) / $maxLaundries) * 100
                                                : 0;
                                        @endphp
                                        <div class="progress-bar bg-primary" style="width: {{ $percentage }}%"></div>
                                    </div>
                                    <small class="text-muted">Revenue: ₱{{ number_format($service['revenue'] ?? 0, 2) }}</small>
                                </div>
                                @endforeach
                            @else
                                <div class="text-center py-5">
                                    <i class="bi bi-star text-muted" style="font-size: 3rem; opacity: 0.3;"></i>
                                    <p class="text-muted mb-0 mt-2">No service data available</p>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>

                {{-- CUSTOMERS TAB --}}
                <div class="tab-pane fade" id="customers" role="tabpanel">
                    <div class="row g-4">
                        <div class="col-lg-8">
                            <div class="card border-0 shadow-sm">
                                <div class="card-header bg-white border-bottom">
                                    <h6 class="mb-0 fw-bold">
                                        <i class="bi bi-people me-2 text-primary"></i>Customer Growth
                                    </h6>
                                </div>
                                <div class="card-body">
                                    <canvas id="customerGrowthChart" height="60"></canvas>
                                </div>
                            </div>
                        </div>

                        <div class="col-lg-4">
                            <div class="card border-0 shadow-sm">
                                <div class="card-header bg-white border-bottom">
                                    <h6 class="mb-0 fw-bold">
                                        <i class="bi bi-trophy me-2 text-warning"></i>Top Customers
                                    </h6>
                                </div>
                                <div class="card-body p-0">
                                    <div class="list-group list-group-flush">
                                        @if(isset($customerAnalytics['top_customers']) && count($customerAnalytics['top_customers']) > 0)
                                            @foreach($customerAnalytics['top_customers'] as $index => $customer)
                                            <div class="list-group-item border-0 py-3">
                                                <div class="d-flex align-items-center">
                                                    <div class="me-3">
                                                        <div class="bg-primary bg-opacity-10 rounded-circle d-flex align-items-center justify-content-center" style="width: 40px; height: 40px;">
                                                            <strong class="text-primary">{{ $index + 1 }}</strong>
                                                        </div>
                                                    </div>
                                                    <div class="flex-grow-1">
                                                        <strong class="d-block">{{ $customer->name ?? 'Unknown Customer' }}</strong>
                                                        <small class="text-muted">{{ Str::limit($customer->email ?? 'No email', 20) }}</small>
                                                    </div>
                                                    <div class="text-end">
                                                        <strong class="d-block text-primary">₱{{ number_format($customer->laundries_sum_total_amount ?? 0, 0) }}</strong>
                                                        <small class="text-muted">{{ $customer->laundries_count ?? 0 }} laundries</small>
                                                    </div>
                                                </div>
                                            </div>
                                            @endforeach
                                        @else
                                        <div class="text-center py-5">
                                            <i class="bi bi-people text-muted" style="font-size: 3rem; opacity: 0.3;"></i>
                                            <p class="text-muted mb-0 mt-2">No customer data available</p>
                                        </div>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- PROMOTIONS TAB --}}
                <div class="tab-pane fade" id="promotions" role="tabpanel">
                    <div class="card border-0 shadow-sm">
                        <div class="card-header bg-white border-bottom d-flex justify-content-between align-items-center">
                            <h6 class="mb-0 fw-bold">
                                <i class="bi bi-megaphone me-2 text-primary"></i>Promotion Effectiveness
                            </h6>
                            <a href="{{ route('admin.promotions.index') }}" class="btn btn-sm btn-primary">
                                <i class="bi bi-plus-circle me-1"></i>Create Promotion
                            </a>
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table table-hover align-middle mb-0">
                                    <thead class="table-light">
                                        <tr>
                                            <th class="ps-4">Promotion Name</th>
                                            <th class="text-center">Type</th>
                                            <th class="text-center">Status</th>
                                            <th class="text-end">Usage</th>
                                            <th class="text-end">Revenue</th>
                                            <th class="text-end">Discount</th>
                                            <th class="text-end pe-4">ROI</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @if(isset($promotionEffectiveness['promotions']) && count($promotionEffectiveness['promotions']) > 0)
                                            @foreach($promotionEffectiveness['promotions'] as $promo)
                                            <tr>
                                                <td class="ps-4">
                                                    <strong>{{ $promo['name'] ?? 'Unknown Promotion' }}</strong>
                                                    @if(($promo['type'] ?? '') === 'poster_promo')
                                                        <br><small class="text-muted">Poster Promotion</small>
                                                    @endif
                                                </td>
                                                <td class="text-center">
                                                    <span class="badge bg-light text-dark">
                                                        {{ ucfirst(str_replace('_', ' ', $promo['type'] ?? 'unknown')) }}
                                                    </span>
                                                </td>
                                                <td class="text-center">
                                                    @if($promo['is_active'] ?? false)
                                                        <span class="badge bg-success">Active</span>
                                                    @else
                                                        <span class="badge bg-secondary">Inactive</span>
                                                    @endif
                                                </td>
                                                <td class="text-end">
                                                    <strong>{{ number_format($promo['usage_count'] ?? 0) }}</strong>
                                                </td>
                                                <td class="text-end">
                                                    <strong class="text-primary">₱{{ number_format($promo['revenue'] ?? 0, 2) }}</strong>
                                                </td>
                                                <td class="text-end text-danger">
                                                    -₱{{ number_format($promo['total_discount'] ?? 0, 2) }}
                                                </td>
                                                <td class="text-end pe-4">
                                                    @php
                                                        $totalDiscount = $promo['total_discount'] ?? 0;
                                                        $revenue = $promo['revenue'] ?? 0;
                                                        $roi = $totalDiscount > 0
                                                            ? ($revenue / $totalDiscount)
                                                            : ($revenue > 0 ? 100 : 0);
                                                    @endphp
                                                    <span class="badge {{ $roi > 3 ? 'bg-success' : ($roi > 1.5 ? 'bg-warning' : 'bg-danger') }}">
                                                        {{ number_format($roi, 2) }}x
                                                    </span>
                                                </td>
                                            </tr>
                                            @endforeach
                                        @else
                                        <tr>
                                            <td colspan="7" class="text-center py-5">
                                                <i class="bi bi-megaphone text-muted" style="font-size: 3rem; opacity: 0.3;"></i>
                                                <p class="text-muted mb-0 mt-2">No promotion data available</p>
                                                <a href="{{ route('admin.promotions.create') }}" class="btn btn-sm btn-primary mt-2">
                                                    Create Your First Promotion
                                                </a>
                                            </td>
                                        </tr>
                                        @endif
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Date Range Modal --}}
<div class="modal fade" id="dateRangeModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title fw-bold">Select Date Range</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="GET" action="{{ route('admin.analytics') }}">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Start Date</label>
                        <input type="date" class="form-control" name="start_date" value="{{ $startDate }}">
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">End Date</label>
                        <input type="date" class="form-control" name="end_date" value="{{ $endDate }}">
                    </div>
                    <div class="d-flex gap-2 flex-wrap">
                        <button type="button" class="btn btn-outline-secondary btn-sm" onclick="setQuickRange('today')">Today</button>
                        <button type="button" class="btn btn-outline-secondary btn-sm" onclick="setQuickRange('week')">This Week</button>
                        <button type="button" class="btn btn-outline-secondary btn-sm" onclick="setQuickRange('month')">This Month</button>
                        <button type="button" class="btn btn-outline-secondary btn-sm" onclick="setQuickRange('year')">This Year</button>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Apply</button>
                </div>
            </form>
        </div>
    </div>
</div>

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
// Chart.js configuration
Chart.defaults.font.family = "'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif";
Chart.defaults.color = '#6B7280';

const colors = {
    primary: '#0d6efd',
    success: '#198754',
    danger: '#dc3545',
    warning: '#ffc107',
    info: '#0dcaf0'
};

function initializeCharts() {
    if (typeof Chart === 'undefined') {
        console.warn('Chart.js is not loaded. Charts will not be displayed.');
        return;
    }

    // ═══ FIX 1: Revenue Chart — was $stats['revenueLabels'], now $revenueAnalytics['labels'] ═══
    const revenueCtx = document.getElementById('revenueChart');
    if (revenueCtx) {
        new Chart(revenueCtx, {
            type: 'line',
            data: {
                labels: @json($revenueAnalytics['labels'] ?? []),
                datasets: [{
                    label: 'Daily Revenue',
                    data: @json($revenueAnalytics['data'] ?? []),
                    borderColor: '#2563eb',
                    backgroundColor: 'rgba(37, 99, 235, 0.1)',
                    borderWidth: 3,
                    fill: true,
                    tension: 0.4,
                    pointBackgroundColor: '#2563eb',
                    pointBorderColor: '#ffffff',
                    pointBorderWidth: 2,
                    pointRadius: 6,
                    pointHoverRadius: 8
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        mode: 'index',
                        intersect: false,
                        backgroundColor: 'rgba(0, 0, 0, 0.8)',
                        titleColor: '#ffffff',
                        bodyColor: '#ffffff',
                        callbacks: {
                            label: function(context) {
                                return '₱' + context.parsed.y.toLocaleString();
                            }
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        grid: {
                            color: 'rgba(0, 0, 0, 0.05)',
                            drawBorder: false
                        },
                        ticks: {
                            callback: function(value) { return '₱' + value.toLocaleString(); },
                            font: { size: 11, family: 'system-ui' }
                        }
                    },
                    x: {
                        grid: { display: false, drawBorder: false },
                        ticks: { font: { size: 11, family: 'system-ui' } }
                    }
                },
                interaction: { intersect: false, mode: 'index' }
            }
        });
    }

    // Laundry Status Chart
    const laundryStatusCtx = document.getElementById('laundryStatusChart');
    if (laundryStatusCtx) {
        new Chart(laundryStatusCtx, {
            type: 'doughnut',
            data: {
                labels: @json($laundryAnalytics['status_labels'] ?? []),
                datasets: [{
                    data: @json($laundryAnalytics['status_data'] ?? []),
                    backgroundColor: [colors.primary, colors.success, colors.warning, colors.info, colors.danger],
                    borderWidth: 0,
                    cutout: '70%'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: { padding: 15, usePointStyle: true, pointStyle: 'circle' }
                    }
                }
            }
        });
    }

    // Branch Revenue Chart
    const branchRevenueCtx = document.getElementById('branchRevenueChart');
    if (branchRevenueCtx) {
        new Chart(branchRevenueCtx, {
            type: 'bar',
            data: {
                labels: @json($branchPerformance['labels'] ?? []),
                datasets: [{
                    label: 'Revenue',
                    data: @json($branchPerformance['revenue_data'] ?? []),
                    backgroundColor: colors.primary,
                    borderRadius: 8
                }]
            },
            options: {
                responsive: true,
                plugins: { legend: { display: false } },
                scales: {
                    y: {
                        beginAtZero: true,
                        grid: { color: '#F3F4F6' },
                        ticks: { callback: (value) => '₱' + (value / 1000) + 'k' }
                    },
                    x: { grid: { display: false } }
                }
            }
        });
    }

    // Customer Growth Chart
    const customerGrowthCtx = document.getElementById('customerGrowthChart');
    if (customerGrowthCtx) {
        new Chart(customerGrowthCtx, {
            type: 'line',
            data: {
                labels: @json($customerAnalytics['growth_labels'] ?? []),
                datasets: [{
                    label: 'New Customers',
                    data: @json($customerAnalytics['growth_data'] ?? []),
                    borderColor: colors.success,
                    backgroundColor: 'rgba(25, 135, 84, 0.1)',
                    tension: 0.4,
                    fill: true,
                    borderWidth: 3
                }]
            },
            options: {
                responsive: true,
                plugins: { legend: { display: false } },
                scales: {
                    y: { beginAtZero: true, grid: { color: '#F3F4F6' } },
                    x: { grid: { display: false } }
                }
            }
        });
    }
}

// Quick date range
function setQuickRange(range) {
    const now = new Date();
    const startInput = document.querySelector('input[name="start_date"]');
    const endInput = document.querySelector('input[name="end_date"]');
    let startDate, endDate;

    switch(range) {
        case 'today':
            startDate = new Date();
            endDate = new Date();
            break;
        case 'week':
            startDate = new Date(now);
            startDate.setDate(now.getDate() - now.getDay());
            endDate = new Date();
            break;
        case 'month':
            startDate = new Date(now.getFullYear(), now.getMonth(), 1);
            endDate = new Date();
            break;
        case 'year':
            startDate = new Date(now.getFullYear(), 0, 1);
            endDate = new Date();
            break;
    }

    if (startInput && endInput) {
        startInput.value = startDate.toISOString().split('T')[0];
        endInput.value = endDate.toISOString().split('T')[0];
    }
}

document.addEventListener('DOMContentLoaded', initializeCharts);
</script>
@endpush

@push('styles')
<style>
    #analyticsTabs .nav-link {
        color: #6c757d;
        font-weight: 600;
        padding: 1rem 1.5rem;
        border: none;
        border-radius: 0;
        transition: all 0.3s ease;
    }
    #analyticsTabs .nav-link:hover {
        color: #0d6efd;
        background-color: #f8f9fa;
    }
    #analyticsTabs .nav-link.active {
        color: #0d6efd;
        background-color: transparent;
        border-bottom: 3px solid #0d6efd;
    }
    .card {
        border-radius: 12px;
        transition: transform 0.2s, box-shadow 0.2s;
    }
    .card:hover {
        transform: translateY(-2px);
        box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.1) !important;
    }
    .progress { border-radius: 10px; background-color: #e9ecef; }
    .progress-bar { border-radius: 10px; }
    .table-hover tbody tr:hover { background-color: rgba(13, 110, 253, 0.05); }
    .list-group-item { transition: background-color 0.2s; }
    .list-group-item:hover { background-color: rgba(13, 110, 253, 0.05); }
    .badge { padding: 0.35em 0.65em; font-weight: 600; }
</style>
@endpush
@endsection
