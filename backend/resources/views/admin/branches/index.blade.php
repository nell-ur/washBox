@extends('admin.layouts.app')

@section('title', 'Branches Management')
@section('page-title', 'BRANCH MANAGEMENT')

@section('content')
<div class="container-fluid px-4 py-4">
    {{-- Header --}}
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <p class="text-muted small mb-0">Manage your laundry service locations across Negros Oriental</p>
        </div>
        <div>
            <a href="{{ route('admin.branches.create') }}" class="btn btn-primary shadow-sm" style="background: #3D3B6B; border: none;">
                <i class="bi bi-plus-circle me-2"></i>Add New Branch
            </a>
            <button class="btn btn-outline-primary shadow-sm" data-bs-toggle="modal" data-bs-target="#addBranchModal">
                <i class="bi bi-plus-lg me-2"></i>Quick Add
            </button>
        </div>
    </div>

    {{-- Stats Overview --}}
    <div class="row g-3 mb-4">
        <div class="col-md-3">
            <div class="card border-0 shadow-sm rounded-4 h-100" style="background: linear-gradient(135deg, #3D3B6B 0%, #2D2850 100%);">
                <div class="card-body p-4 text-white">
                    <div class="d-flex justify-content-between align-items-start mb-3">
                        <div class="bg-white bg-opacity-20 p-3 rounded-3">
                            <i class="bi bi-building fs-3"></i>
                        </div>
                        @php
                            $activeBranches = $branches->where('is_active', true)->count();
                            $totalBranches = $branches->count();
                            $activePercentage = $totalBranches > 0 ? round(($activeBranches / $totalBranches) * 100) : 0;
                        @endphp
                        <span class="badge bg-success">{{ $activePercentage }}%</span>
                    </div>
                    <h6 class="mb-2 opacity-75">Active Branches</h6>
                    <h2 class="fw-bold mb-0">{{ $activeBranches }}/{{ $totalBranches }}</h2>
                    <small class="opacity-75">{{ $branches->where('is_active', false)->count() }} inactive</small>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card border-0 shadow-sm rounded-4 h-100 border-start border-primary border-4">
                <div class="card-body p-4">
                    <div class="d-flex justify-content-between align-items-start mb-3">
                        <div class="bg-primary bg-opacity-10 p-3 rounded-3">
                            <i class="bi bi-box-seam fs-3 text-primary"></i>
                        </div>
                    </div>
                    <h6 class="text-muted mb-2">Total Laundries</h6>
                    <h2 class="fw-bold mb-0">{{ number_format($total_laundries) }}</h2>
                    <small class="text-muted">Network-wide</small>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card border-0 shadow-sm rounded-4 h-100 border-start border-success border-4">
                <div class="card-body p-4">
                    <div class="d-flex justify-content-between align-items-start mb-3">
                        <div class="bg-success bg-opacity-10 p-3 rounded-3">
                            <i class="bi bi-cash-stack fs-3 text-success"></i>
                        </div>
                    </div>
                    <h6 class="text-muted mb-2">Total Revenue</h6>
                    <h2 class="fw-bold mb-0">₱{{ number_format($total_revenue, 2) }}</h2>
                    <small class="text-muted">Combined earnings</small>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card border-0 shadow-sm rounded-4 h-100 border-start border-info border-4">
                <div class="card-body p-4">
                    <div class="d-flex justify-content-between align-items-start mb-3">
                        <div class="bg-info bg-opacity-10 p-3 rounded-3">
                            <i class="bi bi-people fs-3 text-info"></i>
                        </div>
                    </div>
                    <h6 class="text-muted mb-2">Total Staff</h6>
                    <h2 class="fw-bold mb-0">{{ $branches->sum('staff_count') }}</h2>
                    <small class="text-muted">All branches</small>
                </div>
            </div>
        </div>
    </div>

    {{-- Branch Cards --}}
    <div class="row g-4 mb-4">
        @forelse($branches as $branch)
        <div class="col-lg-4 col-md-6">
            <div class="card border-0 shadow-sm rounded-4 h-100 branch-card">
                {{-- Branch Image --}}
                <div class="position-relative overflow-hidden" style="height: 200px; border-radius: 1rem 1rem 0 0;">
                    @if($branch->photo_url)
                        <img src="{{ Storage::url($branch->photo_url) }}" class="w-100 h-100" style="object-fit: cover;">
                    @else
                        <div class="w-100 h-100 d-flex align-items-center justify-content-center" style="background: linear-gradient(135deg, #3D3B6B 0%, #6366F1 100%);">
                            <i class="bi bi-building text-white" style="font-size: 4rem; opacity: 0.3;"></i>
                        </div>
                    @endif

                    {{-- Status Badge --}}
                    <span class="badge position-absolute top-0 start-0 m-3 px-3 py-2"
                        style="background: {{ $branch->is_active ? '#10B981' : '#6B7280' }}; font-size: 0.75rem; font-weight: 600;">
                        <i class="bi bi-{{ $branch->is_active ? 'check-circle' : 'pause-circle' }} me-1"></i>
                        {{ $branch->is_active ? 'ACTIVE' : 'INACTIVE' }}
                    </span>

                    {{-- Quick Actions --}}
                    <div class="position-absolute top-0 end-0 m-3">
                        <div class="dropdown">
                            <button class="btn btn-light btn-sm rounded-circle" data-bs-toggle="dropdown" style="width: 36px; height: 36px;">
                                <i class="bi bi-three-dots-vertical"></i>
                            </button>
                            <ul class="dropdown-menu dropdown-menu-end">
                                <li><a class="dropdown-item" href="{{ route('admin.branches.edit', $branch->id) }}">
                                    <i class="bi bi-pencil me-2"></i>Edit Details
                                </a></li>
                                <li><a class="dropdown-item" href="{{ route('admin.branches.show', $branch->id) }}">
                                    <i class="bi bi-eye me-2"></i>View Analytics
                                </a></li>
                                <li><hr class="dropdown-divider"></li>
                                <li>
                                    @if($branch->is_active)
                                    <form action="{{ route('admin.branches.deactivate', $branch->id) }}" method="POST">
                                        @csrf
                                        @method('PATCH')
                                        <button type="submit" class="dropdown-item text-warning">
                                            <i class="bi bi-pause-circle me-2"></i>Deactivate
                                        </button>
                                    </form>
                                    @else
                                    <form action="{{ route('admin.branches.activate', $branch->id) }}" method="POST">
                                        @csrf
                                        @method('PATCH')
                                        <button type="submit" class="dropdown-item text-success">
                                            <i class="bi bi-play-circle me-2"></i>Activate
                                        </button>
                                    </form>
                                    @endif
                                </li>
                                <li>
                                    <form action="{{ route('admin.branches.destroy', $branch->id) }}" method="POST"
                                          onsubmit="return confirm('Are you sure you want to delete this branch? This action cannot be undone.');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="dropdown-item text-danger">
                                            <i class="bi bi-trash me-2"></i>Delete
                                        </button>
                                    </form>
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>

                {{-- Branch Info --}}
                <div class="card-body p-4">
                    <div class="d-flex justify-content-between align-items-start mb-3">
                        <div class="flex-grow-1">
                            <h5 class="fw-bold mb-1">{{ $branch->name }}</h5>
                            <p class="text-muted small mb-0">
                                <i class="bi bi-geo-alt-fill me-1"></i>
                                {{ Str::limit($branch->address, 30) }}, {{ $branch->city }}
                            </p>
                        </div>
                        <span class="badge bg-light text-dark border">
                            {{ $branch->code ?? 'N/A' }}
                        </span>
                    </div>

                    {{-- ── Branch Rating ─────────────────────────────────────── --}}
                    @if($branch->total_ratings > 0)
                    <div class="branch-rating-box mb-3">
                        {{-- Score + stars + count --}}
                        <div class="d-flex align-items-center gap-2 mb-2">
                            <span class="branch-rating-score">{{ $branch->avg_rating }}</span>
                            <div class="d-flex gap-1">
                                @php $filled = (int) round($branch->avg_rating); @endphp
                                @for($s = 1; $s <= 5; $s++)
                                    <i class="bi bi-star{{ $s <= $filled ? '-fill' : '' }}"
                                       style="font-size: .8rem; color: {{ $s <= $filled ? '#f59e0b' : '#d1d5db' }};"></i>
                                @endfor
                            </div>
                            <span class="text-muted" style="font-size: .72rem;">
                                {{ $branch->total_ratings }} {{ $branch->total_ratings == 1 ? 'review' : 'reviews' }}
                            </span>
                        </div>
                        {{-- Per-star bar breakdown --}}
                        @foreach($branch->rating_distribution as $star => $count)
                        @php
                            $pct      = $branch->total_ratings > 0 ? round(($count / $branch->total_ratings) * 100) : 0;
                            $barColor = $star >= 4 ? '#f59e0b' : ($star == 3 ? '#94a3b8' : '#ef4444');
                        @endphp
                        <div class="d-flex align-items-center gap-2 mb-1">
                            <span class="branch-star-lbl">{{ $star }}<i class="bi bi-star-fill ms-1" style="font-size: .5rem; color: {{ $barColor }};"></i></span>
                            <div class="branch-bar-track flex-grow-1">
                                <div class="branch-bar-fill" style="width: {{ $pct }}%; background: {{ $barColor }};"></div>
                            </div>
                            <span class="branch-bar-count">{{ $count }}</span>
                        </div>
                        @endforeach
                    </div>
                    @else
                    <div class="mb-3">
                        <span class="text-muted" style="font-size: .78rem; font-style: italic;">
                            <i class="bi bi-star me-1"></i>No ratings yet
                        </span>
                    </div>
                    @endif
                    {{-- ── End Branch Rating ─────────────────────────────────── --}}

                    {{-- Contact Info --}}
                    <div class="mb-3 pb-3 border-bottom">
                        <div class="d-flex align-items-center text-muted small mb-2">
                            <i class="bi bi-telephone-fill me-2" style="color: #3D3B6B;"></i>
                            <span>{{ $branch->phone ?? 'N/A' }}</span>
                        </div>
                        @if($branch->email)
                        <div class="d-flex align-items-center text-muted small">
                            <i class="bi bi-envelope-fill me-2" style="color: #3D3B6B;"></i>
                            <span>{{ $branch->email }}</span>
                        </div>
                        @endif
                        @if($branch->manager)
                        <div class="d-flex align-items-center text-muted small mt-2">
                            <i class="bi bi-person-badge-fill me-2" style="color: #3D3B6B;"></i>
                            <span>Manager: {{ $branch->manager }}</span>
                        </div>
                        @endif
                    </div>

                    {{-- Stats --}}
                    <div class="row g-2 mb-3">
                        <div class="col-4">
                            <div class="text-center p-2 bg-light rounded">
                                <div class="fw-bold text-dark">{{ number_format($branch->laundries_mtd ?? 0) }}</div>
                                <small class="text-muted">MTD Laundries</small>
                            </div>
                        </div>
                        <div class="col-4">
                            <div class="text-center p-2 bg-light rounded">
                                <div class="fw-bold text-success">₱{{ number_format($branch->revenue_mtd ?? 0, 0) }}</div>
                                <small class="text-muted">MTD Revenue</small>
                            </div>
                        </div>
                        <div class="col-4">
                            <div class="text-center p-2 bg-light rounded">
                                <div class="fw-bold text-primary">{{ $branch->active_staff ?? 0 }}</div>
                                <small class="text-muted">Active Staff</small>
                            </div>
                        </div>
                    </div>

                    {{-- Action Buttons --}}
                    <div class="d-flex gap-2">
                        <a href="{{ route('admin.branches.edit', $branch->id) }}"
                            class="btn btn-outline-secondary btn-sm flex-fill">
                            <i class="bi bi-gear me-1"></i> Settings
                        </a>
                        <a href="{{ route('admin.branches.show', $branch->id) }}"
                            class="btn btn-sm flex-fill text-white" style="background: #3D3B6B;">
                            <i class="bi bi-bar-chart me-1"></i> Analytics
                        </a>
                    </div>
                </div>
            </div>
        </div>
        @empty
        <div class="col-12">
            <div class="card border-0 shadow-sm rounded-4">
                <div class="card-body p-5 text-center">
                    <i class="bi bi-building" style="font-size: 4rem; opacity: 0.2;"></i>
                    <h5 class="fw-bold mt-3">No Branches Yet</h5>
                    <p class="text-muted mb-3">Start by adding your first branch location</p>
                    <a href="{{ route('admin.branches.create') }}" class="btn btn-primary me-2" style="background: #3D3B6B; border: none;">
                        <i class="bi bi-plus-circle me-2"></i>Add Your First Branch
                    </a>
                    <button class="btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#addBranchModal">
                        <i class="bi bi-plus-lg me-2"></i>Quick Add
                    </button>
                </div>
            </div>
        </div>
        @endforelse
    </div>

    {{-- System Health Card --}}
    <div class="card border-0 shadow-sm rounded-4">
        <div class="card-body p-4">
            <div class="row align-items-center">
                <div class="col-md-8">
                    <div class="d-flex align-items-center mb-3">
                        <div class="bg-success bg-opacity-10 p-3 rounded-3 me-3">
                            <i class="bi bi-shield-check fs-2 text-success"></i>
                        </div>
                        <div>
                            <h5 class="fw-bold mb-1">System-Wide Health</h5>
                            <h3 class="fw-bold text-success mb-0">{{ $activePercentage }}% Operational</h3>
                        </div>
                    </div>
                    <p class="text-muted mb-0">
                        @if($activePercentage == 100)
                        All branches are currently active with no reported issues. All systems operational and running smoothly.
                        @elseif($activePercentage >= 70)
                        Most branches are operational. {{ $branches->where('is_active', false)->count() }} branch(es) currently inactive.
                        @else
                        {{ $branches->where('is_active', false)->count() }} out of {{ $totalBranches }} branches are currently inactive.
                        @endif
                    </p>
                </div>
                <div class="col-md-4">
                    <div class="row g-3">
                        <div class="col-6">
                            <div class="text-center">
                                <div class="fw-bold fs-4 text-dark">{{ number_format($total_laundries) }}</div>
                                <small class="text-muted text-uppercase">Total Laundries</small>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="text-center">
                                <div class="fw-bold fs-4 text-success">₱{{ number_format($total_revenue / 1000, 1) }}k</div>
                                <small class="text-muted text-uppercase">Total Revenue</small>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="text-center">
                                <div class="fw-bold fs-4 text-primary">{{ $totalBranches }}</div>
                                <small class="text-muted text-uppercase">Branches</small>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="text-center">
                                <div class="fw-bold fs-4 text-info">{{ $branches->sum('staff_count') }}</div>
                                <small class="text-muted text-uppercase">Staff</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Quick Add Branch Modal --}}
<div class="modal fade" id="addBranchModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content border-0 shadow rounded-4">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title fw-bold">Quick Add Branch</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form action="{{ route('admin.branches.store') }}" method="POST">
                @csrf
                <div class="modal-body p-4">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Branch Name <span class="text-danger">*</span></label>
                            <input type="text" name="name" class="form-control @error('name') is-invalid @enderror"
                                   value="{{ old('name') }}" placeholder="e.g., WashBox Sibulan" required>
                            @error('name')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Branch Code <span class="text-danger">*</span></label>
                            <input type="text" name="code" class="form-control @error('code') is-invalid @enderror"
                                   value="{{ old('code') }}" placeholder="e.g., SBL" required maxlength="10">
                            <small class="text-muted">Unique 3-10 letter code</small>
                            @error('code')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-semibold">Address <span class="text-danger">*</span></label>
                            <textarea name="address" class="form-control @error('address') is-invalid @enderror"
                                      rows="2" placeholder="Full street address" required>{{ old('address') }}</textarea>
                            @error('address')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">City <span class="text-danger">*</span></label>
                            <input type="text" name="city" class="form-control @error('city') is-invalid @enderror"
                                   value="{{ old('city') }}" placeholder="e.g., Sibulan" required>
                            @error('city')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Province <span class="text-danger">*</span></label>
                            <input type="text" name="province" class="form-control @error('province') is-invalid @enderror"
                                   value="{{ old('province', 'Negros Oriental') }}" placeholder="e.g., Negros Oriental" required>
                            @error('province')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Phone Number <span class="text-danger">*</span></label>
                            <input type="text" name="phone" class="form-control @error('phone') is-invalid @enderror"
                                   value="{{ old('phone') }}" placeholder="e.g., 09171234567" required>
                            @error('phone')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Email Address</label>
                            <input type="email" name="email" class="form-control @error('email') is-invalid @enderror"
                                   value="{{ old('email') }}" placeholder="e.g., branch@washbox.com">
                            @error('email')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Status</label>
                            <select class="form-select @error('is_active') is-invalid @enderror" name="is_active">
                                <option value="1" {{ old('is_active', 1) ? 'selected' : '' }}>Active</option>
                                <option value="0" {{ !old('is_active', 1) ? 'selected' : '' }}>Inactive</option>
                            </select>
                            <small class="text-muted">Inactive branches won't appear in mobile app</small>
                            @error('is_active')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Branch Manager</label>
                            <input type="text" name="manager" class="form-control @error('manager') is-invalid @enderror"
                                   value="{{ old('manager') }}" placeholder="Optional">
                            @error('manager')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-0 pt-0">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary" style="background: #3D3B6B; border: none;">
                        <i class="bi bi-check-circle me-2"></i>Create Branch
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Animate rating bars on page load
    document.querySelectorAll('.branch-bar-fill').forEach(function(el) {
        var target = el.style.width;
        el.style.width = '0';
        requestAnimationFrame(function() { el.style.width = target; });
    });

    // Format phone number in modal
    const phoneInput = document.querySelector('#addBranchModal input[name="phone"]');
    if (phoneInput) {
        phoneInput.addEventListener('input', function(e) {
            let value = e.target.value.replace(/\D/g, '');
            if (value.length > 0) {
                if (value.length <= 4) {
                    value = value;
                } else if (value.length <= 7) {
                    value = value.slice(0, 4) + ' ' + value.slice(4);
                } else if (value.length <= 11) {
                    value = value.slice(0, 4) + ' ' + value.slice(4, 7) + ' ' + value.slice(7);
                } else {
                    value = value.slice(0, 11);
                }
            }
            e.target.value = value;
        });
    }

    // Handle modal errors
    @if($errors->any() && !session('from_modal'))
    setTimeout(() => {
        const modal = new bootstrap.Modal(document.getElementById('addBranchModal'));
        modal.show();
    }, 100);
    @endif
});
</script>
@endpush

@push('styles')
<style>
    .branch-card {
        transition: transform 0.2s, box-shadow 0.2s;
    }

    .branch-card:hover {
        transform: translateY(-4px);
        box-shadow: 0 8px 16px rgba(0,0,0,0.1) !important;
    }

    .badge {
        font-weight: 600;
        letter-spacing: 0.5px;
    }

    .bg-light:hover {
        background-color: #E5E7EB !important;
    }

    .dropdown-item:hover {
        background-color: #f8f9fa;
    }

    .dropdown-item.text-danger:hover {
        background-color: rgba(220, 53, 69, 0.1);
    }

    .dropdown-item.text-warning:hover {
        background-color: rgba(255, 193, 7, 0.1);
    }

    .dropdown-item.text-success:hover {
        background-color: rgba(25, 135, 84, 0.1);
    }

    /* ── Branch Rating Block ───────────────────────────── */
    .branch-rating-box {
        background: #fffbeb;
        border: 1px solid #fef3c7;
        border-radius: 12px;
        padding: 10px 12px;
    }

    [data-theme="dark"] .branch-rating-box {
        background: rgba(245, 158, 11, 0.08);
        border-color: rgba(245, 158, 11, 0.2);
    }

    .branch-rating-score {
        font-size: 1.4rem;
        font-weight: 900;
        color: #f59e0b;
        line-height: 1;
    }

    .branch-star-lbl {
        font-size: .65rem;
        font-weight: 700;
        color: #64748b;
        width: 20px;
        text-align: right;
        flex-shrink: 0;
        display: flex;
        align-items: center;
        justify-content: flex-end;
        gap: 2px;
    }

    .branch-bar-track {
        height: 6px;
        border-radius: 99px;
        background: #e2e8f0;
        overflow: hidden;
    }

    .branch-bar-fill {
        height: 100%;
        border-radius: 99px;
        transition: width 0.7s cubic-bezier(0.4, 0, 0.2, 1);
    }

    .branch-bar-count {
        font-size: .65rem;
        font-weight: 700;
        color: #94a3b8;
        width: 16px;
        text-align: right;
        flex-shrink: 0;
    }

    [data-theme="dark"] .branch-bar-track {
        background: #334155;
    }
</style>
@endpush
@endsection
