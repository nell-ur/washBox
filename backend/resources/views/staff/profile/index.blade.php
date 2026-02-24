@extends('staff.layouts.staff') {{-- Ensure this uses your staff layout --}}

@section('title', 'My Profile')

@section('content')
<div class="container-fluid px-4 py-4">
    <div class="row">
        <div class="col-md-6 mb-4">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white py-3">
                    <h5 class="fw-bold mb-0 text-muted">Personal Information</h5>
                </div>
                <div class="card-body">
                    <form action="{{ route('staff.profile.update') }}" method="POST">
                        @csrf
                        <div class="mb-3">
                            <label class="form-label text-muted">Full Name (Contact Admin to Change)</label>
                            <input type="text" class="form-control bg-light" value="{{ $user->name }}" readonly>
                        </div>
                        <div class="mb-3">
                            <label class="form-label text-muted">Email Address (Contact Admin to Change)</label>
                            <input type="email" class="form-control bg-light" value="{{ $user->email }}" readonly>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Phone Number</label>
                            <input type="text" name="phone" class="form-control" value="{{ $user->phone }}" placeholder="Update your contact number">
                        </div>
                        <button type="submit" class="btn btn-primary">Update Phone Number</button>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-md-6 mb-4">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white py-3">
                    <h5 class="fw-bold mb-0">Change Password</h5>
                </div>
                <div class="card-body">
                    <form action="{{ route('staff.profile.password') }}" method="POST">
                        @csrf
                        <div class="mb-3">
                            <label class="form-label">Current Password</label>
                            <input type="password" name="current_password" class="form-control" required>
                        </div>
                        <hr>
                        <div class="mb-3">
                            <label class="form-label">New Password</label>
                            <input type="password" name="password" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Confirm New Password</label>
                            <input type="password" name="password_confirmation" class="form-control" required>
                        </div>
                        <button type="submit" class="btn btn-danger w-100">Update Security Credentials</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
