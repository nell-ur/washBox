@extends('admin.layouts.app')

@section('title', 'Laundries Report')

@section('content')
<div class="container-fluid px-4 py-5">
    {{-- Header Section --}}
    <div class="mb-5">
        <div class="d-flex justify-content-between align-items-end">
            <div>
                <h1 class="mb-2 fw-bold text-dark" style="font-size: 2rem; letter-spacing: -0.5px;">Laundries Report</h1>
                <p class="text-muted mb-0" style="font-size: 0.95rem;">Track orders, processing status, and order details</p>
            </div>
            <div class="d-flex gap-2">
                <a href="{{ route('admin.reports.index') }}" class="btn btn-outline-secondary btn-sm">
                    <i class="bi bi-arrow-left me-2"></i>Back
                </a>
                <form method="POST" action="{{ route('admin.reports.export') }}" class="d-inline">
                    @csrf
                    <input type="hidden" name="type" value="laundries">
                    <input type="hidden" name="start_date" value="{{ $startDate->format('Y-m-d') }}">
                    <input type="hidden" name="end_date" value="{{ $endDate->format('Y-m-d') }}">
                    <button type="submit" class="btn btn-outline-primary btn-sm">
                        <i class="bi bi-download me-2"></i>Export
                    </button>
                </form>
            </div>
        </div>
    </div>

    {{-- Key Metrics Cards --}}
    <div class="row g-3 mb-5">
        <div class="col-md-6 col-lg-3">
            <div class="card border-0 shadow-sm stat-card h-100">
                <div class="card-body p-4">
                    <div class="d-flex justify-content-between align-items-start mb-3">
                        <div>
                            <p class="text-muted mb-1 small fw-500" style="font-size: 0.85rem; letter-spacing: 0.3px;">TOTAL LAUNDRIES</p>
                            <h3 class="mb-0 fw-bold text-dark">{{ number_format($summary['total_laundries']) }}</h3>
                        </div>
                        <div class="stat-icon bg-primary">
                            <i class="bi bi-basket"></i>
                        </div>
                    </div>
                    <div class="d-flex align-items-center text-muted small">
                        <i class="bi bi-dash me-1"></i>
                        <span>Total orders</span>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-6 col-lg-3">
            <div class="card border-0 shadow-sm stat-card h-100">
                <div class="card-body p-4">
                    <div class="d-flex justify-content-between align-items-start mb-3">
                        <div>
                            <p class="text-muted mb-1 small fw-500" style="font-size: 0.85rem; letter-spacing: 0.3px;">COMPLETED</p>
                            <h3 class="mb-0 fw-bold text-dark">{{ number_format($summary['completed_laundries']) }}</h3>
                        </div>
                        <div class="stat-icon bg-success">
                            <i class="bi bi-check-circle"></i>
                        </div>
                    </div>
                    <div class="d-flex align-items-center text-success small">
                        <i class="bi bi-arrow-up-right me-1"></i>
                        <span>{{ round(($summary['completed_laundries'] / max($summary['total_laundries'], 1)) * 100) }}% complete</span>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-6 col-lg-3">
            <div class="card border-0 shadow-sm stat-card h-100">
                <div class="card-body p-4">
                    <div class="d-flex justify-content-between align-items-start mb-3">
                        <div>
                            <p class="text-muted mb-1 small fw-500" style="font-size: 0.85rem; letter-spacing: 0.3px;">PENDING</p>
                            <h3 class="mb-0 fw-bold text-dark">{{ number_format($summary['pending_laundries']) }}</h3>
                        </div>
                        <div class="stat-icon bg-warning">
                            <i class="bi bi-hourglass-split"></i>
                        </div>
                    </div>
                    <div class="d-flex align-items-center text-warning small">
                        <i class="bi bi-dash me-1"></i>
                        <span>In progress</span>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-6 col-lg-3">
            <div class="card border-0 shadow-sm stat-card h-100">
                <div class="card-body p-4">
                    <div class="d-flex justify-content-between align-items-start mb-3">
                        <div>
                            <p class="text-muted mb-1 small fw-500" style="font-size: 0.85rem; letter-spacing: 0.3px;">TOTAL REVENUE</p>
                            <h3 class="mb-0 fw-bold text-dark">₱{{ number_format($summary['total_revenue'], 2) }}</h3>
                        </div>
                        <div class="stat-icon bg-info">
                            <i class="bi bi-currency-peso"></i>
                        </div>
                    </div>
                    <div class="d-flex align-items-center text-muted small">
                        <i class="bi bi-dash me-1"></i>
                        <span>Period total</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Date Filter Card --}}
    <div class="card border-0 shadow-sm mb-5">
        <div class="card-body p-4">
            <form method="GET" action="{{ route('admin.reports.laundries') }}" class="row g-3 align-items-end">
                <div class="col-md-4">
                    <label class="form-label fw-600 text-dark mb-2">Start Date</label>
                    <input type="date" name="start_date" class="form-control form-control-sm" style="border-radius: 8px;" value="{{ $startDate->format('Y-m-d') }}">
                </div>
                <div class="col-md-4">
                    <label class="form-label fw-600 text-dark mb-2">End Date</label>
                    <input type="date" name="end_date" class="form-control form-control-sm" style="border-radius: 8px;" value="{{ $endDate->format('Y-m-d') }}">
                </div>
                <div class="col-md-4">
                    <button type="submit" class="btn btn-primary btn-sm w-100">
                        <i class="bi bi-funnel me-2"></i>Apply Filter
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- Laundries Table --}}
    <div class="card border-0 shadow-sm">
        <div class="card-header bg-light border-0 p-4 d-flex justify-content-between align-items-center">
            <h6 class="mb-0 fw-bold text-dark">
                <i class="bi bi-table text-dark me-2"></i>Order Details
            </h6>
            <span class="badge bg-light text-dark">{{ $laundries->total() }} orders</span>
        </div>
        <div class="card-body p-0">
            @if($laundries->count() > 0)
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th class="fw-600">Tracking #</th>
                                <th class="fw-600">Customer</th>
                                <th class="fw-600">Branch</th>
                                <th class="text-center fw-600">Status</th>
                                <th class="text-center fw-600">Payment</th>
                                <th class="text-end fw-600">Amount</th>
                                <th class="fw-600">Date</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($laundries as $laundry)
                                @php
                                    $statusClass = match($laundry->status) {
                                        'completed' => 'bg-success',
                                        'processing' => 'bg-info',
                                        'pending' => 'bg-warning',
                                        'cancelled' => 'bg-danger',
                                        default => 'bg-secondary'
                                    };
                                    $paymentClass = match($laundry->payment_status) {
                                        'paid' => 'bg-success',
                                        'pending' => 'bg-warning',
                                        'failed' => 'bg-danger',
                                        default => 'bg-secondary'
                                    };
                                @endphp
                                <tr class="align-middle">
                                    <td>
                                        <span class="fw-500 text-primary">{{ $laundry->tracking_number }}</span>
                                    </td>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <div class="avatar-sm rounded-circle bg-light me-2 d-flex align-items-center justify-content-center" style="width: 32px; height: 32px;">
                                                <span class="small fw-bold text-dark">{{ substr($laundry->customer->name, 0, 1) }}</span>
                                            </div>
                                            <span>{{ $laundry->customer->name }}</span>
                                        </div>
                                    </td>
                                    <td>
                                        <small class="text-muted">{{ $laundry->branch->name }}</small>
                                    </td>
                                    <td class="text-center">
                                        <span class="badge {{ $statusClass }} text-white" style="font-size: 0.8rem;">
                                            {{ ucfirst($laundry->status) }}
                                        </span>
                                    </td>
                                    <td class="text-center">
                                        <span class="badge {{ $paymentClass }} text-white" style="font-size: 0.8rem;">
                                            {{ ucfirst($laundry->payment_status) }}
                                        </span>
                                    </td>
                                    <td class="text-end">
                                        <span class="fw-600">₱{{ number_format($laundry->total_amount, 2) }}</span>
                                    </td>
                                    <td>
                                        <small class="text-muted">{{ $laundry->created_at->format('M d, Y') }}</small>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="p-4 border-top">
                    {{ $laundries->links() }}
                </div>
            @else
                <div class="text-center py-5">
                    <i class="bi bi-inbox text-muted" style="font-size: 3rem; opacity: 0.6;"></i>
                    <p class="text-muted mt-3 mb-0">No laundries found for the selected period</p>
                    <small class="text-muted">Try adjusting your date range filters</small>
                </div>
            @endif
        </div>
    </div>
</div>

<style>
.stat-card {
    border-radius: 12px;
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    position: relative;
    overflow: hidden;
}

.stat-card::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 4px;
    background: linear-gradient(90deg, var(--bs-primary), transparent);
    border-radius: 12px 12px 0 0;
}

.stat-card:hover {
    box-shadow: 0 12px 24px rgba(0, 0, 0, 0.12) !important;
    transform: translateY(-2px);
}

.stat-icon {
    width: 48px;
    height: 48px;
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.5rem;
    color: white;
}

.stat-icon.bg-primary { background: linear-gradient(135deg, #0d6efd, #0a58ca); }
.stat-icon.bg-success { background: linear-gradient(135deg, #198754, #146c43); }
.stat-icon.bg-warning { background: linear-gradient(135deg, #ffc107, #e0a800); }
.stat-icon.bg-info { background: linear-gradient(135deg, #0dcaf0, #0aa2c0); }

.table-light { background-color: #f8f9fa; }
.fw-600 { font-weight: 600; }

table tbody tr {
    border-bottom: 1px solid #e9ecef;
    transition: all 0.2s ease;
}

table tbody tr:hover {
    background-color: #f8f9fa;
}
</style>
@endsection