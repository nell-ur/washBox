@extends('admin.layouts.app')

@section('page-title', 'Staff Management')

@section('content')
<div class="container-fluid px-4 py-4">
    {{-- Header --}}
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <p class="text-muted small mb-0">Manage your team members and their assignments</p>
        </div>
        <a href="{{ route('admin.staff.create') }}" class="btn btn-primary shadow-sm" style="background: #3D3B6B; border: none;">
            <i class="bi bi-person-plus me-2"></i>Add Staff Member
        </a>
    </div>

    {{-- Stats Overview --}}
    <div class="row g-3 mb-4">
        <div class="col-md-3">
            <div class="card border-0 shadow-sm rounded-4 h-100 border-start border-primary border-4">
                <div class="card-body p-4">
                    <div class="d-flex justify-content-between align-items-start mb-3">
                        <div class="bg-primary bg-opacity-10 p-3 rounded-3">
                            <i class="bi bi-people fs-3 text-primary"></i>
                        </div>
                    </div>
                    <h6 class="text-muted mb-2">Total Staff</h6>
                    <h2 class="fw-bold mb-0">{{ $stats['total'] }}</h2>
                    <small class="text-muted">All employees</small>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card border-0 shadow-sm rounded-4 h-100 border-start border-success border-4">
                <div class="card-body p-4">
                    <div class="d-flex justify-content-between align-items-start mb-3">
                        <div class="bg-success bg-opacity-10 p-3 rounded-3">
                            <i class="bi bi-check-circle fs-3 text-success"></i>
                        </div>
                    </div>
                    <h6 class="text-muted mb-2">Active Staff</h6>
                    <h2 class="fw-bold mb-0">{{ $stats['active'] }}</h2>
                    <small class="text-success fw-semibold">{{ $stats['total'] > 0 ? round(($stats['active']/$stats['total'])*100) : 0 }}% active</small>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card border-0 shadow-sm rounded-4 h-100 border-start border-secondary border-4">
                <div class="card-body p-4">
                    <div class="d-flex justify-content-between align-items-start mb-3">
                        <div class="bg-secondary bg-opacity-10 p-3 rounded-3">
                            <i class="bi bi-pause-circle fs-3 text-secondary"></i>
                        </div>
                    </div>
                    <h6 class="text-muted mb-2">Inactive Staff</h6>
                    <h2 class="fw-bold mb-0">{{ $stats['inactive'] }}</h2>
                    <small class="text-muted">Not working</small>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card border-0 shadow-sm rounded-4 h-100 border-start border-info border-4">
                <div class="card-body p-4">
                    <div class="d-flex justify-content-between align-items-start mb-3">
                        <div class="bg-info bg-opacity-10 p-3 rounded-3">
                            <i class="bi bi-box-seam fs-3 text-info"></i>
                        </div>
                    </div>
                    <h6 class="text-muted mb-2">Total Laundries</h6>
                    <h2 class="fw-bold mb-0">{{ number_format($stats['total_laundries']) }}</h2>
                    <small class="text-muted">Handled by staff</small>
                </div>
            </div>
        </div>
    </div>

    {{-- Filters --}}
    <div class="card border-0 shadow-sm rounded-4 mb-4">
        <div class="card-body p-3">
            <form method="GET" class="row g-2 align-items-center">
                <div class="col-md-3">
                    <div class="input-group input-group-sm">
                        <span class="input-group-text bg-transparent border-end-0 text-muted">
                            <i class="bi bi-search"></i>
                        </span>
                        <input type="text" name="search" class="form-control border-start-0"
                            placeholder="Search by name, email..." value="{{ request('search') }}">
                    </div>
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
                    <select name="status" class="form-select form-select-sm" onchange="this.form.submit()">
                        <option value="">All Statuses</option>
                        <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>Active</option>
                        <option value="inactive" {{ request('status') === 'inactive' ? 'selected' : '' }}>Inactive</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <select name="sort_by" class="form-select form-select-sm" onchange="this.form.submit()">
                        <option value="created_at" {{ request('sort_by') === 'created_at' ? 'selected' : '' }}>Sort by Date</option>
                        <option value="name" {{ request('sort_by') === 'name' ? 'selected' : '' }}>Sort by Name</option>
                        <option value="email" {{ request('sort_by') === 'email' ? 'selected' : '' }}>Sort by Email</option>
                    </select>
                </div>
                <div class="col-md-2 text-end">
                    <a href="{{ route('admin.staff.index') }}" class="btn btn-sm btn-light border text-secondary w-100">
                        Clear Filters
                    </a>
                </div>
            </form>
        </div>
    </div>

    {{-- Staff Cards --}}
    <div class="row g-4 mb-4">
        @forelse($staff as $member)
        <div class="col-lg-3 col-md-4 col-sm-6">
            <div class="card border-0 shadow-sm rounded-4 h-100 staff-card">
                {{-- Photo --}}
                <div class="position-relative text-center pt-4">
                    @if($member->profile_photo_path)
                        <img src="{{ asset('storage/' . $member->profile_photo_path) }}"
                            class="rounded-circle border border-3"
                            style="width: 100px; height: 100px; object-fit: cover; border-color: {{ $member->is_active ? '#10B981' : '#6B7280' }} !important;">
                    @else
                        <div class="rounded-circle border border-3 mx-auto d-flex align-items-center justify-content-center"
                            style="width: 100px; height: 100px; background: linear-gradient(135deg, #3D3B6B 0%, #6366F1 100%); border-color: {{ $member->is_active ? '#10B981' : '#6B7280' }} !important;">
                            <i class="bi bi-person text-white" style="font-size: 2.5rem;"></i>
                        </div>
                    @endif

                    {{-- Status Badge --}}
                    <span class="badge position-absolute top-0 end-0 m-3"
                        style="background: {{ $member->is_active ? '#10B981' : '#6B7280' }}; font-size: 0.7rem;">
                        <i class="bi bi-{{ $member->is_active ? 'check-circle' : 'pause-circle' }} me-1"></i>
                        {{ $member->is_active ? 'ACTIVE' : 'INACTIVE' }}
                    </span>
                </div>

                {{-- Info --}}
                <div class="card-body p-4 text-center">
                    <h6 class="fw-bold mb-1">{{ $member->name }}</h6>
                    <p class="text-muted small mb-2">{{ $member->position ?? 'Staff Member' }}</p>

                    @if($member->branch)
                        <span class="badge bg-light text-dark border mb-3">
                            <i class="bi bi-building me-1"></i>{{ $member->branch->name }}
                        </span>
                    @endif

                    {{-- Contact --}}
                    <div class="text-start mb-3 pb-3 border-bottom">
                        <div class="d-flex align-items-center text-muted small mb-1">
                            <i class="bi bi-envelope me-2" style="color: #3D3B6B;"></i>
                            <span class="text-truncate">{{ Str::limit($member->email, 25) }}</span>
                        </div>
                        @if($member->phone)
                        <div class="d-flex align-items-center text-muted small">
                            <i class="bi bi-telephone me-2" style="color: #3D3B6B;"></i>
                            <span>{{ $member->phone }}</span>
                        </div>
                        @endif
                    </div>

                    {{-- Stats --}}
                    <div class="row g-2 mb-3">
                        <div class="col-6">
                            <div class="bg-light rounded p-2">
                                <div class="fw-bold text-dark">{{ $member->laundries_count }}</div>
                                <small class="text-muted">Laundries</small>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="bg-light rounded p-2">
                                <div class="fw-bold text-primary">
                                    @if($member->hire_date)
                                        {{ \Carbon\Carbon::parse($member->hire_date)->diffInMonths(now()) }}m
                                    @else
                                        N/A
                                    @endif
                                </div>
                                <small class="text-muted">Tenure</small>
                            </div>
                        </div>
                    </div>

                    {{-- Actions --}}
                    <div class="d-flex gap-2">
                        <a href="{{ route('admin.staff.show', $member) }}"
                            class="btn btn-outline-secondary btn-sm flex-fill">
                            <i class="bi bi-eye"></i> View
                        </a>
                        <a href="{{ route('admin.staff.edit', $member) }}"
                            class="btn btn-sm flex-fill text-white" style="background: #3D3B6B;">
                            <i class="bi bi-pencil"></i> Edit
                        </a>
                    </div>
                </div>
            </div>
        </div>
        @empty
        <div class="col-12">
            <div class="card border-0 shadow-sm rounded-4">
                <div class="card-body p-5 text-center">
                    <i class="bi bi-people" style="font-size: 4rem; opacity: 0.2;"></i>
                    <h5 class="fw-bold mt-3">No Staff Members Found</h5>
                    <p class="text-muted mb-3">Start by adding your first staff member</p>
                    <a href="{{ route('admin.staff.create') }}" class="btn btn-primary" style="background: #3D3B6B; border: none;">
                        <i class="bi bi-person-plus me-2"></i>Add Staff Member
                    </a>
                </div>
            </div>
        </div>
        @endforelse
    </div>

    {{-- Pagination --}}
    @if($staff->hasPages())
        <div class="d-flex justify-content-center">
            {{ $staff->links() }}
        </div>
    @endif
</div>

@push('styles')
<style>
    .staff-card {
        transition: transform 0.2s, box-shadow 0.2s;
    }

    .staff-card:hover {
        transform: translateY(-4px);
        box-shadow: 0 8px 16px rgba(0,0,0,0.1) !important;
    }

    .bg-light:hover {
        background-color: #E5E7EB !important;
    }
</style>
@endpush
@endsection
