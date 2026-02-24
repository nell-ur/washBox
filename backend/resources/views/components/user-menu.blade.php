<div class="dropdown">
    <div class="user-menu" data-bs-toggle="dropdown" style="cursor: pointer; display: flex; align-items: center; gap: 0.75rem;">
        <div class="user-avatar" style="width: 35px; height: 35px; background: #3D3B6B; color: white; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: bold;">
            {{ strtoupper(substr(auth()->user()->name, 0, 1)) }}
        </div>
        <div class="d-none d-md-block">
            <div style="font-weight: 600; font-size: 0.875rem; color: #111827;">{{ auth()->user()->name }}</div>
            <div style="font-size: 0.75rem; color: #6B7280;">
                {{ auth()->user()->role === 'admin' ? 'Administrator' : 'Staff' }}
            </div>
        </div>
    </div>
    <ul class="dropdown-menu dropdown-menu-end shadow border-0 mt-2">
        <li>
            <a class="dropdown-item" href="{{ auth()->user()->role === 'admin' ? route('admin.profile') : route('staff.profile') }}">
                <i class="bi bi-person me-2"></i> Profil
            </a>
        </li>
        <li><hr class="dropdown-divider"></li>
        <li class="px-2">
            <x-logout-button />
        </li>
    </ul>
</div>
