@extends('admin.layouts.app')

@section('page-title', 'Staff Profile - ' . $staff->name)

@section('content')
<div class="container-fluid px-4 py-4">
    {{-- Header --}}
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <p class="text-muted small mb-0">{{ $staff->position ?? 'Staff Member' }} • {{ $staff->branch->name ?? 'No Branch' }}</p>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('admin.staff.edit', $staff) }}" class="btn btn-primary shadow-sm" style="background: #3D3B6B; border: none;">
                <i class="bi bi-pencil me-2"></i>Edit Profile
            </a>
            <a href="{{ route('admin.staff.index') }}" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left me-2"></i>Back to Staff
            </a>
        </div>
    </div>

    <div class="row g-4">
        {{-- Left Column - Profile Info --}}
        <div class="col-lg-4">
            {{-- Profile Card --}}
            <div class="card border-0 shadow-sm rounded-4 mb-4">
                <div class="card-body p-4 text-center">
                    {{-- Photo --}}
                    <div class="mb-3">
                        @if($staff->profile_photo_path)
                            <img src="{{ asset('storage/' . $staff->profile_photo_path) }}"
                                class="rounded-circle border border-3"
                                style="width: 150px; height: 150px; object-fit: cover; border-color: {{ $staff->is_active ? '#10B981' : '#6B7280' }} !important;">
                        @else
                            <div class="rounded-circle border border-3 mx-auto d-flex align-items-center justify-content-center"
                                style="width: 150px; height: 150px; background: linear-gradient(135deg, #3D3B6B 0%, #6366F1 100%); border-color: {{ $staff->is_active ? '#10B981' : '#6B7280' }} !important;">
                                <i class="bi bi-person text-white" style="font-size: 4rem;"></i>
                            </div>
                        @endif
                    </div>

                    {{-- Name & Position --}}
                    <h5 class="fw-bold mb-1">{{ $staff->name }}</h5>
                    <p class="text-muted mb-3">{{ $staff->position ?? 'Staff Member' }}</p>

                    {{-- Status Badge --}}
                    <span class="badge mb-3" style="background: {{ $staff->is_active ? '#10B981' : '#6B7280' }}; font-size: 0.9rem; padding: 0.5rem 1rem;">
                        <i class="bi bi-{{ $staff->is_active ? 'check-circle' : 'pause-circle' }} me-1"></i>
                        {{ $staff->is_active ? 'ACTIVE' : 'INACTIVE' }}
                    </span>

                    {{-- Branch Badge --}}
                    @if($staff->branch)
                        <div class="mb-3">
                            <span class="badge bg-light text-dark border">
                                <i class="bi bi-building me-1"></i>{{ $staff->branch->name }}
                            </span>
                        </div>
                    @endif

                    {{-- Quick Actions --}}
                    <div class="d-flex gap-2 mb-3">
                        <a href="{{ route('admin.staff.edit', $staff) }}" class="btn btn-outline-primary btn-sm flex-fill">
                            <i class="bi bi-pencil"></i> Edit
                        </a>
                        <form action="{{ route('admin.staff.toggle-status', $staff) }}" method="POST" class="flex-fill">
                            @csrf
                            <button type="submit" class="btn btn-outline-{{ $staff->is_active ? 'warning' : 'success' }} btn-sm w-100">
                                <i class="bi bi-{{ $staff->is_active ? 'pause' : 'play' }}-circle"></i>
                                {{ $staff->is_active ? 'Deactivate' : 'Activate' }}
                            </button>
                        </form>
                    </div>
                </div>
            </div>

            {{-- Contact Information --}}
            <div class="card border-0 shadow-sm rounded-4 mb-4">
                <div class="card-header bg-white border-bottom py-3">
                    <h6 class="mb-0 fw-bold text-dark">
                        <i class="bi bi-person-lines-fill me-2" style="color: #3D3B6B;"></i>
                        Contact Information
                    </h6>
                </div>
                <div class="card-body p-4">
                    <div class="mb-3 pb-3 border-bottom">
                        <small class="text-muted d-block mb-1">Email</small>
                        <strong class="d-block">{{ $staff->email }}</strong>
                    </div>

                    @if($staff->phone)
                    <div class="mb-3 pb-3 border-bottom">
                        <small class="text-muted d-block mb-1">Phone</small>
                        <strong class="d-block">{{ $staff->phone }}</strong>
                    </div>
                    @endif

                    @if($staff->address)
                    <div class="mb-3 pb-3 border-bottom">
                        <small class="text-muted d-block mb-1">Address</small>
                        <strong class="d-block">{{ $staff->address }}</strong>
                    </div>
                    @endif

                    @if($staff->employee_id)
                    <div>
                        <small class="text-muted d-block mb-1">Employee ID</small>
                        <span class="badge bg-light text-dark border">{{ $staff->employee_id }}</span>
                    </div>
                    @endif
                </div>
            </div>

            {{-- Emergency Contact --}}
            @if($staff->emergency_contact || $staff->emergency_phone)
            <div class="card border-0 shadow-sm rounded-4 mb-4">
                <div class="card-header bg-white border-bottom py-3">
                    <h6 class="mb-0 fw-bold text-dark">
                        <i class="bi bi-telephone-plus me-2" style="color: #3D3B6B;"></i>
                        Emergency Contact
                    </h6>
                </div>
                <div class="card-body p-4">
                    @if($staff->emergency_contact)
                    <div class="mb-2">
                        <small class="text-muted d-block mb-1">Contact Name</small>
                        <strong>{{ $staff->emergency_contact }}</strong>
                    </div>
                    @endif

                    @if($staff->emergency_phone)
                    <div>
                        <small class="text-muted d-block mb-1">Contact Phone</small>
                        <strong>{{ $staff->emergency_phone }}</strong>
                    </div>
                    @endif
                </div>
            </div>
            @endif

            {{-- Employment Details --}}
            <div class="card border-0 shadow-sm rounded-4">
                <div class="card-header bg-white border-bottom py-3">
                    <h6 class="mb-0 fw-bold text-dark">
                        <i class="bi bi-briefcase me-2" style="color: #3D3B6B;"></i>
                        Employment Details
                    </h6>
                </div>
                <div class="card-body p-4">
                    <div class="mb-3">
                        <small class="text-muted d-block mb-1">Hire Date</small>
                        <strong>{{ $staff->hire_date ? $staff->hire_date->format('F d, Y') : 'N/A' }}</strong>
                    </div>

                    @if($staff->hire_date)
                    <div class="mb-3">
                        <small class="text-muted d-block mb-1">Tenure</small>
                        <strong>{{ $staff->hire_date->diffForHumans(null, true) }}</strong>
                    </div>
                    @endif

                    <div>
                        <small class="text-muted d-block mb-1">Member Since</small>
                        <strong>{{ $staff->created_at->format('F d, Y') }}</strong>
                    </div>
                </div>
            </div>
        </div>

        {{-- Right Column - Stats & Orders --}}
        <div class="col-lg-8">
            {{-- Statistics --}}
            <div class="row g-3 mb-4">
                <div class="col-md-3">
                    <div class="card border-0 shadow-sm rounded-4 h-100">
                        <div class="card-body p-3 text-center">
                            <div class="bg-primary bg-opacity-10 p-2 rounded-3 d-inline-block mb-2">
                                <i class="bi bi-box-seam fs-4 text-primary"></i>
                            </div>
                            <h4 class="fw-bold mb-0">{{ $stats['total_laundries'] }}</h4>
                            <small class="text-muted">Total Laundries</small>
                        </div>
                    </div>
                </div>

                <div class="col-md-3">
                    <div class="card border-0 shadow-sm rounded-4 h-100">
                        <div class="card-body p-3 text-center">
                            <div class="bg-success bg-opacity-10 p-2 rounded-3 d-inline-block mb-2">
                                <i class="bi bi-check-circle fs-4 text-success"></i>
                            </div>
                            <h4 class="fw-bold mb-0">{{ $stats['completed_laundries'] }}</h4>
                            <small class="text-muted">Completed</small>
                        </div>
                    </div>
                </div>

                <div class="col-md-3">
                    <div class="card border-0 shadow-sm rounded-4 h-100">
                        <div class="card-body p-3 text-center">
                            <div class="bg-warning bg-opacity-10 p-2 rounded-3 d-inline-block mb-2">
                                <i class="bi bi-clock-history fs-4 text-warning"></i>
                            </div>
                            <h4 class="fw-bold mb-0">{{ $stats['pending_laundries'] }}</h4>
                            <small class="text-muted">Pending</small>
                        </div>
                    </div>
                </div>

                <div class="col-md-3">
                    <div class="card border-0 shadow-sm rounded-4 h-100">
                        <div class="card-body p-3 text-center">
                            <div class="bg-info bg-opacity-10 p-2 rounded-3 d-inline-block mb-2">
                                <i class="bi bi-cash-stack fs-4 text-info"></i>
                            </div>
                            <h4 class="fw-bold mb-0">₱{{ number_format($stats['total_revenue'], 0) }}</h4>
                            <small class="text-muted">Revenue</small>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Performance Summary --}}
            <div class="card border-0 shadow-sm rounded-4 mb-4">
                <div class="card-header bg-white border-bottom py-3">
                    <h6 class="mb-0 fw-bold text-dark">
                        <i class="bi bi-graph-up me-2" style="color: #3D3B6B;"></i>
                        Performance Summary
                    </h6>
                </div>
                <div class="card-body p-4">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <span class="text-muted">Completion Rate</span>
                                <strong>{{ $stats['total_laundries'] > 0 ? round(($stats['completed_laundries']/$stats['total_laundries'])*100) : 0 }}%</strong>
                            </div>
                            <div class="progress" style="height: 8px;">
                                <div class="progress-bar bg-success" style="width: {{ $stats['total_laundries'] > 0 ? ($stats['completed_laundries']/$stats['total_laundries'])*100 : 0 }}%"></div>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <span class="text-muted">Average Laundry Value</span>
                                <strong>₱{{ number_format($stats['avg_laundry_value'], 2) }}</strong>
                            </div>
                            <small class="text-muted">Per completed laundry</small>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Recent Laundries --}}
            <div class="card border-0 shadow-sm rounded-4">
                <div class="card-header bg-white border-bottom py-3">
                    <div class="d-flex justify-content-between align-items-center">
                        <h6 class="mb-0 fw-bold text-dark">
                            <i class="bi bi-clock-history me-2" style="color: #3D3B6B;"></i>
                            Recent Laundries
                        </h6>
                        <a href="{{ route('admin.laundries.index', ['staff_id' => $staff->id]) }}" class="btn btn-sm btn-outline-secondary">
                            View All
                        </a>
                    </div>
                </div>
                <div class="card-body p-0">
                    @forelse($recent_laundries as $laundry)
                    <div class="p-3 border-bottom {{ $loop->last ? 'border-0' : '' }}">
                        <div class="d-flex justify-content-between align-items-start">
                            <div>
                                <strong class="d-block mb-1">#{{ $laundry->laundry_number ?? $laundry->id }}</strong>
                                <small class="text-muted">
                                    <i class="bi bi-person me-1"></i>{{ $laundry->customer->name ?? 'N/A' }}
                                </small>
                                @if($laundry->service)
                                    <span class="mx-1">•</span>
                                    <small class="text-muted">{{ $laundry->service->name }}</small>
                                @endif
                            </div>
                            <div class="text-end">
                                <span class="badge bg-{{ $laundry->status === 'completed' ? 'success' : ($laundry->status === 'pending' ? 'warning' : 'primary') }}">
                                    {{ ucfirst($laundry->status) }}
                                </span>
                                <div class="small text-muted mt-1">{{ $laundry->created_at->format('M d, Y') }}</div>
                                <strong class="d-block" style="color: #3D3B6B;">₱{{ number_format($laundry->total_amount, 2) }}</strong>
                            </div>
                        </div>
                    </div>
                    @empty
                    <div class="p-5 text-center">
                        <i class="bi bi-inbox" style="font-size: 3rem; opacity: 0.2;"></i>
                        <p class="text-muted mb-0 mt-2">No laundries yet</p>
                    </div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
