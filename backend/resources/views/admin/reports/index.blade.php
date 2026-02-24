@extends('admin.layouts.app')

@section('page-title', 'Reports')

@section('content')
    <div class="container-fluid px-4 py-5">
        {{-- Header Section --}}
        <div class="mb-5">
            <div class="d-flex justify-content-between align-items-end">
                <div>
                    <p class="text-muted mb-0" style="font-size: 0.95rem;">Track key metrics and business performance</p>
                </div>
                <div class="d-flex gap-2">
                    <button class="btn btn-outline-secondary btn-sm" title="Refresh data">
                        <i class="bi bi-arrow-clockwise me-2"></i>Refresh
                    </button>
                    <button class="btn btn-outline-primary btn-sm" title="Export reports">
                        <i class="bi bi-download me-2"></i>Export
                    </button>
                </div>
            </div>
        </div>

        {{-- Stats Cards --}}
        <div class="row g-3 mb-5">
            <div class="col-md-6 col-lg-3">
                <div class="card border-0 shadow-sm stat-card h-100">
                    <div class="card-body p-4">
                        <div class="d-flex justify-content-between align-items-start mb-3">
                            <div>
                                <p class="text-muted mb-1 small fw-500" style="font-size: 0.85rem; letter-spacing: 0.3px;">
                                    TOTAL REVENUE</p>
                                <h3 class="mb-0 fw-bold text-dark">₱{{ number_format($stats['total_revenue'], 2) }}</h3>
                            </div>
                            <div class="stat-icon bg-primary">
                                <i class="bi bi-currency-dollar"></i>
                            </div>
                        </div>
                        <div class="d-flex align-items-center text-success small">
                            <i class="bi bi-arrow-up-right me-1"></i>
                            <span>12% from last month</span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-6 col-lg-3">
                <div class="card border-0 shadow-sm stat-card h-100">
                    <div class="card-body p-4">
                        <div class="d-flex justify-content-between align-items-start mb-3">
                            <div>
                                <p class="text-muted mb-1 small fw-500" style="font-size: 0.85rem; letter-spacing: 0.3px;">
                                    TOTAL LAUNDRY</p>
                                <h3 class="mb-0 fw-bold text-dark">{{ number_format($stats['total_laundries']) }}</h3>
                            </div>
                            <div class="stat-icon bg-success">
                                <i class="bi bi-basket"></i>
                            </div>
                        </div>
                        <div class="d-flex align-items-center text-success small">
                            <i class="bi bi-arrow-up-right me-1"></i>
                            <span>8% from last month</span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-6 col-lg-3">
                <div class="card border-0 shadow-sm stat-card h-100">
                    <div class="card-body p-4">
                        <div class="d-flex justify-content-between align-items-start mb-3">
                            <div>
                                <p class="text-muted mb-1 small fw-500" style="font-size: 0.85rem; letter-spacing: 0.3px;">
                                    TOTAL CUSTOMERS</p>
                                <h3 class="mb-0 fw-bold text-dark">{{ number_format($stats['total_customers']) }}</h3>
                            </div>
                            <div class="stat-icon bg-info">
                                <i class="bi bi-people"></i>
                            </div>
                        </div>
                        <div class="d-flex align-items-center text-success small">
                            <i class="bi bi-arrow-up-right me-1"></i>
                            <span>5% from last month</span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-6 col-lg-3">
                <div class="card border-0 shadow-sm stat-card h-100">
                    <div class="card-body p-4">
                        <div class="d-flex justify-content-between align-items-start mb-3">
                            <div>
                                <p class="text-muted mb-1 small fw-500" style="font-size: 0.85rem; letter-spacing: 0.3px;">
                                    ACTIVE BRANCHES</p>
                                <h3 class="mb-0 fw-bold text-dark">{{ number_format($stats['active_branches']) }}</h3>
                            </div>
                            <div class="stat-icon bg-warning">
                                <i class="bi bi-shop"></i>
                            </div>
                        </div>
                        <div class="d-flex align-items-center text-muted small">
                            <i class="bi bi-minus me-1"></i>
                            <span>No change</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Report Types Section --}}
        <div>
            <h5 class="mb-4 fw-bold text-dark">Available Reports</h5>
            <div class="row g-3">
                <div class="col-md-6 col-lg-3">
                    <div class="card border-0 shadow-sm report-card h-100 transition-all">
                        <div class="card-body p-4 d-flex flex-column">
                            <div class="report-icon-wrapper mb-4">
                                <i class="bi bi-graph-up text-primary"></i>
                            </div>
                            <h6 class="mb-2 fw-bold text-dark">Revenue Report</h6>
                            <p class="text-muted small mb-auto">View revenue trends, daily earnings, and financial analytics
                            </p>
                            <a href="{{ route('admin.reports.revenue') }}" class="btn btn-primary btn-sm w-100 mt-3">
                                View Report <i class="bi bi-arrow-right ms-2"></i>
                            </a>
                        </div>
                    </div>
                </div>
               {{-- Branch Ratings Card --}}
<div class="col-md-6 col-lg-3">
    <div class="card border-0 shadow-sm report-card h-100 transition-all">
        <div class="card-body p-4 d-flex flex-column">
            <div class="report-icon-wrapper mb-4">
                <i class="bi bi-building text-primary"></i>
            </div>
            <h6 class="mb-2 fw-bold text-dark">Branch Ratings Report</h6>
            <p class="text-muted small mb-auto">View customer satisfaction ratings by branch</p>
            <div class="d-flex justify-content-between align-items-center mt-2 mb-2">
                <span class="badge bg-primary">{{ number_format($stats['total_ratings'] ?? 0) }} ratings</span>
            </div>
            <a href="{{ route('admin.reports.branch-ratings') }}" class="btn btn-primary btn-sm w-100 mt-2">
                View Report <i class="bi bi-arrow-right ms-2"></i>
            </a>
        </div>
    </div>
</div>

                <div class="col-md-6 col-lg-3">
                    <div class="card border-0 shadow-sm report-card h-100 transition-all">
                        <div class="card-body p-4 d-flex flex-column">
                            <div class="report-icon-wrapper mb-4">
                                <i class="bi bi-people text-info"></i>
                            </div>
                            <h6 class="mb-2 fw-bold text-dark">Customers Report</h6>
                            <p class="text-muted small mb-auto">Customer demographics, activity, retention, and engagement
                                data</p>
                            <a href="{{ route('admin.reports.customers') }}" class="btn btn-info btn-sm w-100 mt-3">
                                View Report <i class="bi bi-arrow-right ms-2"></i>
                            </a>
                        </div>
                    </div>
                </div>

                <div class="col-md-6 col-lg-3">
                    <div class="card border-0 shadow-sm report-card h-100 transition-all">
                        <div class="card-body p-4 d-flex flex-column">
                            <div class="report-icon-wrapper mb-4">
                                <i class="bi bi-shop text-warning"></i>
                            </div>
                            <h6 class="mb-2 fw-bold text-dark">Branches Report</h6>
                            <p class="text-muted small mb-auto">Branch performance comparison and operational analytics</p>
                            <a href="{{ route('admin.reports.branches') }}" class="btn btn-warning btn-sm w-100 mt-3">
                                View Report <i class="bi bi-arrow-right ms-2"></i>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <style>
        /* Stat Cards */
        .stat-card {
            position: relative;
            overflow: hidden;
            border-radius: 12px;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .stat-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, var(--bs-primary), transparent);
        }

        .stat-card:hover {
            box-shadow: 0 12px 24px rgba(0, 0, 0, 0.12) !important;
            transform: translateY(-2px);
        }

        /* Stat Icon */
        .stat-icon {
            width: 48px;
            height: 48px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            color: white;
            flex-shrink: 0;
        }

        .stat-icon.bg-primary {
            background: linear-gradient(135deg, #0d6efd, #0a58ca);
        }

        .stat-icon.bg-success {
            background: linear-gradient(135deg, #198754, #146c43);
        }

        .stat-icon.bg-info {
            background: linear-gradient(135deg, #0dcaf0, #0aa2c0);
        }

        .stat-icon.bg-warning {
            background: linear-gradient(135deg, #ffc107, #e0a800);
            color: #333;
        }

        /* Report Cards */
        .report-card {
            border-radius: 12px;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .report-card:hover {
            box-shadow: 0 16px 32px rgba(0, 0, 0, 0.15) !important;
            transform: translateY(-4px);
        }

        .report-icon-wrapper {
            width: 56px;
            height: 56px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2rem;
            background-color: rgba(13, 110, 253, 0.08);
        }

        .report-card:hover .report-icon-wrapper {
            background-color: rgba(13, 110, 253, 0.15);
            transform: scale(1.08);
        }

        /* Transitions */
        .transition-all {
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        /* Button Enhancements */
        .btn-primary:hover,
        .btn-success:hover,
        .btn-info:hover,
        .btn-warning:hover {
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
        }
    </style>
@endsection