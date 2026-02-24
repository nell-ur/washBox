@extends('admin.layouts.app')

@section('page-title', 'Admin Profile')

@section('content')
<div class="container-fluid px-4 py-4">

    {{-- Profile Header --}}
    <div class="card border-0 shadow-sm rounded-4 mb-4 overflow-hidden">
        <div class="bg-gradient position-relative" style="background: linear-gradient(135deg, #3D3B6B 0%, #605C9D 100%); min-height: 200px;">
            <div class="position-absolute bottom-0 start-0 p-4 translate-middle-y ms-4">
                <div class="d-flex align-items-end">
                    <div class="avatar-container position-relative">
                        <div class="avatar-wrapper bg-white p-2 rounded-circle shadow-lg">
                            <div class="avatar-inner bg-gradient-primary rounded-circle d-flex align-items-center justify-content-center"
                                 style="width: 120px; height: 120px;">
                                <i class="bi bi-person-circle text-white" style="font-size: 5rem;"></i>
                            </div>
                        </div>
                        <div class="avatar-badge position-absolute bottom-0 end-0 bg-success rounded-circle p-2 border-3 border-white">
                            <i class="bi bi-check text-white" style="font-size: 0.875rem;"></i>
                        </div>
                    </div>
                    <div class="ms-4 text-white mb-4">
                        <h1 class="fw-bold mb-1 text-white display-6">{{ $user->name }}</h1>
                        <div class="d-flex align-items-center gap-2">
                            <span class="badge bg-white text-primary px-3 py-2 rounded-pill fw-semibold">
                                <i class="bi bi-shield-check me-1"></i> System Administrator
                            </span>
                            <span class="text-white-75 small">{{ $user->role ?? 'Admin' }}</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success border-0 shadow-sm rounded-4 mb-4 d-flex align-items-center fade show" role="alert">
            <i class="bi bi-check-circle-fill me-2 fs-5"></i>
            <span class="fw-semibold">{{ session('success') }}</span>
            <button type="button" class="btn-close ms-auto" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <div class="row g-4">
        {{-- Profile Information --}}
        <div class="col-xl-7 col-lg-6">
            <div class="card border-0 shadow-sm rounded-4 h-100 hover-lift">
                <div class="card-header bg-white py-3 px-4 border-bottom-0">
                    <div class="d-flex align-items-center justify-content-between">
                        <div class="d-flex align-items-center gap-3">
                            <div class="icon-container bg-primary bg-opacity-10 rounded-circle p-2">
                                <i class="bi bi-person text-primary fs-4"></i>
                            </div>
                            <div>
                                <h5 class="fw-bold mb-0 text-dark">Personal Information</h5>
                                <p class="text-muted small mb-0">Update your profile details</p>
                            </div>
                        </div>
                        <span class="badge bg-primary bg-opacity-10 text-primary border border-primary border-opacity-25 px-3 py-2 rounded-pill">
                            <i class="bi bi-pencil me-1"></i> Editable
                        </span>
                    </div>
                </div>
                <div class="card-body p-4 pt-3">
                    <form action="{{ route('admin.profile.update') }}" method="POST" id="profileForm">
                        @csrf
                        <div class="row g-3">
                            <div class="col-12">
                                <label class="form-label fw-semibold small text-uppercase text-muted mb-2">
                                    <i class="bi bi-person me-1"></i> Full Name
                                </label>
                                <div class="input-group input-group-lg">
                                    <span class="input-group-text bg-light border-end-0 rounded-start-3">
                                        <i class="bi bi-person text-muted"></i>
                                    </span>
                                    <input type="text" name="name" class="form-control border-start-0 rounded-end-3 py-3"
                                           value="{{ $user->name }}" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold small text-uppercase text-muted mb-2">
                                    <i class="bi bi-envelope me-1"></i> Email Address
                                </label>
                                <div class="input-group input-group-lg">
                                    <span class="input-group-text bg-light border-end-0 rounded-start-3">
                                        <i class="bi bi-envelope text-muted"></i>
                                    </span>
                                    <input type="email" name="email" class="form-control border-start-0 rounded-end-3 py-3"
                                           value="{{ $user->email }}" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold small text-uppercase text-muted mb-2">
                                    <i class="bi bi-phone me-1"></i> Phone Number
                                </label>
                                <div class="input-group input-group-lg">
                                    <span class="input-group-text bg-light border-end-0 rounded-start-3">
                                        <i class="bi bi-phone text-muted"></i>
                                    </span>
                                    <input type="text" name="phone" class="form-control border-start-0 rounded-end-3 py-3"
                                           value="{{ $user->phone }}" placeholder="+63 900 000 0000">
                                </div>
                            </div>
                            <div class="col-12 mt-4">
                                <button type="submit" class="btn btn-primary px-5 rounded-3 shadow-sm fw-bold py-3"
                                        style="background: linear-gradient(135deg, #3D3B6B 0%, #605C9D 100%); border: none;">
                                    <i class="bi bi-save me-2"></i> Save Profile Changes
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        {{-- Security Settings --}}
        <div class="col-xl-5 col-lg-6">
            <div class="card border-0 shadow-sm rounded-4 h-100 hover-lift">
                <div class="card-header bg-white py-3 px-4 border-bottom-0">
                    <div class="d-flex align-items-center justify-content-between">
                        <div class="d-flex align-items-center gap-3">
                            <div class="icon-container bg-danger bg-opacity-10 rounded-circle p-2">
                                <i class="bi bi-shield-lock text-danger fs-4"></i>
                            </div>
                            <div>
                                <h5 class="fw-bold mb-0 text-dark">Security Settings</h5>
                                <p class="text-muted small mb-0">Update your password</p>
                            </div>
                        </div>
                        <span class="badge bg-warning bg-opacity-10 text-warning border border-warning border-opacity-25 px-3 py-2 rounded-pill">
                            <i class="bi bi-exclamation-triangle me-1"></i> Important
                        </span>
                    </div>
                </div>
                <div class="card-body p-4 pt-3">
                    <div class="alert alert-info border-0 bg-info bg-opacity-10 rounded-3 mb-4" role="alert">
                        <div class="d-flex align-items-center">
                            <i class="bi bi-info-circle text-info me-3 fs-4"></i>
                            <div>
                                <h6 class="alert-heading mb-1 text-info">Password Requirements</h6>
                                <p class="mb-0 small">Ensure your password is at least 8 characters long and includes a mix of letters, numbers, and special characters.</p>
                            </div>
                        </div>
                    </div>
                    <form action="{{ route('admin.profile.password') }}" method="POST" id="passwordForm">
                        @csrf
                        <div class="mb-4">
                            <label class="form-label fw-semibold small text-uppercase text-muted mb-2">
                                <i class="bi bi-key me-1"></i> Current Password
                            </label>
                            <div class="password-input-container position-relative">
                                <input type="password" name="current_password" class="form-control form-control-lg rounded-3 py-3"
                                       placeholder="••••••••" required>
                                <button type="button" class="btn btn-link position-absolute end-0 top-50 translate-middle-y me-3 text-muted"
                                        onclick="togglePassword(this)">
                                    <i class="bi bi-eye"></i>
                                </button>
                            </div>
                        </div>
                        <div class="mb-4">
                            <label class="form-label fw-semibold small text-uppercase text-muted mb-2">
                                <i class="bi bi-key-fill me-1"></i> New Password
                            </label>
                            <div class="password-input-container position-relative">
                                <input type="password" name="password" class="form-control form-control-lg rounded-3 py-3"
                                       placeholder="Enter new password" required>
                                <button type="button" class="btn btn-link position-absolute end-0 top-50 translate-middle-y me-3 text-muted"
                                        onclick="togglePassword(this)">
                                    <i class="bi bi-eye"></i>
                                </button>
                            </div>
                        </div>
                        <div class="mb-4">
                            <label class="form-label fw-semibold small text-uppercase text-muted mb-2">
                                <i class="bi bi-key-fill me-1"></i> Confirm New Password
                            </label>
                            <div class="password-input-container position-relative">
                                <input type="password" name="password_confirmation" class="form-control form-control-lg rounded-3 py-3"
                                       placeholder="Repeat new password" required>
                                <button type="button" class="btn btn-link position-absolute end-0 top-50 translate-middle-y me-3 text-muted"
                                        onclick="togglePassword(this)">
                                    <i class="bi bi-eye"></i>
                                </button>
                            </div>
                        </div>
                        <button type="submit" class="btn btn-danger w-100 rounded-3 fw-bold shadow-sm py-3"
                                style="background: linear-gradient(135deg, #EF4444 0%, #DC2626 100%); border: none;">
                            <i class="bi bi-shield-check me-2"></i> Update Security Credentials
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    :root {
        --primary-gradient: linear-gradient(135deg, #3D3B6B 0%, #605C9D 100%);
        --danger-gradient: linear-gradient(135deg, #EF4444 0%, #DC2626 100%);
        --avatar-size: 120px;
        --border-radius: 1rem;
    }

    /* Card hover effects */
    .hover-lift {
        transition: transform 0.3s ease, box-shadow 0.3s ease;
    }

    .hover-lift:hover {
        transform: translateY(-4px);
        box-shadow: 0 12px 24px rgba(0, 0, 0, 0.1) !important;
    }

    /* Avatar styles */
    .avatar-container {
        position: relative;
    }

    .avatar-wrapper {
        position: relative;
        z-index: 2;
    }

    .avatar-inner {
        background: linear-gradient(135deg, #3D3B6B 0%, #605C9D 100%);
    }

    .avatar-badge {
        width: 36px;
        height: 36px;
        z-index: 3;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
    }

    /* Form styles */
    .input-group-lg .form-control,
    .input-group-lg .input-group-text {
        height: 52px;
    }

    .form-control:focus {
        border-color: #3D3B6B;
        box-shadow: 0 0 0 0.25rem rgba(61, 59, 107, 0.15);
        outline: 0;
    }

    .password-input-container .form-control {
        padding-right: 50px;
    }

    .password-input-container .btn-link {
        text-decoration: none;
        z-index: 5;
    }

    .password-input-container .btn-link:hover {
        color: #3D3B6B !important;
    }

    .icon-container {
        width: 48px;
        height: 48px;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    /* Button styles */
    .btn-primary, .btn-danger {
        transition: all 0.3s ease;
        position: relative;
        overflow: hidden;
    }

    .btn-primary:hover, .btn-danger:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 20px rgba(0, 0, 0, 0.2) !important;
    }

    .btn-primary::after, .btn-danger::after {
        content: '';
        position: absolute;
        top: 50%;
        left: 50%;
        width: 5px;
        height: 5px;
        background: rgba(255, 255, 255, 0.5);
        opacity: 0;
        border-radius: 100%;
        transform: scale(1, 1) translate(-50%);
        transform-origin: 50% 50%;
    }

    .btn-primary:focus:not(:active)::after,
    .btn-danger:focus:not(:active)::after {
        animation: ripple 1s ease-out;
    }

    @keyframes ripple {
        0% {
            transform: scale(0, 0);
            opacity: 0.5;
        }
        20% {
            transform: scale(25, 25);
            opacity: 0.3;
        }
        100% {
            opacity: 0;
            transform: scale(40, 40);
        }
    }

    /* Alert animation */
    .fade.show {
        animation: fadeIn 0.5s ease;
    }

    @keyframes fadeIn {
        from {
            opacity: 0;
            transform: translateY(-10px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    /* Gradient background */
    .bg-gradient {
        position: relative;
        overflow: hidden;
    }

    .bg-gradient::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: linear-gradient(45deg, rgba(255,255,255,0.1) 0%, transparent 100%);
        z-index: 1;
    }

    /* Responsive adjustments */
    @media (max-width: 768px) {
        .avatar-inner {
            width: 80px !important;
            height: 80px !important;
        }

        .avatar-badge {
            width: 28px;
            height: 28px;
        }

        .display-6 {
            font-size: 1.75rem;
        }
    }
</style>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Password toggle functionality
        window.togglePassword = function(button) {
            const input = button.parentElement.querySelector('input');
            const icon = button.querySelector('i');

            if (input.type === 'password') {
                input.type = 'text';
                icon.classList.remove('bi-eye');
                icon.classList.add('bi-eye-slash');
            } else {
                input.type = 'password';
                icon.classList.remove('bi-eye-slash');
                icon.classList.add('bi-eye');
            }
        }

        // Form validation
        const profileForm = document.getElementById('profileForm');
        const passwordForm = document.getElementById('passwordForm');

        if (profileForm) {
            profileForm.addEventListener('submit', function(e) {
                const phoneInput = this.querySelector('input[name="phone"]');
                if (phoneInput.value.trim() !== '') {
                    // Basic phone validation
                    const phoneRegex = /^[\d\s\+\-\(\)]{7,}$/;
                    if (!phoneRegex.test(phoneInput.value.trim())) {
                        e.preventDefault();
                        showToast('Please enter a valid phone number', 'warning');
                        phoneInput.focus();
                        return;
                    }
                }
            });
        }

        if (passwordForm) {
            passwordForm.addEventListener('submit', function(e) {
                const password = this.querySelector('input[name="password"]').value;
                const confirmPassword = this.querySelector('input[name="password_confirmation"]').value;

                if (password.length < 8) {
                    e.preventDefault();
                    showToast('Password must be at least 8 characters long', 'warning');
                    return;
                }

                if (password !== confirmPassword) {
                    e.preventDefault();
                    showToast('Passwords do not match', 'warning');
                    return;
                }
            });
        }

        // Toast notification function
        function showToast(message, type = 'info') {
            const toast = document.createElement('div');
            toast.className = `toast align-items-center text-white bg-${type} border-0 position-fixed bottom-0 end-0 m-3`;
            toast.setAttribute('role', 'alert');
            toast.setAttribute('aria-live', 'assertive');
            toast.setAttribute('aria-atomic', 'true');
            toast.innerHTML = `
                <div class="d-flex">
                    <div class="toast-body">
                        <i class="bi ${type === 'success' ? 'bi-check-circle' : 'bi-exclamation-triangle'} me-2"></i>
                        ${message}
                    </div>
                    <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
                </div>
            `;

            document.body.appendChild(toast);
            const bsToast = new bootstrap.Toast(toast);
            bsToast.show();

            toast.addEventListener('hidden.bs.toast', function () {
                toast.remove();
            });
        }

        // Auto-dismiss alerts after 5 seconds
        const alerts = document.querySelectorAll('.alert:not(.alert-dismissible)');
        alerts.forEach(alert => {
            setTimeout(() => {
                const bsAlert = new bootstrap.Alert(alert);
                bsAlert.close();
            }, 5000);
        });
    });
</script>
@endsection
