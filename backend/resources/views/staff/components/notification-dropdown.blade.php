
<!-- Notification Bell -->
<div class="notification-wrapper">
    <button class="notification-bell" type="button" id="notificationDropdown"
        data-bs-toggle="dropdown" data-bs-auto-close="outside" aria-expanded="false"
        aria-label="Notifications">
        <i class="bi bi-bell"></i>
        <span class="notification-badge" id="notificationBadge" style="display: none;">0</span>
    </button>

    <!-- Notifications Dropdown -->
    <div class="dropdown-menu dropdown-menu-end notification-dropdown rounded-4 p-0 shadow-lg"
        aria-labelledby="notificationDropdown">

        {{-- Header --}}
        <div class="notification-header p-3 border-bottom d-flex justify-content-between align-items-center">
            <h6 class="mb-0 fw-bold">
                <i class="bi bi-bell-fill me-2 text-primary"></i>Notifications
            </h6>
            <button class="btn btn-sm btn-link text-decoration-none p-0" id="markAllReadBtn" style="display: none;">
                <small><i class="bi bi-check-all me-1"></i>Mark all read</small>
            </button>
        </div>

        {{-- Notifications Container --}}
        <div class="notifications-list" id="notificationsList">
            {{-- Loading State --}}
            <div class="notification-loading text-center py-4" id="notificationLoading">
                <div class="spinner-border spinner-border-sm text-primary" role="status">
                    <span class="visually-hidden">Loading...</span>
                </div>
                <p class="text-muted small mt-2 mb-0">Loading notifications...</p>
            </div>

            {{-- Empty State --}}
            <div class="notification-empty text-center py-4" id="notificationEmpty" style="display: none;">
                <i class="bi bi-bell-slash text-muted" style="font-size: 2rem;"></i>
                <p class="text-muted small mt-2 mb-0">No new notifications</p>
            </div>

            {{-- Notifications will be loaded here --}}
            <div id="notificationContent"></div>
        </div>

        {{-- Footer --}}
        <div class="notification-footer border-top p-2 text-center">
            <a href="{{ route('staff.notifications.index') }}"
               class="btn btn-sm btn-link text-decoration-none fw-semibold">
                View All Notifications <i class="bi bi-arrow-right ms-1"></i>
            </a>
        </div>
    </div>
</div>

{{-- ====================================================================== --}}
{{-- ADD THESE STYLES to your existing <style> section --}}
{{-- ====================================================================== --}}
<style>
    /* Notification Dropdown */
    .notification-dropdown {
        width: 380px;
        max-height: 500px;
        border: none !important;
        overflow: hidden;
    }

    .notification-header {
        background: linear-gradient(135deg, var(--primary-color), var(--accent-color));
        color: white;
    }

    .notification-header h6 {
        color: white;
    }

    .notification-header .btn-link {
        color: rgba(255, 255, 255, 0.9) !important;
    }

    .notification-header .btn-link:hover {
        color: white !important;
    }

    .notifications-list {
        max-height: 350px;
        overflow-y: auto;
    }

    .notifications-list::-webkit-scrollbar {
        width: 6px;
    }

    .notifications-list::-webkit-scrollbar-track {
        background: transparent;
    }

    .notifications-list::-webkit-scrollbar-thumb {
        background: var(--border-color);
        border-radius: 3px;
    }

    .notification-item-dropdown {
        display: flex;
        align-items: flex-start;
        gap: 12px;
        padding: 12px 16px;
        border-bottom: 1px solid var(--border-color);
        transition: all 0.2s ease;
        cursor: pointer;
        text-decoration: none;
        color: inherit;
    }

    .notification-item-dropdown:hover {
        background: var(--bg-color);
        text-decoration: none;
        color: inherit;
    }

    .notification-item-dropdown.unread {
        background: rgba(var(--bs-primary-rgb), 0.05);
        border-left: 3px solid var(--primary-color);
    }

    .notification-icon-sm {
        width: 40px;
        height: 40px;
        border-radius: 10px;
        display: flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
    }

    .notification-content {
        flex: 1;
        min-width: 0;
    }

    .notification-title {
        font-weight: 600;
        font-size: 0.875rem;
        color: var(--text-primary);
        margin-bottom: 2px;
        display: -webkit-box;
        -webkit-line-clamp: 1;
        -webkit-box-orient: vertical;
        overflow: hidden;
    }

    .notification-message {
        font-size: 0.8rem;
        color: var(--text-secondary);
        margin-bottom: 4px;
        display: -webkit-box;
        -webkit-line-clamp: 2;
        -webkit-box-orient: vertical;
        overflow: hidden;
    }

    .notification-time {
        font-size: 0.7rem;
        color: var(--text-muted);
    }

    .notification-footer {
        background: var(--bg-color);
    }

    /* Responsive */
    @media (max-width: 768px) {
        .notification-dropdown {
            position: fixed !important;
            top: 70px !important;
            left: 50% !important;
            transform: translateX(-50%) !important;
            right: auto !important;
            width: calc(100vw - 2rem);
            max-width: 380px;
        }
    }
</style>

{{-- ====================================================================== --}}
{{-- ADD THIS SCRIPT before </body> or in @push('scripts') --}}
{{-- ====================================================================== --}}
<script>
    // Staff Notification System
    class StaffNotificationSystem {
        constructor() {
            this.badge = document.getElementById('notificationBadge');
            this.content = document.getElementById('notificationContent');
            this.loading = document.getElementById('notificationLoading');
            this.empty = document.getElementById('notificationEmpty');
            this.markAllBtn = document.getElementById('markAllReadBtn');
            this.dropdown = document.getElementById('notificationDropdown');
            this.pollInterval = 30000; // 30 seconds
            this.csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

            this.init();
        }

        init() {
            // Initial load of unread count
            this.fetchUnreadCount();

            // Fetch notifications when dropdown opens
            if (this.dropdown) {
                this.dropdown.addEventListener('show.bs.dropdown', () => {
                    this.fetchNotifications();
                });
            }

            // Mark all read button
            if (this.markAllBtn) {
                this.markAllBtn.addEventListener('click', (e) => {
                    e.preventDefault();
                    this.markAllAsRead();
                });
            }

            // Poll for new notifications
            setInterval(() => this.fetchUnreadCount(), this.pollInterval);
        }

        async fetchUnreadCount() {
            try {
                const response = await fetch('{{ route("staff.notifications.unread-count") }}', {
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                    }
                });

                if (response.ok) {
                    const data = await response.json();
                    this.updateBadge(data.count);
                }
            } catch (error) {
                console.error('Error fetching notification count:', error);
            }
        }

        async fetchNotifications() {
            this.showLoading();

            try {
                const response = await fetch('{{ route("staff.notifications.recent") }}?limit=10', {
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                    }
                });

                if (response.ok) {
                    const data = await response.json();
                    this.renderNotifications(data.notifications);
                }
            } catch (error) {
                console.error('Error fetching notifications:', error);
                this.showEmpty();
            }
        }

        updateBadge(count) {
            if (this.badge) {
                if (count > 0) {
                    this.badge.textContent = count > 99 ? '99+' : count;
                    this.badge.style.display = 'flex';
                    if (this.markAllBtn) {
                        this.markAllBtn.style.display = 'block';
                    }
                } else {
                    this.badge.style.display = 'none';
                    if (this.markAllBtn) {
                        this.markAllBtn.style.display = 'none';
                    }
                }
            }
        }

        showLoading() {
            if (this.loading) this.loading.style.display = 'block';
            if (this.empty) this.empty.style.display = 'none';
            if (this.content) this.content.innerHTML = '';
        }

        showEmpty() {
            if (this.loading) this.loading.style.display = 'none';
            if (this.empty) this.empty.style.display = 'block';
            if (this.content) this.content.innerHTML = '';
        }

        getIconBgColor(color) {
            const colors = {
                'primary': 'rgba(61, 59, 107, 0.1)',
                'success': 'rgba(16, 185, 129, 0.1)',
                'warning': 'rgba(245, 158, 11, 0.1)',
                'danger': 'rgba(239, 68, 68, 0.1)',
                'info': 'rgba(59, 130, 246, 0.1)',
                'secondary': 'rgba(107, 114, 128, 0.1)',
            };
            return colors[color] || colors['secondary'];
        }

        renderNotifications(notifications) {
            if (this.loading) this.loading.style.display = 'none';

            if (!notifications || notifications.length === 0) {
                this.showEmpty();
                return;
            }

            if (this.empty) this.empty.style.display = 'none';

            const html = notifications.map(notification => `
                <a href="${notification.url}"
                   class="notification-item-dropdown ${!notification.is_read ? 'unread' : ''}"
                   data-id="${notification.id}"
                   onclick="staffNotifications.markAsRead(${notification.id})">
                    <div class="notification-icon-sm" style="background: ${this.getIconBgColor(notification.color)};">
                        <i class="bi ${notification.icon} text-${notification.color}"></i>
                    </div>
                    <div class="notification-content">
                        <div class="notification-title">
                            ${notification.title}
                            ${!notification.is_read ? '<span class="badge bg-primary ms-1" style="font-size: 0.6rem;">NEW</span>' : ''}
                        </div>
                        <div class="notification-message">${notification.message}</div>
                        <div class="notification-time">
                            <i class="bi bi-clock me-1"></i>${notification.created_at}
                        </div>
                    </div>
                </a>
            `).join('');

            if (this.content) this.content.innerHTML = html;
        }

        async markAsRead(id) {
            try {
                const response = await fetch(`{{ url('staff/notifications') }}/${id}/read`, {
                    method: 'POST',
                    headers: {
                        'Accept': 'application/json',
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': this.csrfToken,
                        'X-Requested-With': 'XMLHttpRequest',
                    }
                });

                if (response.ok) {
                    // Update UI
                    const item = document.querySelector(`[data-id="${id}"]`);
                    if (item) {
                        item.classList.remove('unread');
                        const badge = item.querySelector('.badge');
                        if (badge) badge.remove();
                    }
                    this.fetchUnreadCount();
                }
            } catch (error) {
                console.error('Error marking notification as read:', error);
            }
        }

        async markAllAsRead() {
            try {
                const response = await fetch('{{ route("staff.notifications.mark-all-read") }}', {
                    method: 'POST',
                    headers: {
                        'Accept': 'application/json',
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': this.csrfToken,
                        'X-Requested-With': 'XMLHttpRequest',
                    }
                });

                if (response.ok) {
                    // Update UI
                    document.querySelectorAll('.notification-item-dropdown.unread').forEach(item => {
                        item.classList.remove('unread');
                        const badge = item.querySelector('.badge');
                        if (badge) badge.remove();
                    });
                    this.updateBadge(0);
                }
            } catch (error) {
                console.error('Error marking all as read:', error);
            }
        }
    }

    // Initialize notification system when DOM is ready
    let staffNotifications;
    document.addEventListener('DOMContentLoaded', () => {
        staffNotifications = new StaffNotificationSystem();
    });
</script>
