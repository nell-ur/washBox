@extends('admin.layouts.app')

@section('page-title', 'Pickup Requests')

@section('content')
    <div class="container-fluid">
        {{-- Header --}}
        <div class="d-flex justify-content-between align-items-center mb-4">
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
                <form method="GET" action="{{ route('admin.pickups.index') }}" class="row g-3">
                    <div class="col-md-3">
                        <label class="form-label">Status</label>
                        <select name="status" class="form-select">
                            <option value="all" {{ request('status') == 'all' ? 'selected' : '' }}>All Status</option>
                            <option value="pending" {{ request('status') == 'pending' ? 'selected' : '' }}>Pending</option>
                            <option value="accepted" {{ request('status') == 'accepted' ? 'selected' : '' }}>Accepted</option>
                            <option value="en_route" {{ request('status') == 'en_route' ? 'selected' : '' }}>En Route</option>
                            <option value="picked_up" {{ request('status') == 'picked_up' ? 'selected' : '' }}>Picked Up
                            </option>
                            <option value="cancelled" {{ request('status') == 'cancelled' ? 'selected' : '' }}>Cancelled
                            </option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Branch</label>
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
                        <label class="form-label">Date</label>
                        <input type="date" name="date" class="form-control" value="{{ request('date') }}">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Search</label>
                        <input type="text" name="search" class="form-control" placeholder="Customer name or address"
                            value="{{ request('search') }}">
                    </div>
                    <div class="col-12">
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-funnel"></i> Apply Filters
                        </button>
                        <a href="{{ route('admin.pickups.index') }}" class="btn btn-outline-secondary">
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
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Customer</th>
                                    <th>Branch</th>
                                    <th>Pickup Address</th>
                                    <th>Preferred Date/Time</th>
                                    <th>Fees (Quoted)</th>
                                    <th>Status</th>
                                    <th>Created</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            {{-- Update the Table Body --}}
                            <tbody>
                                @foreach($pickups as $pickup)
                                    <tr>
                                        <td>#{{ $pickup->id }}</td>
                                        <td>
                                            <div>
                                                <strong>{{ $pickup->customer->name }}</strong><br>
                                                <small class="text-muted">{{ $pickup->contact_phone }}</small>
                                            </div>
                                        </td>
                                        <td>{{ $pickup->branch->name }}</td>
                                        <td>
                                            <small class="d-block">{{ Str::limit($pickup->pickup_address, 40) }}</small>
                                            @if($pickup->landmark)
                                                <small class="text-info"><i class="bi bi-geo"></i> {{ $pickup->landmark }}</small>
                                            @endif
                                        </td>
                                        <td>
                                            <div>{{ $pickup->preferred_date->format('M d, Y') }}</div>
                                            <small class="text-muted">{{ $pickup->preferred_time_slot }}</small>
                                        </td>
                                        <td>
                                            <div class="fw-bold text-success">₱{{ number_format($pickup->total_fee, 2) }}</div>
                                            <small class="text-muted" style="font-size: 0.75rem;">
                                                P: ₱{{ number_format($pickup->pickup_fee, 0) }} | D:
                                                ₱{{ number_format($pickup->delivery_fee, 0) }}
                                            </small>
                                        </td>
                                        <td>
                                            <span class="badge @if($pickup->status == 'pending') bg-warning
                                               @elseif($pickup->status == 'picked_up') bg-success
                                               @else bg-primary @endif">
                                                {{ ucfirst($pickup->status) }}
                                            </span>
                                        </td>
                                        <td> {{-- Added Created At Column --}}
                                            <div class="small text-muted" title="{{ $pickup->created_at->format('Y-m-d H:i:s') }}">
                                                {{ $pickup->created_at->diffForHumans() }}
                                            </div>
                                        </td>
                                        <td>
                                            <div class="btn-group">
                                                <a href="{{ route('admin.pickups.show', $pickup->id) }}"
                                                    class="btn btn-sm btn-outline-primary">
                                                    <i class="bi bi-eye"></i>
                                                </a>
                                                @if($pickup->status == 'picked_up' && !$pickup->laundries_id)
                                                    <a href="{{ route('admin.laundries.create', ['pickup_id' => $pickup->id]) }}"
                                                        class="btn btn-sm btn-success">
                                                        <i class="bi bi-plus-circle"></i> Create Laundry
                                                    </a>
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
                        {{ $pickups->links() }}
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
            setInterval(function () {
                if (document.visibilityState === 'visible') {
                    window.location.reload();
                }
            }, 30000);
        @endif
    </script>
@endpush
