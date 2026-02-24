@extends('admin.layouts.app')

@section('page-title', 'LAUNDRY MANAGEMENT')
@push('styles')
    <link rel="stylesheet" href="{{ asset('assets/css/laundry.css') }}">
@endpush

@section('content')
<div class="container-fluid px-4 py-4">
    {{-- Header --}}
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <p class="text-muted small mb-0">Track and manage all laundry </p>
        </div>
        <a href="{{ route('admin.laundries.create') }}" class="btn btn-primary shadow-sm" style="background: #3D3B6B; border: none;">
            <i class="bi bi-plus-circle me-2"></i>Create New Laundry
        </a>
    </div>

    {{-- Stats Overview --}}
    <div class="row g-3 mb-4">
        <div class="col-md-2">
            <div class="card border-0 shadow-sm rounded-4 h-100">
                <div class="card-body p-3 text-center">
                    <div class="bg-primary bg-opacity-10 p-2 rounded-3 d-inline-block mb-2">
                        <i class="bi bi-box-seam fs-4 text-primary"></i>
                    </div>
                    <h4 class="fw-bold mb-0">{{ $stats['total'] }}</h4>
                    <small class="text-muted">Total Laundries</small>
                </div>
            </div>
        </div>
        <div class="col-md-2">
            <div class="card border-0 shadow-sm rounded-4 h-100">
                <div class="card-body p-3 text-center">
                    <div class="bg-warning bg-opacity-10 p-2 rounded-3 d-inline-block mb-2">
                        <i class="bi bi-clock fs-4 text-warning"></i>
                    </div>
                    <h4 class="fw-bold mb-0">{{ $stats['pending'] }}</h4>
                    <small class="text-muted">Pending</small>
                </div>
            </div>
        </div>
        <div class="col-md-2">
            <div class="card border-0 shadow-sm rounded-4 h-100">
                <div class="card-body p-3 text-center">
                    <div class="bg-info bg-opacity-10 p-2 rounded-3 d-inline-block mb-2">
                        <i class="bi bi-arrow-repeat fs-4 text-info"></i>
                    </div>
                    <h4 class="fw-bold mb-0">{{ $stats['processing'] }}</h4>
                    <small class="text-muted">Processing</small>
                </div>
            </div>
        </div>
        <div class="col-md-2">
            <div class="card border-0 shadow-sm rounded-4 h-100">
                <div class="card-body p-3 text-center">
                    <div class="bg-success bg-opacity-10 p-2 rounded-3 d-inline-block mb-2">
                        <i class="bi bi-check-circle fs-4 text-success"></i>
                    </div>
                    <h4 class="fw-bold mb-0">{{ $stats['ready'] }}</h4>
                    <small class="text-muted">Ready</small>
                </div>
            </div>
        </div>
        <div class="col-md-2">
            <div class="card border-0 shadow-sm rounded-4 h-100">
                <div class="card-body p-3 text-center">
                    <div class="bg-dark bg-opacity-10 p-2 rounded-3 d-inline-block mb-2">
                        <i class="bi bi-check-all fs-4 text-dark"></i>
                    </div>
                    <h4 class="fw-bold mb-0">{{ $stats['completed'] }}</h4>
                    <small class="text-muted">Completed</small>
                </div>
            </div>
        </div>
        <div class="col-md-2">
            <div class="card border-0 shadow-sm rounded-4 h-100 border-start border-success border-4">
                <div class="card-body p-3 text-center">
                    <div class="bg-success bg-opacity-10 p-2 rounded-3 d-inline-block mb-2">
                        <i class="bi bi-cash-stack fs-4 text-success"></i>
                    </div>
                    <h5 class="fw-bold mb-0 small">₱{{ number_format($stats['total_revenue'], 0) }}</h5>
                    <small class="text-muted">Revenue</small>
                </div>
            </div>
        </div>
    </div>

    {{-- Filters --}}
    <div class="card border-0 shadow-sm rounded-4 mb-4">
        <div class="card-body p-3">
            <form method="GET" class="row g-2 align-items-center">
                <div class="col-md-2">
                    <input type="text" name="search" class="form-control form-control-sm"
                        placeholder="Search..." value="{{ request('search') }}">
                </div>
                <div class="col-md-2">
                    <select name="status" class="form-select form-select-sm" onchange="this.form.submit()">
                        <option value="">All Status</option>
                        <option value="received" {{ request('status') === 'received' ? 'selected' : '' }}>Received</option>
                        <option value="processing" {{ request('status') === 'processing' ? 'selected' : '' }}>Processing</option>
                        <option value="ready" {{ request('status') === 'ready' ? 'selected' : '' }}>Ready</option>
                        <option value="paid" {{ request('status') === 'paid' ? 'selected' : '' }}>Paid</option>
                        <option value="completed" {{ request('status') === 'completed' ? 'selected' : '' }}>Completed</option>
                        <option value="cancelled" {{ request('status') === 'cancelled' ? 'selected' : '' }}>Cancelled</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <select name="branch_id" class="form-select form-select-sm" onchange="this.form.submit()">
                        <option value="">All Branches</option>
                        @foreach($branches as $branch)
                            <option value="{{ $branch->id }}" {{ request('branch_id') == $branch->id ? 'selected' : '' }}>
                                {{ $branch->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <select name="service_id" class="form-select form-select-sm" onchange="this.form.submit()">
                        <option value="">All Services</option>
                        @foreach($services as $service)
                            <option value="{{ $service->id }}" {{ request('service_id') == $service->id ? 'selected' : '' }}>
                                {{ $service->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <input type="date" name="date_from" class="form-control form-control-sm"
                        value="{{ request('date_from') }}" onchange="this.form.submit()">
                </div>
                <div class="col-md-2">
                    <a href="{{ route('admin.laundries.index') }}" class="btn btn-sm btn-light border w-100">Clear</a>
                </div>
            </form>
        </div>
    </div>

    {{-- Laundries Table --}}
    <div class="card border-0 shadow-sm rounded-4">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th class="px-4 py-3">Tracking #</th>
                            <th class="px-4 py-3">Customer</th>
                            <th class="px-4 py-3">Service</th>
                            <th class="px-4 py-3">Branch</th>
                            <th class="px-4 py-3">Weight / Loads</th>
                            <th class="px-4 py-3">Amount</th>
                            <th class="px-4 py-3">Status</th>
                            <th class="px-4 py-3">Date</th>
                            <th class="px-4 py-3 text-center">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($laundries as $laundry)
                        <tr>
                            <td class="px-4 py-3">
                                <strong class="text-primary">{{ $laundry->tracking_number }}</strong>
                                @if($laundry->pickupRequest)
                                    <br><small class="text-muted">
                                        <i class="bi bi-truck me-1"></i>Pickup #{{ $laundry->pickupRequest->id }}
                                    </small>
                                @endif
                            </td>
                            <td class="px-4 py-3">
                                <div class="fw-semibold">{{ $laundry->customer->name ?? 'N/A' }}</div>
                                <small class="text-muted">{{ $laundry->customer->phone ?? '' }}</small>
                            </td>
                            <td class="px-4 py-3">
                                {{ $laundry->service->name ?? 'N/A' }}
                                @if($laundry->addons_total > 0)
                                    <br><small class="text-success">+₱{{ number_format($laundry->addons_total, 2) }} add-ons</small>
                                @endif
                                @if($laundry->promotion)
                                    <br><small class="text-info"><i class="bi bi-tag me-1"></i>{{ $laundry->promotion->name }}</small>
                                @endif
                            </td>
                            <td class="px-4 py-3">
                                <span class="badge bg-light text-dark border">
                                    {{ $laundry->branch->name ?? 'N/A' }}
                                </span>
                            </td>
                            <td class="px-4 py-3">
                                @if($laundry->weight > 0)
                                    <span>{{ number_format($laundry->weight, 2) }} kg</span><br>
                                @endif
                                @if($laundry->number_of_loads)
                                    <small class="text-muted">
                                        {{ $laundry->number_of_loads }}
                                        {{ ($laundry->service?->pricing_type === 'per_piece') ? 'pc(s)' : 'load(s)' }}
                                    </small>
                                @endif
                                @if(!$laundry->weight && !$laundry->number_of_loads)
                                    <span class="text-muted">—</span>
                                @endif
                            </td>
                            <td class="px-4 py-3">
                                <strong>₱{{ number_format($laundry->total_amount, 2) }}</strong>
                                @if($laundry->discount_amount > 0)
                                    <br><small class="text-success">-₱{{ number_format($laundry->discount_amount, 2) }} disc.</small>
                                @endif
                            </td>
                            <td class="px-4 py-3">
                                @php
                                    $statusColors = [
                                        'completed' => 'success',
                                        'ready' => 'info',
                                        'processing' => 'warning',
                                        'paid' => 'primary',
                                        'cancelled' => 'danger',
                                        'received' => 'secondary',
                                        'pending' => 'warning'
                                    ];
                                    $color = $statusColors[$laundry->status] ?? 'secondary';
                                @endphp
                                <span class="badge bg-{{ $color }}">{{ ucfirst($laundry->status) }}</span>
                            </td>
                            <td class="px-4 py-3">
                                <small>{{ $laundry->created_at->format('M d, Y') }}</small>
                                <br><small class="text-muted">{{ $laundry->created_at->format('h:i A') }}</small>
                            </td>
                            <td class="px-4 py-3 text-center">
                                <a href="{{ route('admin.laundries.show', $laundry) }}" class="btn btn-sm btn-outline-primary">
                                    <i class="bi bi-eye"></i>
                                </a>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="9" class="text-center py-5">
                                <i class="bi bi-inbox" style="font-size: 3rem; opacity: 0.2;"></i>
                                <p class="text-muted mt-2 mb-3">
                                    @if(request()->anyFilled(['search', 'status', 'branch_id', 'service_id', 'date_from']))
                                        No laundries match your filters
                                        <br>
                                        <a href="{{ route('admin.laundries.index') }}" class="btn btn-link">Clear all filters</a>
                                    @else
                                        No laundries found
                                    @endif
                                </p>
                                <a href="{{ route('admin.laundries.create') }}" class="btn btn-primary btn-sm">
                                    <i class="bi bi-plus-circle me-1"></i>Create First Laundry
                                </a>
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        @if($laundries->hasPages())
        <div class="card-footer bg-white border-top">
            <div class="d-flex justify-content-between align-items-center">
                <small class="text-muted">
                    Showing {{ $laundries->firstItem() }}–{{ $laundries->lastItem() }}
                    of {{ $laundries->total() }} laundries
                </small>
                {{ $laundries->links() }}
            </div>
        </div>
        @endif
    </div>
</div>
@endsection
