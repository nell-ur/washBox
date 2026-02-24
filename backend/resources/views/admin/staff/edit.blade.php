@extends('admin.layouts.app')

@section('page-title', 'Edit Staff Member')

@section('content')
<div class="container-fluid px-4 py-4">
    {{-- Header --}}
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <p class="text-muted small mb-0">Update {{ $staff->name }}'s information</p>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('admin.staff.show', $staff) }}" class="btn btn-outline-secondary">
                <i class="bi bi-eye me-2"></i>View Profile
            </a>
            <a href="{{ route('admin.staff.index') }}" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left me-2"></i>Back to Staff
            </a>
        </div>
    </div>

    <form action="{{ route('admin.staff.update', $staff) }}" method="POST" enctype="multipart/form-data">
        @csrf
        @method('PUT')

        <div class="row g-4">
            {{-- Left Column - Main Form --}}
            <div class="col-lg-8">
                {{-- Personal Information --}}
                <div class="card border-0 shadow-sm rounded-4 mb-4">
                    <div class="card-header bg-white border-bottom py-3">
                        <h6 class="mb-0 fw-bold text-dark">
                            <i class="bi bi-person-circle me-2" style="color: #3D3B6B;"></i>
                            Personal Information
                        </h6>
                    </div>
                    <div class="card-body p-4">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Full Name <span class="text-danger">*</span></label>
                                <input type="text" name="name" class="form-control @error('name') is-invalid @enderror"
                                    value="{{ old('name', $staff->name) }}" required>
                                @error('name')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Email Address <span class="text-danger">*</span></label>
                                <input type="email" name="email" class="form-control @error('email') is-invalid @enderror"
                                    value="{{ old('email', $staff->email) }}" required>
                                @error('email')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Phone Number</label>
                                <input type="text" name="phone" class="form-control @error('phone') is-invalid @enderror"
                                    value="{{ old('phone', $staff->phone) }}">
                                @error('phone')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Employee ID</label>
                                <input type="text" name="employee_id" class="form-control @error('employee_id') is-invalid @enderror"
                                    value="{{ old('employee_id', $staff->employee_id) }}">
                                @error('employee_id')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-12">
                                <label class="form-label fw-semibold">Address</label>
                                <textarea name="address" class="form-control @error('address') is-invalid @enderror"
                                    rows="2">{{ old('address', $staff->address) }}</textarea>
                                @error('address')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Employment Details --}}
                <div class="card border-0 shadow-sm rounded-4 mb-4">
                    <div class="card-header bg-white border-bottom py-3">
                        <h6 class="mb-0 fw-bold text-dark">
                            <i class="bi bi-briefcase me-2" style="color: #3D3B6B;"></i>
                            Employment Details
                        </h6>
                    </div>
                    <div class="card-body p-4">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Assigned Branch <span class="text-danger">*</span></label>
                                <select name="branch_id" class="form-select @error('branch_id') is-invalid @enderror" required>
                                    <option value="">Select Branch</option>
                                    @foreach($branches as $branch)
                                        <option value="{{ $branch->id }}"
                                            {{ old('branch_id', $staff->branch_id) == $branch->id ? 'selected' : '' }}>
                                            {{ $branch->name }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('branch_id')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Position / Role</label>
                                <input type="text" name="position" class="form-control @error('position') is-invalid @enderror"
                                    value="{{ old('position', $staff->position) }}">
                                @error('position')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Hire Date</label>
                                <input type="date" name="hire_date" class="form-control @error('hire_date') is-invalid @enderror"
                                    value="{{ old('hire_date', $staff->hire_date ? $staff->hire_date->format('Y-m-d') : '') }}">
                                @error('hire_date')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Status</label>
                                <select name="is_active" class="form-select">
                                    <option value="1" {{ old('is_active', $staff->is_active) == '1' ? 'selected' : '' }}>Active</option>
                                    <option value="0" {{ old('is_active', $staff->is_active) == '0' ? 'selected' : '' }}>Inactive</option>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Emergency Contact --}}
                <div class="card border-0 shadow-sm rounded-4 mb-4">
                    <div class="card-header bg-white border-bottom py-3">
                        <h6 class="mb-0 fw-bold text-dark">
                            <i class="bi bi-telephone-plus me-2" style="color: #3D3B6B;"></i>
                            Emergency Contact
                        </h6>
                    </div>
                    <div class="card-body p-4">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Contact Name</label>
                                <input type="text" name="emergency_contact" class="form-control @error('emergency_contact') is-invalid @enderror"
                                    value="{{ old('emergency_contact', $staff->emergency_contact) }}">
                                @error('emergency_contact')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Contact Phone</label>
                                <input type="text" name="emergency_phone" class="form-control @error('emergency_phone') is-invalid @enderror"
                                    value="{{ old('emergency_phone', $staff->emergency_phone) }}">
                                @error('emergency_phone')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Action Buttons --}}
                <div class="d-flex gap-2 mb-4">
                    <button type="submit" class="btn btn-primary px-5 shadow-sm" style="background: #3D3B6B; border: none;">
                        <i class="bi bi-check-circle me-2"></i>Update Staff Member
                    </button>
                    <a href="{{ route('admin.staff.show', $staff) }}" class="btn btn-outline-secondary px-4">
                        <i class="bi bi-x-circle me-2"></i>Cancel
                    </a>
                    <form action="{{ route('admin.staff.toggle-status', $staff) }}" method="POST" class="ms-auto">
                        @csrf
                        <button type="submit" class="btn btn-outline-{{ $staff->is_active ? 'warning' : 'success' }}">
                            <i class="bi bi-{{ $staff->is_active ? 'pause-circle' : 'play-circle' }} me-2"></i>
                            {{ $staff->is_active ? 'Deactivate' : 'Activate' }}
                        </button>
                    </form>
                </div>
            </div>

            {{-- Right Column - Preview & Photo --}}
            <div class="col-lg-4">
                <div class="sticky-top" style="top: 20px;">
                    {{-- Current Photo --}}
                    <div class="card border-0 shadow-sm rounded-4 mb-3">
                        <div class="card-header bg-white border-bottom py-3">
                            <h6 class="mb-0 fw-bold text-dark">
                                <i class="bi bi-camera me-2" style="color: #3D3B6B;"></i>
                                Profile Photo
                            </h6>
                        </div>
                        <div class="card-body p-4 text-center">
                            <div class="mb-3">
                                <div class="rounded-circle mx-auto d-flex align-items-center justify-content-center overflow-hidden"
                                    id="photoPreview"
                                    style="width: 150px; height: 150px; background: linear-gradient(135deg, #3D3B6B 0%, #6366F1 100%);">
                                    @if($staff->profile_photo_path)
                                        <img src="{{ asset('storage/' . $staff->profile_photo_path) }}"
                                            class="w-100 h-100" style="object-fit: cover;">
                                    @else
                                        <i class="bi bi-person text-white" style="font-size: 4rem;"></i>
                                    @endif
                                </div>
                            </div>
                            <input type="file" name="photo" id="photoInput" class="form-control @error('photo') is-invalid @enderror" accept="image/*">
                            <small class="text-muted d-block mt-2">Upload new photo (Max 2MB)</small>
                            @error('photo')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    {{-- Reset Password Card --}}
                    <div class="card border-0 shadow-sm rounded-4 mb-3">
                        <div class="card-body p-4">
                            <h6 class="fw-bold mb-3">
                                <i class="bi bi-shield-lock text-warning me-2"></i>Reset Password
                            </h6>
                            <p class="small text-muted mb-3">Need to reset this staff member's password?</p>
                            <button type="button" class="btn btn-outline-warning btn-sm w-100" data-bs-toggle="modal" data-bs-target="#resetPasswordModal">
                                <i class="bi bi-key me-2"></i>Reset Password
                            </button>
                        </div>
                    </div>

                    {{-- Quick Stats --}}
                    <div class="card border-0 shadow-sm rounded-4">
                        <div class="card-body p-4">
                            <h6 class="fw-bold mb-3">
                                <i class="bi bi-graph-up text-info me-2"></i>Quick Stats
                            </h6>
                            <div class="d-flex justify-content-between mb-2">
                                <span class="small text-muted">Total Laundries:</span>
                                <strong>{{ $staff->laundries()->count() }}</strong>
                            </div>
                            <div class="d-flex justify-content-between mb-2">
                                <span class="small text-muted">Member Since:</span>
                                <strong>{{ $staff->created_at->format('M Y') }}</strong>
                            </div>
                            @if($staff->hire_date)
                            <div class="d-flex justify-content-between">
                                <span class="small text-muted">Tenure:</span>
                                <strong>{{ $staff->hire_date->diffForHumans(null, true) }}</strong>
                            </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>

{{-- Reset Password Modal --}}
<div class="modal fade" id="resetPasswordModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content border-0 shadow rounded-4">
            <div class="modal-header border-0">
                <h5 class="modal-title fw-bold">Reset Password</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form action="{{ route('admin.staff.reset-password', $staff) }}" method="POST">
                @csrf
                <div class="modal-body">
                    <div class="alert alert-warning border-0 mb-3">
                        <i class="bi bi-exclamation-triangle me-2"></i>
                        This will reset {{ $staff->name }}'s password. They will need to use the new password to log in.
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">New Password</label>
                        <input type="password" name="password" class="form-control" placeholder="Minimum 8 characters" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Confirm New Password</label>
                        <input type="password" name="password_confirmation" class="form-control" placeholder="Re-enter password" required>
                    </div>
                </div>
                <div class="modal-footer border-0">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-warning">
                        <i class="bi bi-key me-2"></i>Reset Password
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

@push('scripts')
<script>
// Photo preview
document.getElementById('photoInput').addEventListener('change', function(e) {
    const file = e.target.files[0];
    if (file) {
        const reader = new FileReader();
        reader.onload = function(e) {
            document.getElementById('photoPreview').innerHTML =
                `<img src="${e.target.result}" class="w-100 h-100" style="object-fit: cover;">`;
        }
        reader.readAsDataURL(file);
    }
});
</script>
@endpush
@endsection
