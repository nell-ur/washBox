@extends('admin.layouts.app')

@section('page-title', 'Branch Ratings Report')

@section('content')
<div class="container-fluid px-4 py-5">
    {{-- Header Section --}}
    <div class="mb-5">
        <div class="d-flex justify-content-between align-items-end">
            <div>
                <h4 class="mb-2">Branch Ratings Report</h4>
                <p class="text-muted mb-0" style="font-size: 0.95rem;">
                    Monitor customer satisfaction ratings across all branches
                </p>
            </div>
            <div class="d-flex gap-2">
                <a href="{{ route('admin.reports.index') }}" class="btn btn-outline-secondary btn-sm">
                    <i class="bi bi-arrow-left me-2"></i>Back to Reports
                </a>
                <a href="{{ route('admin.reports.branch-ratings.export', request()->all()) }}" class="btn btn-outline-primary btn-sm">
                    <i class="bi bi-download me-2"></i>Export Report
                </a>
            </div>
        </div>
    </div>

    {{-- Filter Section --}}
    <div class="card border-0 shadow-sm mb-5">
        <div class="card-body p-4">
            <form method="GET" action="{{ route('admin.reports.branch-ratings') }}" class="row g-3 align-items-end">
                <div class="col-md-3">
                    <label class="form-label fw-600 text-dark mb-2">Date Range</label>
                    <select name="filter" class="form-select form-select-sm" id="filterSelect">
                        <option value="today" {{ request('filter') == 'today' ? 'selected' : '' }}>Today</option>
                        <option value="this_week" {{ request('filter') == 'this_week' ? 'selected' : '' }}>This Week</option>
                        <option value="this_month" {{ request('filter') == 'this_month' ? 'selected' : '' }}>This Month</option>
                        <option value="last_month" {{ request('filter') == 'last_month' ? 'selected' : '' }}>Last Month</option>
                        <option value="last_3_months" {{ request('filter') == 'last_3_months' ? 'selected' : '' }}>Last 3 Months</option>
                        <option value="last_6_months" {{ request('filter') == 'last_6_months' ? 'selected' : '' }}>Last 6 Months</option>
                        <option value="this_year" {{ request('filter') == 'this_year' ? 'selected' : '' }}>This Year</option>
                        <option value="custom" {{ request('filter') == 'custom' ? 'selected' : '' }}>Custom Range</option>
                    </select>
                </div>

                <div class="col-md-2">
                    <label class="form-label fw-600 text-dark mb-2">Branch</label>
                    <select name="branch_id" class="form-select form-select-sm">
                        <option value="">All Branches</option>
                        @foreach($allBranches as $branch)
                            <option value="{{ $branch->id }}" {{ request('branch_id') == $branch->id ? 'selected' : '' }}>
                                {{ $branch->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="col-md-2">
                    <label class="form-label fw-600 text-dark mb-2">Rating</label>
                    <select name="rating" class="form-select form-select-sm">
                        <option value="">All Ratings</option>
                        @for($i = 5; $i >= 1; $i--)
                            <option value="{{ $i }}" {{ request('rating') == $i ? 'selected' : '' }}>
                                {{ $i }} Star{{ $i > 1 ? 's' : '' }}
                            </option>
                        @endfor
                    </select>
                </div>

                {{-- Custom Date Inputs --}}
                <div class="col-md-2" id="customDateFrom" style="display: {{ request('filter') == 'custom' ? 'block' : 'none' }};">
                    <label class="form-label fw-600 text-dark mb-2">From Date</label>
                    <input type="date" name="date_from" class="form-control form-control-sm" value="{{ request('date_from', now()->startOfMonth()->format('Y-m-d')) }}">
                </div>

                <div class="col-md-2" id="customDateTo" style="display: {{ request('filter') == 'custom' ? 'block' : 'none' }};">
                    <label class="form-label fw-600 text-dark mb-2">To Date</label>
                    <input type="date" name="date_to" class="form-control form-control-sm" value="{{ request('date_to', now()->format('Y-m-d')) }}">
                </div>

                <div class="col-md-2">
                    <button type="submit" class="btn btn-primary btn-sm w-100">
                        <i class="bi bi-funnel me-2"></i>Apply Filter
                    </button>
                </div>

                @if(request()->anyFilled(['filter', 'branch_id', 'rating', 'date_from', 'date_to']))
                    <div class="col-12 mt-2">
                        <a href="{{ route('admin.reports.branch-ratings') }}" class="btn btn-outline-secondary btn-sm">
                            <i class="bi bi-x-circle me-2"></i>Clear Filters
                        </a>
                    </div>
                @endif
            </form>
        </div>
    </div>

    {{-- Summary Cards --}}
    <div class="row g-3 mb-5">
        <div class="col-md-6 col-lg-3">
            <div class="card border-0 shadow-sm stat-card h-100">
                <div class="card-body p-4">
                    <div class="d-flex justify-content-between align-items-start mb-3">
                        <div>
                            <p class="text-muted mb-1 small fw-500">TOTAL RATINGS</p>
                            <h3 class="mb-0 fw-bold text-dark">{{ number_format($summary['total_ratings']) }}</h3>
                        </div>
                        <div class="stat-icon bg-info">
                            <i class="bi bi-chat-text"></i>
                        </div>
                    </div>
                    <div class="d-flex align-items-center text-muted small">
                        <i class="bi bi-building me-1"></i>
                        <span>{{ $summary['branches_with_ratings'] }} branches rated</span>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-6 col-lg-3">
            <div class="card border-0 shadow-sm stat-card h-100">
                <div class="card-body p-4">
                    <div class="d-flex justify-content-between align-items-start mb-3">
                        <div>
                            <p class="text-muted mb-1 small fw-500">OVERALL AVERAGE</p>
                            <h3 class="mb-0 fw-bold text-dark">{{ number_format($summary['avg_rating'], 2) }} ⭐</h3>
                        </div>
                        <div class="stat-icon bg-warning">
                            <i class="bi bi-star-fill"></i>
                        </div>
                    </div>
                    <div class="d-flex align-items-center text-muted small">
                        <i class="bi bi-arrow-up-right me-1"></i>
                        <span>out of 5 stars</span>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-6 col-lg-3">
            <div class="card border-0 shadow-sm stat-card h-100">
                <div class="card-body p-4">
                    <div class="d-flex justify-content-between align-items-start mb-3">
                        <div>
                            <p class="text-muted mb-1 small fw-500">WITH COMMENTS</p>
                            <h3 class="mb-0 fw-bold text-dark">{{ number_format($summary['total_comments']) }}</h3>
                        </div>
                        <div class="stat-icon bg-success">
                            <i class="bi bi-chat-quote"></i>
                        </div>
                    </div>
                    <div class="d-flex align-items-center text-success small">
                        <i class="bi bi-percent me-1"></i>
                        <span>{{ $summary['total_ratings'] > 0 ? round(($summary['total_comments'] / $summary['total_ratings']) * 100) : 0 }}% feedback rate</span>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-6 col-lg-3">
            <div class="card border-0 shadow-sm stat-card h-100">
                <div class="card-body p-4">
                    <div class="d-flex justify-content-between align-items-start mb-3">
                        <div>
                            <p class="text-muted mb-1 small fw-500">SATISFACTION RATE</p>
                            <h3 class="mb-0 fw-bold text-dark">
                                @php
                                    $fiveStarCount = $summary['rating_distribution'][5]['count'] ?? 0;
                                    $fourStarCount = $summary['rating_distribution'][4]['count'] ?? 0;
                                    $satisfaction = ($fiveStarCount + $fourStarCount) / max($summary['total_ratings'], 1) * 100;
                                @endphp
                                {{ number_format($satisfaction, 1) }}%
                            </h3>
                        </div>
                        <div class="stat-icon bg-primary">
                            <i class="bi bi-emoji-smile"></i>
                        </div>
                    </div>
                    <div class="d-flex align-items-center text-primary small">
                        <i class="bi bi-star-fill me-1"></i>
                        <span>4-5 star ratings</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Rating Distribution Overview --}}
    <div class="row g-3 mb-5">
        <div class="col-lg-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-light border-0 p-4">
                    <h6 class="mb-0 fw-bold text-dark">
                        <i class="bi bi-pie-chart text-primary me-2"></i>Rating Distribution
                    </h6>
                </div>
                <div class="card-body p-4">
                    @foreach(range(5, 1, -1) as $star)
                        <div class="mb-3">
                            <div class="d-flex justify-content-between align-items-center mb-1">
                                <span>
                                    @for($i = 0; $i < $star; $i++)
                                        <i class="bi bi-star-fill text-warning small"></i>
                                    @endfor
                                </span>
                                <span class="text-muted small">
                                    {{ $summary['rating_distribution'][$star]['count'] }} ratings
                                    ({{ $summary['rating_distribution'][$star]['percentage'] }}%)
                                </span>
                            </div>
                            <div class="progress" style="height: 8px;">
                                <div class="progress-bar bg-warning" style="width: {{ $summary['rating_distribution'][$star]['percentage'] }}%"></div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>

        <div class="col-lg-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-light border-0 p-4">
                    <h6 class="mb-0 fw-bold text-dark">
                        <i class="bi bi-trophy text-warning me-2"></i>Branch Performance
                    </h6>
                </div>
                <div class="card-body p-4">
                    <div class="row">
                        <div class="col-6">
                            <h6 class="small text-muted mb-3">Top Rated Branches</h6>
                            @forelse($topBranches as $branch)
                                <div class="d-flex align-items-center justify-content-between mb-2">
                                    <span class="small">{{ $branch->name }}</span>
                                    <span class="badge bg-success">
                                        {{ number_format($branch->average_rating, 1) }} ⭐
                                    </span>
                                </div>
                            @empty
                                <p class="text-muted small">No rated branches</p>
                            @endforelse
                        </div>
                        <div class="col-6">
                            <h6 class="small text-muted mb-3">Needs Improvement</h6>
                            @forelse($needsImprovement as $branch)
                                <div class="d-flex align-items-center justify-content-between mb-2">
                                    <span class="small">{{ $branch->name }}</span>
                                    <span class="badge bg-warning text-dark">
                                        {{ number_format($branch->average_rating, 1) }} ⭐
                                    </span>
                                </div>
                            @empty
                                <p class="text-muted small">No rated branches</p>
                            @endforelse
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Branch Performance Cards --}}
    <div class="row g-3 mb-5">
        @forelse($branches as $branch)
            @if($branch['total_ratings'] > 0)
                <div class="col-md-6 col-lg-4">
                    <div class="card border-0 shadow-sm h-100">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-start mb-3">
                                <div>
                                    <h6 class="mb-1">{{ $branch['name'] }}</h6>
                                    <p class="text-muted small mb-0">Code: {{ $branch['code'] }}</p>
                                </div>
                                <div class="text-end">
                                    <span class="badge bg-warning text-dark p-2 fs-6">
                                        {{ $branch['average_rating'] }} ⭐
                                    </span>
                                    <div class="small text-muted mt-1">{{ $branch['total_ratings'] }} ratings</div>
                                </div>
                            </div>

                            {{-- Rating Distribution --}}
                            @foreach(range(5, 1, -1) as $star)
                                @php
                                    $count = $branch['distribution'][$star] ?? 0;
                                    $percentage = $branch['total_ratings'] > 0 ? ($count / $branch['total_ratings']) * 100 : 0;
                                @endphp
                                <div class="d-flex align-items-center gap-2 mb-1">
                                    <span style="width: 25px; font-size: 0.8rem;">{{ $star }}★</span>
                                    <div class="flex-grow-1">
                                        <div class="progress" style="height: 5px;">
                                            <div class="progress-bar bg-warning" style="width: {{ $percentage }}%"></div>
                                        </div>
                                    </div>
                                    <span style="width: 30px; font-size: 0.8rem;">{{ $count }}</span>
                                </div>
                            @endforeach

                            {{-- Monthly Trend Mini Chart --}}
                            @if(count($branch['trend']) > 0)
                                <hr>
                                <div class="small text-muted mb-2">Monthly Trend</div>
                                <div class="d-flex justify-content-between">
                                    @foreach($branch['trend'] as $trend)
                                        <div class="text-center" style="width: 18%;">
                                            <div class="small text-muted">{{ $trend['month'] }}</div>
                                            <span class="badge bg-light text-dark">{{ $trend['rating'] }}</span>
                                        </div>
                                    @endforeach
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            @endif
        @empty
            <div class="col-12">
                <div class="text-center py-5">
                    <i class="bi bi-star text-muted" style="font-size: 3rem;"></i>
                    <p class="text-muted mt-3">No branch ratings found for the selected period</p>
                </div>
            </div>
        @endforelse
    </div>

    {{-- Recent Ratings Table --}}
    <div class="card border-0 shadow-sm">
        <div class="card-header bg-light border-0 p-4 d-flex justify-content-between align-items-center">
            <h6 class="mb-0 fw-bold text-dark">
                <i class="bi bi-table me-2"></i>Recent Ratings & Comments
            </h6>
            <span class="badge bg-primary">{{ $recentRatings->total() }} Total</span>
        </div>
        <div class="card-body p-0">
            @if($recentRatings->count() > 0)
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Date</th>
                                <th>Branch</th>
                                <th>Customer</th>
                                <th class="text-center">Rating</th>
                                <th>Comment</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($recentRatings as $rating)
                                <tr>
                                    <td>
                                        <small>{{ $rating->created_at->format('M d, Y') }}</small>
                                        <br>
                                        <small class="text-muted">{{ $rating->created_at->format('h:i A') }}</small>
                                    </td>
                                    <td>
                                        <span class="fw-500">{{ $rating->branch->name }}</span>
                                        <br>
                                        <small class="text-muted">{{ $rating->branch->code }}</small>
                                    </td>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <div class="avatar-sm bg-light rounded-circle me-2 d-flex align-items-center justify-content-center" 
                                                 style="width: 32px; height: 32px;">
                                                <span class="small fw-bold">{{ substr($rating->customer->name, 0, 1) }}</span>
                                            </div>
                                            <span>{{ $rating->customer->name }}</span>
                                        </div>
                                    </td>
                                    <td class="text-center">
                                        <span class="badge bg-warning text-dark p-2">
                                            {{ $rating->rating }} ⭐
                                        </span>
                                    </td>
                                    <td>
                                        @if($rating->comment)
                                            <button class="btn btn-sm btn-link text-decoration-none p-0" 
                                                    data-bs-toggle="modal" 
                                                    data-bs-target="#commentModal{{ $rating->id }}">
                                                <i class="bi bi-chat-text me-1"></i>
                                                View Comment
                                            </button>
                                        @else
                                            <span class="text-muted small">No comment</span>
                                        @endif
                                    </td>
                                    <td>
                                        <button class="btn btn-sm btn-outline-primary" 
                                                data-bs-toggle="modal" 
                                                data-bs-target="#viewRatingModal{{ $rating->id }}">
                                            <i class="bi bi-eye"></i>
                                        </button>
                                    </td>
                                </tr>

                                {{-- Comment Modal --}}
                                @if($rating->comment)
                                    <div class="modal fade" id="commentModal{{ $rating->id }}" tabindex="-1">
                                        <div class="modal-dialog">
                                            <div class="modal-content">
                                                <div class="modal-header">
                                                    <h6 class="modal-title">Customer Comment</h6>
                                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                </div>
                                                <div class="modal-body">
                                                    <div class="mb-3">
                                                        <strong>Branch:</strong> {{ $rating->branch->name }}
                                                    </div>
                                                    <div class="mb-3">
                                                        <strong>Rating:</strong>
                                                        <span class="text-warning ms-2">
                                                            @for($i = 0; $i < $rating->rating; $i++)
                                                                <i class="bi bi-star-fill"></i>
                                                            @endfor
                                                        </span>
                                                    </div>
                                                    <div class="mb-3">
                                                        <strong>Comment:</strong>
                                                        <p class="mt-2 p-3 bg-light rounded">{{ $rating->comment }}</p>
                                                    </div>
                                                    <div>
                                                        <small class="text-muted">
                                                            Posted on {{ $rating->created_at->format('F d, Y h:i A') }}
                                                        </small>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                @endif

                                {{-- View Rating Modal --}}
                                <div class="modal fade" id="viewRatingModal{{ $rating->id }}" tabindex="-1">
                                    <div class="modal-dialog">
                                        <div class="modal-content">
                                            <div class="modal-header">
                                                <h6 class="modal-title">Rating Details</h6>
                                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                            </div>
                                            <div class="modal-body">
                                                <div class="mb-3">
                                                    <strong>Branch:</strong> {{ $rating->branch->name }} ({{ $rating->branch->code }})
                                                </div>
                                                <div class="mb-3">
                                                    <strong>Customer:</strong> {{ $rating->customer->name }}
                                                </div>
                                                <div class="mb-3">
                                                    <strong>Rating:</strong>
                                                    <div class="text-warning">
                                                        @for($i = 0; $i < $rating->rating; $i++)
                                                            <i class="bi bi-star-fill"></i>
                                                        @endfor
                                                        @for($i = $rating->rating; $i < 5; $i++)
                                                            <i class="bi bi-star"></i>
                                                        @endfor
                                                    </div>
                                                </div>
                                                <div class="mb-3">
                                                    <strong>Comment:</strong>
                                                    <p class="mt-2">{{ $rating->comment ?? 'No comment provided' }}</p>
                                                </div>
                                                <div>
                                                    <strong>Rated on:</strong>
                                                    <p>{{ $rating->created_at->format('F d, Y h:i A') }}</p>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="p-4 border-top">
                    {{ $recentRatings->appends(request()->query())->links() }}
                </div>
            @else
                <div class="text-center py-5">
                    <i class="bi bi-star text-muted" style="font-size: 3rem;"></i>
                    <p class="text-muted mt-3">No ratings found for the selected period</p>
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
.stat-icon.bg-warning { background: linear-gradient(135deg, #ffc107, #e0a800); color: #333; }
.stat-icon.bg-success { background: linear-gradient(135deg, #198754, #146c43); }
.stat-icon.bg-primary { background: linear-gradient(135deg, #0d6efd, #0a58ca); }

.avatar-sm {
    width: 32px;
    height: 32px;
    flex-shrink: 0;
}

.progress {
    background-color: #e9ecef;
    border-radius: 4px;
}

.table-light {
    background-color: #f8f9fa;
}
</style>

<script>
document.getElementById('filterSelect')?.addEventListener('change', function() {
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