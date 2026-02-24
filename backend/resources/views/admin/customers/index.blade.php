@extends('admin.layouts.app')

@section('page-title', 'Customers Management')

@push('styles')
    <link rel="stylesheet" href="{{ asset('assets/css/customers.css') }}">
@endpush

@section('content')
<div class="container-fluid px-4 py-4">
    {{-- Header --}}
    <div class="d-flex justify-content-between align-items-center mb-4 page-header">
        <div>
            <p class="text-muted mb-0">Manage walk-in and mobile app registered customers across all branches.</p>
        </div>
        <a href="{{ route('admin.customers.create') }}" class="btn btn-primary shadow-sm px-4">
            <i class="bi bi-person-plus-fill me-2"></i>Create New Customer
        </a>
    </div>

    {{-- Stats Cards --}}
    <div class="stats-grid">
        <div class="stat-card">
            <div class="d-flex align-items-center">
                <div class="stat-icon primary">
                    <i class="bi bi-people"></i>
                </div>
                <div class="flex-grow-1">
                    <div class="stat-label">Total Customers</div>
                    <div class="stat-value">{{ number_format($stats['total']) }}</div>
                    <div class="stat-trend up">
                        <i class="bi bi-arrow-up"></i> +{{ number_format($stats['new_today']) }} today
                    </div>
                </div>
            </div>
        </div>

        <div class="stat-card">
            <div class="d-flex align-items-center">
                <div class="stat-icon secondary">
                    <i class="bi bi-person"></i>
                </div>
                <div class="flex-grow-1">
                    <div class="stat-label">Walk-in Customers</div>
                    <div class="stat-value">{{ number_format($stats['walk_in']) }}</div>
                    <div class="stat-trend">
                        {{ number_format(($stats['walk_in'] / max($stats['total'], 1)) * 100, 1) }}% of total
                    </div>
                </div>
            </div>
        </div>

        <div class="stat-card">
            <div class="d-flex align-items-center">
                <div class="stat-icon success">
                    <i class="bi bi-person-check"></i>
                </div>
                <div class="flex-grow-1">
                    <div class="stat-label">Mobile App Users</div>
                    <div class="stat-value">{{ number_format($stats['self_registered']) }}</div>
                    <div class="stat-trend">
                        {{ number_format(($stats['self_registered'] / max($stats['total'], 1)) * 100, 1) }}% of total
                    </div>
                </div>
            </div>
        </div>

        <div class="stat-card">
            <div class="d-flex align-items-center">
                <div class="stat-icon warning">
                    <i class="bi bi-person-plus"></i>
                </div>
                <div class="flex-grow-1">
                    <div class="stat-label">New Today</div>
                    <div class="stat-value">{{ number_format($stats['new_today']) }}</div>
                    <div class="stat-trend up">
                        <i class="bi bi-graph-up"></i> New registrations
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Filters - FIXED SEARCH BAR --}}
    <div class="filter-card">
        <form method="GET" class="row g-3 align-items-end">
            <div class="col-lg-2 col-md-4">
                <label class="filter-label">Registration Type</label>
                <select name="registration_type" class="filter-select" onchange="this.form.submit()">
                    <option value="">All Types</option>
                    <option value="walk_in" {{ request('registration_type') === 'walk_in' ? 'selected' : '' }}>Walk-in</option>
                    <option value="self_registered" {{ request('registration_type') === 'self_registered' ? 'selected' : '' }}>Mobile App</option>
                </select>
            </div>

            <div class="col-lg-2 col-md-4">
                <label class="filter-label">Branch</label>
                <select name="branch_id" class="filter-select" onchange="this.form.submit()">
                    <option value="">All Branches</option>
                    @foreach($branches as $branch)
                        <option value="{{ $branch->id }}" {{ request('branch_id') == $branch->id ? 'selected' : '' }}>
                            {{ $branch->name }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="col-lg-2 col-md-4">
                <label class="filter-label">Status</label>
                <select name="status" class="filter-select" onchange="this.form.submit()">
                    <option value="">All Status</option>
                    <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>Active</option>
                    <option value="inactive" {{ request('status') === 'inactive' ? 'selected' : '' }}>Inactive</option>
                </select>
            </div>

            <div class="col-lg-4 col-md-8">
                <label class="filter-label">Search</label>
                <div class="d-flex gap-2">
                    <div class="flex-grow-1 position-relative">
                        <i class="bi bi-search position-absolute top-50 start-0 translate-middle-y ms-3 text-muted" style="z-index: 10;"></i>
                        <input type="text" 
                               name="search" 
                               class="filter-input" 
                               placeholder="Search by name, phone, or email..." 
                               value="{{ request('search') }}"
                               style="padding-left: 2.5rem;">
                    </div>
                    <button class="filter-btn filter-btn-primary" type="submit">
                        <i class="bi bi-search"></i> Search
                    </button>
                    @if(request()->anyFilled(['search', 'branch_id', 'status', 'registration_type']))
                        <a href="{{ route('admin.customers.index') }}" class="filter-btn filter-btn-clear">
                            <i class="bi bi-x-circle"></i> Clear
                        </a>
                    @endif
                </div>
            </div>
        </form>

        {{-- Active Filters Display --}}
        @if(request()->anyFilled(['search', 'branch_id', 'status', 'registration_type']))
            <div class="mt-3 pt-3 border-top">
                <div class="d-flex align-items-center gap-2 flex-wrap">
                    <span class="text-muted small">Active filters:</span>
                    @if(request('search'))
                        <span class="filter-badge">
                            <i class="bi bi-search me-1"></i> "{{ request('search') }}"
                            <a href="{{ request()->fullUrlWithoutQuery(['search']) }}" class="remove-filter">
                                <i class="bi bi-x"></i>
                            </a>
                        </span>
                    @endif
                    @if(request('registration_type'))
                        <span class="filter-badge">
                            <i class="bi bi-person me-1"></i> {{ request('registration_type') === 'walk_in' ? 'Walk-in' : 'Mobile App' }}
                            <a href="{{ request()->fullUrlWithoutQuery(['registration_type']) }}" class="remove-filter">
                                <i class="bi bi-x"></i>
                            </a>
                        </span>
                    @endif
                    @if(request('branch_id'))
                        @php $branch = $branches->firstWhere('id', request('branch_id')); @endphp
                        @if($branch)
                            <span class="filter-badge">
                                <i class="bi bi-geo-alt me-1"></i> {{ $branch->name }}
                                <a href="{{ request()->fullUrlWithoutQuery(['branch_id']) }}" class="remove-filter">
                                    <i class="bi bi-x"></i>
                                </a>
                            </span>
                        @endif
                    @endif
                    @if(request('status'))
                        <span class="filter-badge">
                            <i class="bi bi-{{ request('status') === 'active' ? 'check-circle' : 'x-circle' }} me-1"></i> {{ ucfirst(request('status')) }}
                            <a href="{{ request()->fullUrlWithoutQuery(['status']) }}" class="remove-filter">
                                <i class="bi bi-x"></i>
                            </a>
                        </span>
                    @endif
                    <a href="{{ route('admin.customers.index') }}" class="btn btn-sm btn-link text-danger p-0 ms-2">
                        Clear all
                    </a>
                </div>
            </div>
        @endif
    </div>

    {{-- Customers Table --}}
    <div class="table-card">
        <div class="table-header">
            <h6>
                <i class="bi bi-people"></i> Customers List
            </h6>
            <span class="badge">{{ $customers->total() }} total customers</span>
        </div>

        <div class="table-responsive">
            <table class="customers-table">
                <thead>
                    <tr>
                        <th class="ps-4">Customer</th>
                        <th>Contact</th>
                        <th>Registration</th>
                        <th>Branch</th>
                        <th class="text-center">Laundries</th>
                        <th>Total Spent</th>
                        <th>Status</th>
                        <th class="text-end pe-4">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($customers as $customer)
                    <tr>
                        <td class="ps-4">
                            <div class="customer-info">
                                @if($customer->profile_photo_url)
                                    <div class="customer-avatar">
                                        <img src="{{ $customer->profile_photo_url }}" alt="{{ $customer->name }}">
                                    </div>
                                @else
                                    <div class="customer-avatar">
                                        {{ strtoupper(substr($customer->name, 0, 1)) }}
                                    </div>
                                @endif
                                <div class="customer-details">
                                    <div class="customer-name">
                                        {{ $customer->name }}
                                    </div>
                                    <span class="customer-id">#CUST-{{ str_pad($customer->id, 4, '0', STR_PAD_LEFT) }}</span>
                                </div>
                            </div>
                        </td>
                        <td>
                            <div class="contact-info">
                                <div class="contact-item">
                                    <i class="bi bi-telephone"></i>
                                    <span class="phone">{{ $customer->phone }}</span>
                                </div>
                                @if($customer->email)
                                    <div class="contact-item">
                                        <i class="bi bi-envelope"></i>
                                        <span class="email">{{ Str::limit($customer->email, 20) }}</span>
                                    </div>
                                @endif
                            </div>
                        </td>
                        <td>
                            @if($customer->registration_type == 'walk_in')
                                <span class="reg-badge walk-in">
                                    <i class="bi bi-person"></i> Walk-in
                                </span>
                            @else
                                <span class="reg-badge mobile">
                                    <i class="bi bi-phone"></i> Mobile App
                                </span>
                            @endif
                        </td>
                        <td>
                            @if($customer->preferredBranch)
                                <span class="branch-badge">
                                    <i class="bi bi-geo-alt"></i> {{ $customer->preferredBranch->name }}
                                </span>
                            @else
                                <span class="text-muted small">—</span>
                            @endif
                        </td>
                        <td class="text-center">
                            <span class="stats-badge">
                                {{ $customer->laundries_count ?? $customer->laundries()->count() }}
                            </span>
                        </td>
                        <td>
                            <span class="amount">₱{{ number_format($customer->getTotalSpent(), 2) }}</span>
                        </td>
                        <td>
                            @if($customer->is_active)
                                <span class="status-badge active">
                                    <i class="bi bi-check-circle"></i> Active
                                </span>
                            @else
                                <span class="status-badge inactive">
                                    <i class="bi bi-x-circle"></i> Inactive
                                </span>
                            @endif
                        </td>
                        <td class="text-end pe-4">
                            <div class="action-buttons">
                                <a href="{{ route('admin.customers.show', $customer) }}" class="action-btn view" title="View Details">
                                    <i class="bi bi-eye"></i>
                                </a>
                                <a href="{{ route('admin.customers.edit', $customer) }}" class="action-btn edit" title="Edit Customer">
                                    <i class="bi bi-pencil"></i>
                                </a>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="8" class="p-0">
                            <div class="empty-state">
                                <i class="bi bi-people"></i>
                                <h5>No customers found</h5>
                                <p>No customers match your current filters. Try adjusting your search criteria.</p>
                                @if(request()->anyFilled(['search', 'branch_id', 'status', 'registration_type']))
                                    <a href="{{ route('admin.customers.index') }}" class="btn btn-outline-primary">
                                        <i class="bi bi-arrow-counterclockwise me-1"></i> Clear all filters
                                    </a>
                                @else
                                    <a href="{{ route('admin.customers.create') }}" class="btn btn-primary">
                                        <i class="bi bi-person-plus me-1"></i> Create First Customer
                                    </a>
                                @endif
                            </div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Pagination --}}
        @if($customers->hasPages())
            <div class="pagination-wrapper">
                <div class="pagination-info">
                    Showing {{ $customers->firstItem() }} to {{ $customers->lastItem() }} of {{ $customers->total() }} customers
                </div>
                <div>
                    {{ $customers->links() }}
                </div>
            </div>
        @endif
    </div>
</div>
@endsection