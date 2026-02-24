{{-- Notification Bell Dropdown --}}
<div class="dropdown me-3">
    <button class="btn btn-link text-dark position-relative p-0" type="button" id="notificationDropdown"
            data-bs-toggle="dropdown" data-bs-auto-close="outside" aria-expanded="false">
        <i class="bi bi-bell fs-5"></i>
        {{-- Badge (hidden by default, shown when there are unread notifications) --}}
        <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger"
              id="notificationBadge" style="display: none; font-size: 0.65rem;">
            0
        </span>
    </button>

    <div class="dropdown-menu dropdown-menu-end shadow-lg border-0 rounded-3 p-0" style="width: 360px;">
        {{-- Header --}}
        <div class="d-flex justify-content-between align-items-center px-3 py-2 border-bottom bg-light rounded-top">
            <h6 class="mb-0 fw-bold"><i class="bi bi-bell me-2"></i>Notifications</h6>
            <button class="btn btn-sm btn-link text-decoration-none p-0 text-primary" onclick="markAllNotificationsRead()">
                Mark all read
            </button>
        </div>

        {{-- Notifications List --}}
        <div class="notification-dropdown-list" id="notificationDropdownList" style="max-height: 350px; overflow-y: auto;">
            {{-- Loading state --}}
            <div class="text-center py-4 text-muted" id="notificationLoading">
                <div class="spinner-border spinner-border-sm me-2" role="status"></div>
                <small>Loading...</small>
            </div>
        </div>

        {{-- Footer --}}
        <div class="border-top px-3 py-2 text-center bg-light rounded-bottom">
            <a href="{{ route('admin.notifications.index') }}" class="text-decoration-none small fw-semibold">
                View All Notifications <i class="bi bi-arrow-right"></i>
            </a>
        </div>
    </div>
</div>
