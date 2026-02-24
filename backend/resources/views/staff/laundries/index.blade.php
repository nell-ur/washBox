@extends('staff.layouts.staff')

@section('page-title', 'MY LAUNDRIES')

@push('styles')
    <link rel="stylesheet" href="{{ asset('assets/css/laundry.css') }}">
@endpush

@section('content')
<div class="container-fluid px-4 py-4">

    {{-- Header --}}
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <p class="text-muted small mb-0">Track and manage all laundry orders for your branch</p>
        </div>
        <a href="{{ route('staff.laundries.create') }}"
           class="btn btn-primary shadow-sm"
           style="background: #3D3B6B; border: none;">
            <i class="bi bi-plus-circle me-2"></i>Create New Laundry
        </a>
    </div>

    {{-- Stats Overview --}}
    <div class="row g-3 mb-4">
        {{-- Total --}}
        <div class="col-md-2 col-sm-4">
            <div class="card border-0 shadow-sm rounded-4 h-100">
                <div class="card-body p-3 text-center">
                    <div class="bg-primary bg-opacity-10 p-2 rounded-3 d-inline-block mb-2">
                        <i class="bi bi-box-seam fs-4 text-primary"></i>
                    </div>
                    <h4 class="fw-bold mb-0">{{ $stats['total'] ?? 0 }}</h4>
                    <small class="text-muted">Total</small>
                </div>
            </div>
        </div>

        {{-- Received (was incorrectly labelled "Pending") --}}
        <div class="col-md-2 col-sm-4">
            <div class="card border-0 shadow-sm rounded-4 h-100">
                <div class="card-body p-3 text-center">
                    <div class="bg-warning bg-opacity-10 p-2 rounded-3 d-inline-block mb-2">
                        <i class="bi bi-inbox fs-4 text-warning"></i>
                    </div>
                    <h4 class="fw-bold mb-0">{{ $stats['received'] ?? 0 }}</h4>
                    <small class="text-muted">Received</small>
                </div>
            </div>
        </div>

        {{-- Processing --}}
        <div class="col-md-2 col-sm-4">
            <div class="card border-0 shadow-sm rounded-4 h-100">
                <div class="card-body p-3 text-center">
                    <div class="bg-info bg-opacity-10 p-2 rounded-3 d-inline-block mb-2">
                        <i class="bi bi-arrow-repeat fs-4 text-info"></i>
                    </div>
                    <h4 class="fw-bold mb-0">{{ $stats['processing'] ?? 0 }}</h4>
                    <small class="text-muted">Processing</small>
                </div>
            </div>
        </div>

        {{-- Ready --}}
        <div class="col-md-2 col-sm-4">
            <div class="card border-0 shadow-sm rounded-4 h-100">
                <div class="card-body p-3 text-center">
                    <div class="bg-success bg-opacity-10 p-2 rounded-3 d-inline-block mb-2">
                        <i class="bi bi-check-circle fs-4 text-success"></i>
                    </div>
                    <h4 class="fw-bold mb-0">{{ $stats['ready'] ?? 0 }}</h4>
                    <small class="text-muted">Ready</small>
                </div>
            </div>
        </div>

        {{-- Today --}}
        <div class="col-md-2 col-sm-4">
            <div class="card border-0 shadow-sm rounded-4 h-100">
                <div class="card-body p-3 text-center">
                    <div class="bg-dark bg-opacity-10 p-2 rounded-3 d-inline-block mb-2">
                        <i class="bi bi-calendar-check fs-4 text-dark"></i>
                    </div>
                    <h4 class="fw-bold mb-0">{{ $stats['today_laundries'] ?? 0 }}</h4>
                    <small class="text-muted">Today</small>
                </div>
            </div>
        </div>

        {{-- Revenue --}}
        <div class="col-md-2 col-sm-4">
            <div class="card border-0 shadow-sm rounded-4 h-100 border-start border-success border-4">
                <div class="card-body p-3 text-center">
                    <div class="bg-success bg-opacity-10 p-2 rounded-3 d-inline-block mb-2">
                        <i class="bi bi-cash-stack fs-4 text-success"></i>
                    </div>
                    <h5 class="fw-bold mb-0 small">₱{{ number_format($stats['total_revenue'] ?? 0, 0) }}</h5>
                    <small class="text-muted">Revenue</small>
                </div>
            </div>
        </div>
    </div>

    {{-- Alerts --}}
    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show rounded-3" role="alert">
            <i class="bi bi-check-circle me-2"></i>{{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show rounded-3" role="alert">
            <i class="bi bi-exclamation-triangle me-2"></i>{{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    {{-- Filters --}}
    <div class="card border-0 shadow-sm rounded-4 mb-4">
        <div class="card-body p-3">
            <form method="GET" class="row g-2 align-items-center">
                <div class="col-md-3">
                    <input type="text" name="search" class="form-control form-control-sm"
                        placeholder="Search customer, tracking #..."
                        value="{{ request('search') }}">
                </div>
                <div class="col-md-2">
                    <select name="status" class="form-select form-select-sm" onchange="this.form.submit()">
                        <option value="">All Status</option>
                        @foreach(['received' => 'Received', 'processing' => 'Processing', 'ready' => 'Ready', 'paid' => 'Paid', 'completed' => 'Completed', 'cancelled' => 'Cancelled'] as $val => $label)
                            <option value="{{ $val }}" {{ request('status') === $val ? 'selected' : '' }}>{{ $label }}</option>
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
                <div class="col-md-1">
                    <button type="submit" class="btn btn-sm btn-primary w-100">
                        <i class="bi bi-search"></i>
                    </button>
                </div>
                <div class="col-md-2">
                    <a href="{{ route('staff.laundries.index') }}" class="btn btn-sm btn-light border w-100">
                        <i class="bi bi-x me-1"></i>Clear
                    </a>
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
                                    $badgeMap = [
                                        'completed'  => 'success',
                                        'ready'      => 'info',
                                        'processing' => 'warning',
                                        'paid'       => 'primary',
                                        'cancelled'  => 'danger',
                                        'received'   => 'secondary',
                                    ];
                                    $badge = $badgeMap[$laundry->status] ?? 'secondary';
                                @endphp
                                <span class="badge bg-{{ $badge }}">{{ ucfirst($laundry->status) }}</span>
                            </td>
                            <td class="px-4 py-3">
                                <small>{{ $laundry->created_at->format('M d, Y') }}</small>
                                <br><small class="text-muted">{{ $laundry->created_at->format('h:i A') }}</small>
                            </td>
                            <td class="px-4 py-3 text-center">
                                <a href="{{ route('staff.laundries.show', $laundry) }}"
                                   class="btn btn-sm btn-outline-primary"
                                   title="View Details">
                                    <i class="bi bi-eye"></i>
                                </a>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="8" class="text-center py-5">
                                <i class="bi bi-inbox" style="font-size: 3rem; opacity: 0.2;"></i>
                                <p class="text-muted mt-2 mb-3">No laundries found</p>
                                <a href="{{ route('staff.laundries.create') }}" class="btn btn-primary btn-sm">
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
        <div class="card-footer bg-white border-top rounded-bottom-4">
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