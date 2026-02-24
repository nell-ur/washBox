@extends('staff.layouts.staff')

@section('title', 'Pickup Requests')

@section('content')
<div class="container-fluid">
    {{-- Header --}}
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0">Pickup Requests</h1>
        <div class="d-flex gap-2">
            <button class="btn btn-outline-secondary" onclick="window.location.reload()">
                <i class="bi bi-arrow-clockwise"></i> Refresh
            </button>
        </div>
    </div>

    {{-- Success/Error Messages --}}
    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="bi bi-check-circle"></i> {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="bi bi-exclamation-circle"></i> {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    {{-- Statistics Cards --}}
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card border-warning">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-muted mb-1">Pending</h6>
                            <h3 class="mb-0">{{ $pickups->where('status', 'pending')->count() }}</h3>
                        </div>
                        <div class="text-warning">
                            <i class="bi bi-clock-history fs-1"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-info">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-muted mb-1">Accepted</h6>
                            <h3 class="mb-0">{{ $pickups->where('status', 'accepted')->count() }}</h3>
                        </div>
                        <div class="text-info">
                            <i class="bi bi-check-circle fs-1"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-primary">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-muted mb-1">En Route</h6>
                            <h3 class="mb-0">{{ $pickups->where('status', 'en_route')->count() }}</h3>
                        </div>
                        <div class="text-primary">
                            <i class="bi bi-truck fs-1"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-success">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-muted mb-1">Picked Up</h6>
                            <h3 class="mb-0">{{ $pickups->where('status', 'picked_up')->count() }}</h3>
                        </div>
                        <div class="text-success">
                            <i class="bi bi-box-seam fs-1"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Filters --}}
    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" action="{{ route('staff.pickups.index') }}" class="row g-3">
                <div class="col-md-3">
                    <select name="status" class="form-select">
                        <option value="all" {{ request('status') == 'all' || !request('status') ? 'selected' : '' }}>All Status</option>
                        <option value="pending" {{ request('status') == 'pending' ? 'selected' : '' }}>Pending</option>
                        <option value="accepted" {{ request('status') == 'accepted' ? 'selected' : '' }}>Accepted</option>
                        <option value="en_route" {{ request('status') == 'en_route' ? 'selected' : '' }}>En Route</option>
                        <option value="picked_up" {{ request('status') == 'picked_up' ? 'selected' : '' }}>Picked Up</option>
                        <option value="cancelled" {{ request('status') == 'cancelled' ? 'selected' : '' }}>Cancelled</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <select name="branch_id" class="form-select">
                        <option value="">All Branches</option>
                        @foreach($branches as $branch)
                            <option value="{{ $branch->id }}" {{ request('branch_id') == $branch->id ? 'selected' : '' }}>
                                {{ $branch->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <input type="date" name="date" class="form-control" value="{{ request('date') }}" placeholder="mm/dd/yyyy">
                </div>
                <div class="col-md-3">
                    <input type="text" name="search" class="form-control" placeholder="Customer name or address" value="{{ request('search') }}">
                </div>
                <div class="col-12">
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-funnel"></i> Apply Filters
                    </button>
                    <a href="{{ route('staff.pickups.index') }}" class="btn btn-outline-secondary">
                        <i class="bi bi-x-circle"></i> Clear
                    </a>
                </div>
            </form>
        </div>
    </div>

    {{-- Pickup Requests Table --}}
    <div class="card">
        <div class="card-body">
            @if($pickups->count() > 0)
                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>ID</th>
                                <th>Customer</th>
                                <th>Branch</th>
                                <th>Pickup Address</th>
                                <th>Preferred Date/Time</th>
                                <th>Status</th>
                                <th>Created</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($pickups as $pickup)
                                <tr>
                                    <td><strong>#{{ $pickup->id }}</strong></td>
                                    <td>
                                        <div>{{ $pickup->customer->name }}</div>
                                    </td>
                                    <td>{{ $pickup->branch->name }}</td>
                                    <td>
                                        <div>{{ Str::limit($pickup->pickup_address, 40) }}</div>
                                        @if($pickup->latitude && $pickup->longitude)
                                            <a href="https://www.google.com/maps?q={{ $pickup->latitude }},{{ $pickup->longitude }}"
                                               target="_blank"
                                               class="text-primary text-decoration-none">
                                                <i class="bi bi-geo-alt"></i> Map
                                            </a>
                                        @endif
                                    </td>
                                    <td>
                                        <div>{{ \Carbon\Carbon::parse($pickup->preferred_date)->format('M d, Y') }}</div>
                                        @if($pickup->preferred_time)
                                            <small class="text-muted">{{ date('g:i A', strtotime($pickup->preferred_time)) }}</small>
                                        @elseif($pickup->preferred_time_slot)
                                            <small class="text-muted">{{ $pickup->preferred_time_slot }}</small>
                                        @endif
                                    </td>
                                    <td>
                                        @if($pickup->status == 'pending')
                                            <span class="badge bg-warning text-dark">Pending</span>
                                        @elseif($pickup->status == 'accepted')
                                            <span class="badge bg-info">Accepted</span>
                                        @elseif($pickup->status == 'en_route')
                                            <span class="badge bg-primary">En Route</span>
                                        @elseif($pickup->status == 'picked_up')
                                            <span class="badge bg-success">Picked Up</span>
                                        @elseif($pickup->status == 'cancelled')
                                            <span class="badge bg-danger">Cancelled</span>
                                        @endif
                                    </td>
                                    <td>
                                        <small class="text-muted">{{ $pickup->created_at->diffForHumans() }}</small>
                                    </td>
                                    <td>
                                        <div class="d-flex gap-1">
                                            <a href="{{ route('staff.pickups.show', $pickup->id) }}"
                                               class="btn btn-sm btn-outline-primary"
                                               title="View">
                                                <i class="bi bi-eye"></i>
                                            </a>

                                            @if($pickup->status == 'pending')
                                                <form action="{{ route('staff.pickups.accept', $pickup->id) }}" method="POST" class="d-inline">
                                                    @csrf
                                                    <button type="submit"
                                                            class="btn btn-sm btn-success"
                                                            title="Accept"
                                                            onclick="return confirm('Accept this pickup request?')">
                                                        <i class="bi bi-check-circle"></i> Accept
                                                    </button>
                                                </form>
                                            @endif

                                            @if($pickup->status == 'accepted' && $pickup->assigned_to == auth()->id())
                                                <form action="{{ route('staff.pickups.en-route', $pickup->id) }}" method="POST" class="d-inline">
                                                    @csrf
                                                    <button type="submit"
                                                            class="btn btn-sm btn-dark"
                                                            title="En Route">
                                                        <i class="bi bi-truck"></i> En Route
                                                    </button>
                                                </form>
                                            @endif

                                            @if($pickup->status == 'en_route' && $pickup->assigned_to == auth()->id())
                                                <form action="{{ route('staff.pickups.picked-up', $pickup->id) }}" method="POST" class="d-inline">
                                                    @csrf
                                                    <button type="submit"
                                                            class="btn btn-sm btn-success"
                                                            title="Picked Up">
                                                        <i class="bi bi-box-seam"></i> Picked Up
                                                    </button>
                                                </form>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                {{-- Pagination --}}
                <div class="mt-3">
                    {{ $pickups->appends(request()->query())->links() }}
                </div>
            @else
                <div class="text-center py-5">
                    <i class="bi bi-inbox fs-1 text-muted"></i>
                    <p class="text-muted mt-2">No pickup requests found</p>
                </div>
            @endif
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
// Auto-refresh every 30 seconds for pending requests
@if(request('status') == 'pending' || !request('status'))
    setInterval(function() {
        if (document.visibilityState === 'visible') {
            window.location.reload();
        }
    }, 30000);
@endif
</script>
@endpush
