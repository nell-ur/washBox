@extends('staff.layouts.staff')
@section('title', 'Customers')
@section('page-title', 'Register New Customer')
@push('styles')
    <link rel="stylesheet" href="{{ asset('assets/css/customers.css') }}">
@endpush
@section('content')
    <div class="container-fluid px-4 py-4">
        <div class="d-flex align-items-center mb-4">
            <a href="{{ route('staff.customers.index') }}" class="btn btn-link text-dark p-0 me-3">
                <i class="bi bi-arrow-left fs-4"></i>
            </a>
        </div>

        <div class="row justify-content-center">
            <div class="col-lg-8">
                <div class="card border-0 shadow-sm rounded-4">
                    <div class="card-body p-4">
                        <form action="{{ route('staff.customers.store') }}" method="POST">
                            @csrf
                            <div class="row g-4">
                                <div class="col-12">
                                    <h6 class="text-primary fw-bold mb-3">Basic Information</h6>
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label fw-semibold">Full Name</label>
                                    <input type="text" name="name" class="form-control @error('name') is-invalid @enderror"
                                        placeholder="Enter complete name" value="{{ old('name') }}" required>
                                    @error('name')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label fw-semibold">Phone Number</label>
                                    <input type="text" name="phone" class="form-control @error('phone') is-invalid @enderror"
                                        placeholder="09XXXXXXXXX" maxlength="11" value="{{ old('phone') }}" required>
                                    @error('phone')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="col-md-12">
                                    <label class="form-label fw-semibold">Email Address (Optional)</label>
                                    <input type="email" name="email" class="form-control @error('email') is-invalid @enderror"
                                        placeholder="customer@example.com" value="{{ old('email') }}">
                                    @error('email')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="col-12 mt-4">
                                    <h6 class="text-primary fw-bold mb-3">Service Details</h6>
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label fw-semibold">Preferred/Current Branch</label>
                                    <select name="preferred_branch_id" class="form-select @error('preferred_branch_id') is-invalid @enderror">
                                        <option value="">Default: {{ $currentBranch->name }}</option>
                                        @foreach($branches as $branch)
                                            <option value="{{ $branch->id }}"
                                                {{ old('preferred_branch_id', auth()->user()->branch_id) == $branch->id ? 'selected' : '' }}>
                                                {{ $branch->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('preferred_branch_id')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="col-12">
                                    <label class="form-label fw-semibold">Home Address</label>
                                    <textarea name="address" class="form-control @error('address') is-invalid @enderror" rows="3"
                                        placeholder="Enter house number, street, and barangay">{{ old('address') }}</textarea>
                                    @error('address')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="col-12 mt-4">
                                    <hr>
                                    <div class="d-flex justify-content-end gap-2">
                                        <a href="{{ route('staff.customers.index') }}" class="btn btn-light px-4">Cancel</a>
                                        <button type="submit" class="btn btn-primary px-5" style="background: #3D3B6B;">
                                            Register Customer
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
