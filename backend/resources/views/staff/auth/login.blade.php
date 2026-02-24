<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Staff Login - WashBox</title>

    <!-- Bootstrap CSS -->
    <link href="{{ asset('assets/bootstrap/css/bootstrap.min.css') }}" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="{{ asset('assets/bootstrap-icons/font/bootstrap-icons.css') }}">
    <!-- Shared Login CSS -->
    <link rel="stylesheet" href="{{ asset('assets/css/login.css') }}">
</head>
<body class="staff-bg">

<!-- Background Effects - Same as Admin but with staff colors -->
<div class="bubble-container"></div>
<div class="water-waves">
    <svg class="water-wave" viewBox="0 0 1200 120" preserveAspectRatio="none">
        <defs>
            <linearGradient id="waveGradient" x1="0%" y1="0%" x2="0%" y2="100%">
                <stop offset="0%" style="stop-color: rgba(16, 185, 129, 0.4)" />
                <stop offset="100%" style="stop-color: rgba(5, 150, 105, 0.2)" />
            </linearGradient>
        </defs>
        <path d="M0 60 Q 300 20, 600 60 T 1200 60 L 1200 120 L 0 120 Z" fill="url(#waveGradient)" opacity="0.6" />
        <path d="M0 70 Q 300 40, 600 70 T 1200 70 L 1200 120 L 0 120 Z" fill="rgba(16,185,129,0.2)" opacity="0.4" />
    </svg>
</div>
<div class="floating-particles"></div>

<div class="login-container">
    <!-- LEFT PANEL -->
    <div class="login-left">
        <div class="logo-container">
            <div class="logo-circle">
                <img src="{{ asset('images/logo.png') }}"
                     alt="WashBox Logo"
                     onerror="this.src='data:image/svg+xml,%3Csvg xmlns=\'http://www.w3.org/2000/svg\' viewBox=\'0 0 100 100\'%3E%3Ccircle cx=\'50\' cy=\'50\' r=\'50\' fill=\'%23059669\'/%3E%3Ctext x=\'50\' y=\'70\' font-size=\'40\' fill=\'white\' text-anchor=\'middle\' font-weight=\'bold\'%3EWB%3C/text%3E%3C/svg%3E';">
            </div>
            <h1 class="brand-name">WashBox</h1>
            <p class="brand-tagline">Staff Operations Portal</p>
        </div>

        <ul class="features-list">
            <li><i class="bi bi-shop"></i> Branch Operations Management</li>
            <li><i class="bi bi-speedometer2"></i> Daily Performance Dashboard</li>
            <li><i class="bi bi-graph-up"></i> Service & Order Management</li>
            <li><i class="bi bi-bar-chart"></i> Customer Service Tools</li>
            <li><i class="bi bi-people"></i> Team Collaboration Features</li>
            <li><i class="bi bi-truck"></i> Pickup & Delivery Management</li>
        </ul>

        <div class="role-badge">
            <i class="bi bi-person-badge"></i>
            <span>Staff Member Access</span>
        </div>
    </div>

    <!-- RIGHT PANEL -->
    <div class="login-right">
        <div class="login-header">
            <h3>Staff Portal</h3>
            <p>Sign in to your staff dashboard</p>
        </div>

        @if(session('error'))
            <div class="alert alert-danger">
                <i class="bi bi-exclamation-circle"></i>
                <span>{{ session('error') }}</span>
                <button type="button" class="btn-close"></button>
            </div>
        @endif

        @if(session('success'))
            <div class="alert alert-success">
                <i class="bi bi-check-circle"></i>
                <span>{{ session('success') }}</span>
                <button type="button" class="btn-close"></button>
            </div>
        @endif

        @if($errors->any())
            <div class="alert alert-danger">
                <i class="bi bi-exclamation-circle"></i>
                <span>{{ $errors->first() }}</span>
                <button type="button" class="btn-close"></button>
            </div>
        @endif

        <form method="POST" action="{{ route('staff.login') }}" id="staffLoginForm">
            @csrf

            <div class="form-group">
                <label class="form-label">Staff Email</label>
                <input type="email"
                       name="email"
                       class="form-control @error('email') is-invalid @enderror"
                       placeholder="staff@washbox.com"
                       value="{{ old('email') }}"
                       required
                       autocomplete="email"
                       autofocus>
                @error('email')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <div class="form-group">
                <label class="form-label">Password</label>
                <div class="password-wrapper">
                    <input type="password"
                           name="password"
                           id="password"
                           class="form-control @error('password') is-invalid @enderror"
                           placeholder="Enter your password"
                           required
                           autocomplete="current-password">
                    <button type="button" class="password-toggle">
                        <i class="bi bi-eye"></i>
                    </button>
                </div>
                @error('password')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <div class="d-flex justify-content-between align-items-center">
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" id="remember" name="remember">
                    <label class="form-check-label" for="remember">
                        Remember me
                    </label>
                </div>
                <a href="#" class="forgot-link">
                    Forgot Password?
                </a>
            </div>

            <button type="submit" class="btn-login">
                <i class="bi bi-box-arrow-in-right"></i>
                Staff Sign In
            </button>

            <div class="security-note">
                <i class="bi bi-shield-check text-success"></i>
                <span>Secure Login · 256-bit SSL Encrypted</span>
            </div>

            <div class="text-center mt-4">
                <p class="mb-0">
                    <span class="role-switch-link">Administrator?</span>
                    <a href="{{ route('admin.login') }}" class="role-switch-link fw-bold ms-1">
                        Go to Admin Login →
                    </a>
                </p>
            </div>
        </form>
    </div>
</div>

<!-- Scripts -->
<script src="{{ asset('assets/bootstrap/js/bootstrap.bundle.min.js') }}"></script>
<script src="{{ asset('assets/js/login.js') }}"></script>
</body>
</html>