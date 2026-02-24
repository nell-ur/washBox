@extends('staff.layouts.staff')

@section('title', 'Pickup Requests - Customers')

@section('content')
<div class="container-fluid py-4">
    {{-- Header --}}
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="fw-bold mb-0">
            <i class="bi bi-people"></i> Customers Who Requested Laundry Pickup
        </h2>
        <div class="d-flex gap-2">
            <a href="{{ route('staff.pickups.index') }}" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left"></i> Back to Pickups
            </a>
            <button class="btn btn-outline-secondary" onclick="window.location.reload()">
                <i class="bi bi-arrow-clockwise"></i> Refresh
            </button>
        </div>
    </div>

    {{-- Branch Info --}}
    <div class="alert alert-info mb-4">
        <i class="bi bi-info-circle"></i>
        Showing pickup requests from <strong>{{ auth()->user()->branch->name }}</strong> branch only
    </div>

    {{-- Success/Error Messages --}}
    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="bi bi-check-circle"></i> {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    {{-- Statistics Cards --}}
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card border-primary">
                <div class="card-body">
                    <h6 class="text-muted mb-1">Total Customers</h6>
                    <h3 class="mb-0">{{ $pickupRequests->unique('customer_id')->count() }}</h3>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-success">
                <div class="card-body">
                    <h6 class="text-muted mb-1">Total Requests</h6>
                    <h3 class="mb-0">{{ $pickupRequests->count() }}</h3>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-warning">
                <div class="card-body">
                    <h6 class="text-muted mb-1">Pending</h6>
                    <h3 class="mb-0">{{ $pickupRequests->where('status', 'pending')->count() }}</h3>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-info">
                <div class="card-body">
                    <h6 class="text-muted mb-1">Completed</h6>
                    <h3 class="mb-0">{{ $pickupRequests->where('status', 'picked_up')->count() }}</h3>
                </div>
            </div>
        </div>
    </div>

    {{-- Customers Table --}}
    <div class="card">
        <div class="card-header">
            <h5 class="mb-0">
                <i class="bi bi-table"></i> Customer Pickup Requests
            </h5>
        </div>
        <div class="card-body">
            @if($pickupRequests->count() > 0)
                <div class="table-responsive">
                    <table class="table table-bordered table-striped table-hover">
                        <thead class="table-light">
                            <tr>
                                <th>#</th>
                                <th>Pickup ID</th>
                                <th>Customer Name</th>
                                <th>Email</th>
                                <th>Phone</th>
                                <th>Pickup Address</th>
                                <th>Preferred Date</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($pickupRequests as $pickup)
                                <tr>
                                    <td>{{ $loop->iteration }}</td>
                                    <td>
                                        <strong>#{{ $pickup->id }}</strong>
                                        @if($pickup->assigned_to == auth()->id())
                                            <br><span class="badge bg-dark text-white">Mine</span>
                                        @endif
                                    </td>
                                    <td>
                                        <strong>{{ $pickup->customer->name ?? 'N/A' }}</strong>
                                        <br>
                                        <small class="text-muted">ID: #{{ $pickup->customer->id ?? 'N/A' }}</small>
                                    </td>
                                    <td>{{ $pickup->customer->email ?? 'N/A' }}</td>
                                    <td>{{ $pickup->contact_phone ?? 'N/A' }}</td>
                                    <td>
                                        <small>{{ Str::limit($pickup->pickup_address, 40) }}</small>
                                        @if($pickup->landmark)
                                            <br>
                                            <small class="text-muted">
                                                <i class="bi bi-signpost"></i> {{ Str::limit($pickup->landmark, 30) }}
                                            </small>
                                        @endif
                                    </td>
                                    <td>{{ \Carbon\Carbon::parse($pickup->preferred_date)->format('M d, Y') }}</td>
                                    <td>
                                        @if($pickup->status == 'pending')
                                            <span class="badge bg-warning text-dark">Pending</span>
                                        @elseif($pickup->status == 'accepted')
                                            <span class="badge bg-info text-white">Accepted</span>
                                        @elseif($pickup->status == 'en_route')
                                            <span class="badge bg-primary">En Route</span>
                                        @elseif($pickup->status == 'picked_up')
                                            <span class="badge bg-success">Picked Up</span>
                                        @elseif($pickup->status == 'cancelled')
                                            <span class="badge bg-danger">Cancelled</span>
                                        @endif
                                    </td>
                                    <td>
                                        <div class="btn-group" role="group">
                                            <a href="{{ route('staff.pickups.show', $pickup->id) }}"
                                               class="btn btn-sm btn-outline-primary"
                                               title="View Details">
                                                <i class="bi bi-eye"></i>
                                            </a>

                                            @if($pickup->customer)
                                                <a href="tel:{{ $pickup->contact_phone }}"
                                                   class="btn btn-sm btn-outline-success"
                                                   title="Call Customer">
                                                    <i class="bi bi-telephone"></i>
                                                </a>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                {{-- Export Options --}}
                <div class="mt-3 d-flex justify-content-between align-items-center">
                    <div>
                        <small class="text-muted">Showing {{ $pickupRequests->count() }} pickup requests</small>
                    </div>
                    <div>
                        <button class="btn btn-sm btn-outline-success" onclick="window.print()">
                            <i class="bi bi-printer"></i> Print
                        </button>
                    </div>
                </div>
            @else
                <div class="text-center py-5">
                    <i class="bi bi-inbox fs-1 text-muted"></i>
                    <p class="text-muted mt-2">No pickup requests found for your branch</p>
                    <a href="{{ route('staff.pickups.create') }}" class="btn btn-primary mt-2">
                        <i class="bi bi-plus-circle"></i> Create First Pickup Request
                    </a>
                </div>
            @endif
        </div>
    </div>
</div>
@endsection

@push('styles')
<style>
    @media print {
        .btn, .alert, .card-header {
            display: none !important;
        }
    }
</style>
@endpush
