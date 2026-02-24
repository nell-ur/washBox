@extends('admin.layouts.app')

@section('page-title', 'Edit Customer')
@push('styles')
    <link rel="stylesheet" href="{{ asset('assets/css/customers.css') }}">
@endpush

@section('content')
<div class="container-fluid px-4 py-4">


    <div class="d-flex justify-content-between align-items-center mb-4">
        <div class="d-flex gap-2">
            <a href="{{ route('admin.customers.show', $customer->id) }}" class="btn btn-outline-primary">
                <i class="bi bi-eye me-1"></i> View Profile
            </a>
            <a href="{{ route('admin.customers.index') }}" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left me-1"></i> Back
            </a>
        </div>
    </div>

    <form action="{{ route('admin.customers.update', $customer->id) }}" method="POST" enctype="multipart/form-data">
        @csrf
        @method('PUT')

        <div class="row g-4">
            {{-- Left Column: Main Information --}}
            <div class="col-xl-8">
                <div class="card border-0 shadow-sm rounded-4 mb-4">
                    <div class="card-header bg-white py-3 border-bottom">
                        <h5 class="fw-bold mb-0 text-primary">Personal Details</h5>
                    </div>
                    <div class="card-body p-4">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label fw-bold">Full Name <span class="text-danger">*</span></label>
                                <input type="text" name="name" class="form-control @error('name') is-invalid @enderror"
                                       value="{{ old('name', $customer->name) }}" required>
                                @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>

                            <div class="col-md-6">
                                <label class="form-label fw-bold">Phone Number <span class="text-danger">*</span></label>
                                <input type="text" name="phone" class="form-control @error('phone') is-invalid @enderror"
                                       value="{{ old('phone', $customer->phone) }}" required>
                                @error('phone') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>

                            <div class="col-12">
                                <label class="form-label fw-bold">Email Address</label>
                                <input type="email" name="email" class="form-control @error('email') is-invalid @enderror"
                                       value="{{ old('email', $customer->email) }}">
                                @error('email') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>

                            <div class="col-12">
                                <label class="form-label fw-bold">Home Address</label>
                                <textarea name="address" class="form-control" rows="3">{{ old('address', $customer->address) }}</textarea>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card border-0 shadow-sm rounded-4">
                    <div class="card-header bg-white py-3 border-bottom">
                        <h5 class="fw-bold mb-0 text-primary">Service Preferences</h5>
                    </div>
                    <div class="card-body p-4">
                        <div class="row">
                            <div class="col-md-12">
                                <label class="form-label fw-bold">Primary Branch</label>
                                <select name="preferred_branch_id" class="form-select @error('preferred_branch_id') is-invalid @enderror">
                                    <option value="">Select Branch</option>
                                    @foreach($branches as $branch)
                                        <option value="{{ $branch->id }}" {{ old('preferred_branch_id', $customer->preferred_branch_id) == $branch->id ? 'selected' : '' }}>
                                            {{ $branch->name }}
                                        </option>
                                    @endforeach
                                </select>
                                <small class="text-muted">Determines the default dashboard for this customer's laundries.</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Right Column: Status & Metadata --}}
            <div class="col-xl-4">
                <div class="card border-0 shadow-sm rounded-4 mb-4">
                    <div class="card-body p-4">
                        <div class="mb-4">
                            <label class="form-label fw-bold">Account Status</label>
                            <select name="is_active" class="form-select border-2 {{ $customer->is_active ? 'border-success' : 'border-danger' }}">
                                <option value="1" {{ old('is_active', $customer->is_active) ? 'selected' : '' }}>Active</option>
                                <option value="0" {{ !old('is_active', $customer->is_active) ? 'selected' : '' }}>Inactive / Suspended</option>
                            </select>
                        </div>

                        <div class="bg-light p-3 rounded-3 mb-4">
                            <div class="d-flex justify-content-between mb-2">
                                <span class="text-muted small">Registered Via:</span>
                                <span class="badge bg-info text-dark">{{ $customer->registration_type_label }}</span>
                            </div>
                            <div class="d-flex justify-content-between mb-2">
                                <span class="text-muted small">Total Laundries:</span>
                                <span class="fw-bold">{{ $customer->getTotalLaundriesCount() }}</span>
                            </div>
                            <div class="d-flex justify-content-between">
                                <span class="text-muted small">Customer Since:</span>
                                <span class="fw-bold">{{ $customer->created_at->format('M Y') }}</span>
                            </div>
                        </div>

                        <button type="submit" class="btn btn-primary w-100 py-2 fw-bold" style="background: #3D3B6B;">
                            <i class="bi bi-save me-2"></i> Update Profile
                        </button>
                    </div>
                </div>

                {{-- Admin Log Note (Optional) --}}
                <div class="card border-0 shadow-sm rounded-4">
                    <div class="card-body p-4">
                        <h6 class="fw-bold mb-2 small text-uppercase">Admin Note</h6>
                        <p class="text-muted x-small mb-0">
                            Last modified: {{ $customer->updated_at->diffForHumans() }}
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>
@endsection
