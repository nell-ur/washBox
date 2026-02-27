@extends('admin.layouts.app')

@section('page-title', 'Customers Report')

@section('content')
<div class="container-fluid px-4 py-5">
    {{-- Header Section --}}
    <div class="mb-5">
        <div class="d-flex justify-content-between align-items-end">
            <div>
                <p class="text-muted mb-0" style="font-size: 0.95rem;">Customer demographics, activity, retention, ratings, and engagement data</p>
            </div>
            <div class="d-flex gap-2">
                <a href="{{ route('admin.reports.index') }}" class="btn btn-outline-secondary btn-sm">
                    <i class="bi bi-arrow-left me-2"></i>Back
                </a>
                <form method="POST" action="{{ route('admin.reports.export') }}" class="d-inline">
                    @csrf
                    <input type="hidden" name="type" value="customers">
                    <input type="hidden" name="filter" value="{{ request('filter', 'this_month') }}">
                    <input type="hidden" name="date_from" value="{{ request('date_from') }}">
                    <input type="hidden" name="date_to" value="{{ request('date_to') }}">
                    <button type="submit" class="btn btn-outline-primary btn-sm">
                        <i class="bi bi-download me-2"></i>Export
                    </button>
                </form>
            </div>
        </div>
    </div>

    {{-- Filter Section --}}
    <div class="card border-0 shadow-sm mb-5">
        <div class="card-body p-4">
            <form method="GET" action="{{ route('admin.reports.customers') }}" class="row g-3 align-items-end">
                <div class="col-md-3">
                    <label class="form-label fw-600 text-dark mb-2">Date Range</label>
                    <select name="filter" class="form-select form-select-sm" id="filterSelect">
                        <option value="this_month" {{ request('filter') == 'this_month' ? 'selected' : '' }}>This Month (February)</option>
                        <option value="last_month" {{ request('filter') == 'last_month' ? 'selected' : '' }}>Last Month (January)</option>
                        <option value="last_3_months" {{ request('filter') == 'last_3_months' ? 'selected' : '' }}>Last 3 Months</option>
                        <option value="last_6_months" {{ request('filter') == 'last_6_months' ? 'selected' : '' }}>Last 6 Months</option>
                        <option value="this_year" {{ request('filter') == 'this_year' ? 'selected' : '' }}>This Year</option>
                        <option value="custom" {{ request('filter') == 'custom' ? 'selected' : '' }}>Custom Range</option>
                    </select>
                </div>

                <div class="col-md-2">
                    <label class="form-label fw-600 text-dark mb-2">Rating Filter</label>
                    <select name="rating" class="form-select form-select-sm">
                        <option value="">All Ratings</option>
                        <option value="5" {{ request('rating') == '5' ? 'selected' : '' }}>⭐⭐⭐⭐⭐ (5 Stars)</option>
                        <option value="4" {{ request('rating') == '4' ? 'selected' : '' }}>⭐⭐⭐⭐ (4 Stars)</option>
                        <option value="3" {{ request('rating') == '3' ? 'selected' : '' }}>⭐⭐⭐ (3 Stars)</option>
                        <option value="2" {{ request('rating') == '2' ? 'selected' : '' }}>⭐⭐ (2 Stars)</option>
                        <option value="1" {{ request('rating') == '1' ? 'selected' : '' }}>⭐ (1 Star)</option>
                        <option value="0" {{ request('rating') == '0' ? 'selected' : '' }}>No Rating</option>
                    </select>
                </div>

                {{-- Custom Date Inputs (Hidden by default) --}}
                <div class="col-md-2" id="customDateFrom" style="display: {{ request('filter') == 'custom' ? 'block' : 'none' }};">
                    <label class="form-label fw-600 text-dark mb-2">From Date</label>
                    <input type="date" name="date_from" class="form-control form-control-sm" value="{{ request('date_from') }}">
                </div>

                <div class="col-md-2" id="customDateTo" style="display: {{ request('filter') == 'custom' ? 'block' : 'none' }};">
                    <label class="form-label fw-600 text-dark mb-2">To Date</label>
                    <input type="date" name="date_to" class="form-control form-control-sm" value="{{ request('date_to') }}">
                </div>

                <div class="col-md-2">
                    <button type="submit" class="btn btn-primary btn-sm w-100">
                        <i class="bi bi-funnel me-2"></i>Apply Filter
                    </button>
                </div>

                @if(request('filter') || request('date_from') || request('date_to') || request('rating'))
                    <div class="col-md-12">
                        <a href="{{ route('admin.reports.customers') }}" class="btn btn-outline-secondary btn-sm">
                            <i class="bi bi-x-circle me-2"></i>Clear Filters
                        </a>
                    </div>
                @endif
            </form>
        </div>
    </div>

    {{-- Key Metrics Cards --}}
    <div class="row g-3 mb-5">
        <div class="col-md-6 col-lg-3">
            <div class="card border-0 shadow-sm stat-card h-100">
                <div class="card-body p-4">
                    <div class="d-flex justify-content-between align-items-start mb-3">
                        <div>
                            <p class="text-muted mb-1 small fw-500" style="font-size: 0.85rem; letter-spacing: 0.3px;">TOTAL CUSTOMERS</p>
                            <h3 class="mb-0 fw-bold text-dark">{{ number_format($metrics['total_customers'] ?? 0) }}</h3>
                        </div>
                        <div class="stat-icon bg-info">
                            <i class="bi bi-people"></i>
                        </div>
                    </div>
                    <div class="d-flex align-items-center text-success small">
                        <i class="bi bi-arrow-up-right me-1"></i>
                        <span>{{ $metrics['new_customers_period'] ?? 0 }} new</span>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-6 col-lg-3">
            <div class="card border-0 shadow-sm stat-card h-100">
                <div class="card-body p-4">
                    <div class="d-flex justify-content-between align-items-start mb-3">
                        <div>
                            <p class="text-muted mb-1 small fw-500" style="font-size: 0.85rem; letter-spacing: 0.3px;">ACTIVE CUSTOMERS</p>
                            <h3 class="mb-0 fw-bold text-dark">{{ number_format($metrics['active_customers'] ?? 0) }}</h3>
                        </div>
                        <div class="stat-icon bg-success">
                            <i class="bi bi-check-circle"></i>
                        </div>
                    </div>
                    <div class="d-flex align-items-center text-success small">
                        <i class="bi bi-arrow-up-right me-1"></i>
                        {{-- FIXED: guard against division by zero when total_customers is 0 --}}
                        <span>{{ ($metrics['total_customers'] ?? 0) > 0 ? round(($metrics['active_customers'] ?? 0) / $metrics['total_customers'] * 100) : 0 }}% of total</span>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-6 col-lg-3">
            <div class="card border-0 shadow-sm stat-card h-100">
                <div class="card-body p-4">
                    <div class="d-flex justify-content-between align-items-start mb-3">
                        <div>
                            <p class="text-muted mb-1 small fw-500" style="font-size: 0.85rem; letter-spacing: 0.3px;">AVG CUSTOMER VALUE</p>
                            <h3 class="mb-0 fw-bold text-dark">₱{{ number_format($metrics['avg_customer_value'] ?? 0, 2) }}</h3>
                        </div>
                        <div class="stat-icon bg-primary">
                            <i class="bi bi-currency-peso"></i>
                        </div>
                    </div>
                    <div class="d-flex align-items-center text-muted small">
                        <i class="bi bi-dash me-1"></i>
                        <span>Lifetime value</span>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-6 col-lg-3">
            <div class="card border-0 shadow-sm stat-card h-100">
                <div class="card-body p-4">
                    <div class="d-flex justify-content-between align-items-start mb-3">
                        <div>
                            <p class="text-muted mb-1 small fw-500" style="font-size: 0.85rem; letter-spacing: 0.3px;">AVG RATING</p>
                            <h3 class="mb-0 fw-bold text-dark">{{ number_format($metrics['avg_rating'] ?? 0, 1) }} ⭐</h3>
                        </div>
                        <div class="stat-icon bg-warning">
                            <i class="bi bi-star-fill"></i>
                        </div>
                    </div>
                    <div class="d-flex align-items-center text-success small">
                        <i class="bi bi-arrow-up-right me-1"></i>
                        <span>{{ $metrics['rated_customers_count'] ?? 0 }} ratings</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Analytics Section --}}
    <div class="row g-3 mb-5">
        {{-- Demographics --}}
        <div class="col-lg-6">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-light border-0 p-4">
                    <h6 class="mb-0 fw-bold text-dark">
                        <i class="bi bi-pie-chart text-primary me-2"></i>Customer Demographics
                    </h6>
                </div>
                <div class="card-body p-4">
                    @php
                        $totalForBars = max(1, ($metrics['new_customers_period'] ?? 0) + ($metrics['returning_customers'] ?? 0) + ($metrics['inactive_customers'] ?? 0));
                    @endphp
                    <div class="mb-4">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <span class="text-muted small">New Customers</span>
                            <span class="fw-bold">{{ $metrics['new_customers_period'] ?? 0 }}</span>
                        </div>
                        <div class="progress" style="height: 8px; border-radius: 4px;">
                            <div class="progress-bar bg-success" style="width: {{ round(($metrics['new_customers_period'] ?? 0) / $totalForBars * 100) }}%"></div>
                        </div>
                    </div>
                    <div class="mb-4">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <span class="text-muted small">Returning Customers</span>
                            <span class="fw-bold">{{ $metrics['returning_customers'] ?? 0 }}</span>
                        </div>
                        <div class="progress" style="height: 8px; border-radius: 4px;">
                            <div class="progress-bar bg-primary" style="width: {{ round(($metrics['returning_customers'] ?? 0) / $totalForBars * 100) }}%"></div>
                        </div>
                    </div>
                    <div class="mb-4">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <span class="text-muted small">Inactive Customers</span>
                            <span class="fw-bold">{{ $metrics['inactive_customers'] ?? 0 }}</span>
                        </div>
                        <div class="progress" style="height: 8px; border-radius: 4px;">
                            <div class="progress-bar bg-danger" style="width: {{ round(($metrics['inactive_customers'] ?? 0) / $totalForBars * 100) }}%"></div>
                        </div>
                    </div>
                    <hr>
                    <div class="small text-muted">
                        <p class="mb-2"><strong>By Location:</strong> Data comes from customer addresses and branch associations</p>
                        <p class="mb-0"><strong>By Segment:</strong> Categorized by spending level and service frequency</p>
                    </div>
                </div>
            </div>
        </div>

        {{-- Activity Metrics --}}
        <div class="col-lg-6">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-light border-0 p-4">
                    <h6 class="mb-0 fw-bold text-dark">
                        <i class="bi bi-activity text-success me-2"></i>Activity & Rating Metrics
                    </h6>
                </div>
                <div class="card-body p-4">
                    <div class="row g-3">
                        <div class="col-sm-6">
                            <div class="p-3 rounded-3 bg-light">
                                <p class="text-muted small mb-2">Avg Orders / Customer</p>
                                <h4 class="mb-0 fw-bold text-dark">{{ $metrics['avg_orders_per_customer'] ?? 0 }}</h4>
                            </div>
                        </div>
                        <div class="col-sm-6">
                            <div class="p-3 rounded-3 bg-light">
                                <p class="text-muted small mb-2">Orders This Period</p>
                                <h4 class="mb-0 fw-bold text-dark">{{ $metrics['total_orders_period'] ?? 0 }}</h4>
                            </div>
                        </div>
                        <div class="col-sm-6">
                            <div class="p-3 rounded-3 bg-light">
                                <p class="text-muted small mb-2">Total Revenue</p>
                                <h4 class="mb-0 fw-bold text-dark">₱{{ number_format($metrics['total_revenue_period'] ?? 0, 2) }}</h4>
                            </div>
                        </div>
                        <div class="col-sm-6">
                            <div class="p-3 rounded-3 bg-light">
                                <p class="text-muted small mb-2">Avg Spend / Customer</p>
                                <h4 class="mb-0 fw-bold text-dark">₱{{ number_format($metrics['avg_spend_period'] ?? 0, 2) }}</h4>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Customers Table --}}
    <div class="card border-0 shadow-sm">
        <div class="card-header bg-light border-0 p-4 d-flex justify-content-between align-items-center">
            <h6 class="mb-0 fw-bold text-dark">
                <i class="bi bi-table text-dark me-2"></i>Detailed Customer List
            </h6>
            <form method="POST" action="{{ route('admin.reports.export') }}" class="d-inline">
                @csrf
                <input type="hidden" name="type" value="customers">
                <input type="hidden" name="filter" value="{{ request('filter', 'this_month') }}">
                <input type="hidden" name="date_from" value="{{ request('date_from') }}">
                <input type="hidden" name="date_to" value="{{ request('date_to') }}">
                <button type="submit" class="btn btn-sm btn-outline-success">
                    <i class="bi bi-download me-2"></i>Export CSV
                </button>
            </form>
        </div>
        <div class="card-body p-0">
            @if($customers->count() > 0)
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th class="fw-600">Customer Name</th>
                                <th>Email</th>
                                <th>Phone</th>
                                <th class="text-center">Orders</th>
                                <th class="text-end">Spent</th>
                                <th class="text-center">Avg Rating</th>
                                <th class="text-center">Rating Count</th>
                                <th class="text-center">Status</th>
                                <th>Joined</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($customers as $customer)
                                @php
                                    $totalSpent = $customer->laundries()
                                        ->where('payment_status', 'paid')
                                        ->sum('total_amount');
                                    $isActive = $customer->laundries()
                                        ->where('created_at', '>=', now()->subDays(30))
                                        ->exists();
                                    $avgRating = $customer->ratings?->avg('rating') ?? 0;
                                    $ratingCount = $customer->ratings?->count() ?? 0;
                                    $ratingClass = $avgRating > 0 ? 'badge-' . ($avgRating >= 4 ? 'success' : ($avgRating >= 3 ? 'warning' : 'danger')) : 'badge-secondary';
                                @endphp
                                <tr>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <div class="avatar-sm rounded-circle bg-light me-3 d-flex align-items-center justify-content-center" style="width: 36px; height: 36px;">
                                                <span class="small fw-bold text-dark">{{ substr($customer->name, 0, 1) }}</span>
                                            </div>
                                            <a href="{{ route('admin.customers.show', $customer->id) }}" class="text-decoration-none fw-500">
                                                {{ $customer->name }}
                                            </a>
                                        </div>
                                    </td>
                                    <td>
                                        <small class="text-muted">{{ $customer->email }}</small>
                                    </td>
                                    <td>
                                        <small>{{ $customer->phone }}</small>
                                    </td>
                                    <td class="text-center">
                                        <span class="badge bg-light text-dark">{{ $customer->laundries_count ?? 0 }}</span>
                                    </td>
                                    <td class="text-end fw-600">₱{{ number_format($totalSpent, 2) }}</td>
                                    <td class="text-center">
                                        @if($ratingCount > 0)
                                            <button type="button" class="badge {{ $ratingClass }} border-0" data-bs-toggle="modal" data-bs-target="#ratingModal{{ $customer->id }}" style="cursor: pointer;">
                                                {{ number_format($avgRating, 1) }} ⭐
                                            </button>
                                        @else
                                            <span class="badge bg-secondary">N/A</span>
                                        @endif
                                    </td>
                                    <td class="text-center">
                                        @if($ratingCount > 0)
                                            <span class="badge bg-info">{{ $ratingCount }}</span>
                                        @else
                                            <span class="text-muted small">—</span>
                                        @endif
                                    </td>
                                    <td class="text-center">
                                        @if($isActive)
                                            <span class="badge bg-success-soft text-success">Active</span>
                                        @else
                                            <span class="badge bg-secondary-soft text-secondary">Inactive</span>
                                        @endif
                                    </td>
                                    <td>
                                        <small class="text-muted">{{ $customer->created_at->format('M d, Y') }}</small>
                                    </td>
                                </tr>

                                {{-- Rating Details Modal --}}
                                @if($ratingCount > 0)
                                    <div class="modal fade" id="ratingModal{{ $customer->id }}" tabindex="-1">
                                        <div class="modal-dialog modal-sm">
                                            <div class="modal-content">
                                                <div class="modal-header">
                                                    <h6 class="modal-title">{{ $customer->name }} - Ratings</h6>
                                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                </div>
                                                <div class="modal-body">
                                                    @foreach($customer->ratings->sortByDesc('created_at') as $rating)
                                                        <div class="mb-3 pb-3 border-bottom">
                                                            <div class="d-flex justify-content-between align-items-start mb-2">
                                                                <div>
                                                                    <span class="text-warning">
                                                                        @for($i = 0; $i < $rating->rating; $i++)
                                                                            <i class="bi bi-star-fill"></i>
                                                                        @endfor
                                                                        @for($i = $rating->rating; $i < 5; $i++)
                                                                            <i class="bi bi-star"></i>
                                                                        @endfor
                                                                    </span>
                                                                </div>
                                                                <small class="text-muted">{{ $rating->created_at->format('M d, Y') }}</small>
                                                            </div>
                                                            <small class="text-muted d-block mb-2">
                                                                @if($rating->laundry_id)
                                                                    Order #{{ $rating->laundry_id }}
                                                                @elseif($rating->branch_id)
                                                                    Branch: {{ $rating->branch->name ?? 'N/A' }}
                                                                @else
                                                                    N/A
                                                                @endif
                                                            </small>
                                                            <small>{{ $rating->comment }}</small>
                                                        </div>
                                                    @endforeach
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                @endif
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="p-4 border-top">
                    {{ $customers->links() }}
                </div>
            @else
                <div class="text-center py-5">
                    <i class="bi bi-inbox text-muted" style="font-size: 3rem;"></i>
                    <p class="text-muted mt-3">No customers found for the selected period</p>
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
    background: linear-gradient(90deg, var(--bs-info), transparent);
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

.stat-icon.bg-info { background: linear-gradient(135deg, #0dcaf0, #0aa2c0); }
.stat-icon.bg-success { background: linear-gradient(135deg, #198754, #146c43); }
.stat-icon.bg-primary { background: linear-gradient(135deg, #0d6efd, #0a58ca); }
.stat-icon.bg-warning { background: linear-gradient(135deg, #ffc107, #e0a800); }

.bg-success-soft { background-color: rgba(25, 135, 84, 0.1); }
.bg-secondary-soft { background-color: rgba(108, 117, 125, 0.1); }

.badge-success { background-color: #198754; }
.badge-warning { background-color: #ffc107; color: #000; }
.badge-danger { background-color: #dc3545; }

.avatar-sm { flex-shrink: 0; }
.table-light { background-color: #f8f9fa; }
.fw-600 { font-weight: 600; }

.form-select-sm, .form-control-sm {
    border-radius: 8px;
}
</style>

<script>
document.getElementById('filterSelect').addEventListener('change', function() {
    const customDateFrom = document.getElementById('customDateFrom');
    const customDateTo = document.getElementById('customDateTo');

    if (this.value === 'custom') {
        customDateFrom.style.display = 'block';
        customDateTo.style.display = 'block';
    } else {
        customDateFrom.style.display = 'none';
        customDateTo.style.display = 'none';
    }
});
</script>
@endsection
