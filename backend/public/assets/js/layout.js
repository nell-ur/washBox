window.toggleServicesDropdown = function(element) {
    console.log('Dropdown clicked', element);

    // Get the dropdown menu (next element)
    const dropdownMenu = element.nextElementSibling;
    const arrow = element.querySelector('.dropdown-arrow');

    if (!dropdownMenu) {
        console.error('Dropdown menu not found');
        return false;
    }

    // Prevent event from bubbling
    if (event) {
        event.preventDefault();
        event.stopPropagation();
    }

    // Toggle the show class
    dropdownMenu.classList.toggle('show');

    // Toggle arrow rotation
    if (arrow) {
        arrow.classList.toggle('rotated');
    }

    // Log state for debugging
    console.log('Dropdown state:', dropdownMenu.classList.contains('show') ? 'open' : 'closed');

    return false;
};

// ============================================
// BASE LAYOUT MANAGER
// ============================================
class LayoutManager {
    constructor(role = 'admin') {
        this.role = role;
        this.sidebar = document.getElementById('sidebar');
        this.mainContent = document.getElementById('mainContent');
        this.mobileMenuToggle = document.getElementById('mobileMenuToggle');
        this.sidebarOverlay = document.getElementById('sidebarOverlay');
        this.sidebarToggleBtn = document.getElementById('desktopToggleBtn');
        this.themeToggle = document.getElementById('themeToggle');

        this.sidebarStateKey = role === 'staff' ? 'staffSidebarCollapsed' : 'sidebarCollapsed';
        this.themeKey = 'theme';

        this.init();
    }

    init() {
        this.initSidebar();
        this.initTheme();
        this.initEventListeners();
    }

    // ============================================
    // SIDEBAR MANAGEMENT
    // ============================================
    initSidebar() {
        this.isCollapsed = localStorage.getItem(this.sidebarStateKey) === 'true';

        if (window.innerWidth <= 768) {
            this.sidebar?.classList.add('hide-mobile');
        } else {
            this.applySidebarState();
        }

        this.setupSidebarEvents();
        this.initSidebarTooltips();
    }

    applySidebarState() {
        if (this.isCollapsed) {
            this.sidebar?.classList.add('collapsed');
            this.mainContent?.classList.add('expanded');
        } else {
            this.sidebar?.classList.remove('collapsed');
            this.mainContent?.classList.remove('expanded');
        }
    }

    setupSidebarEvents() {
        // Mobile menu toggle
        this.mobileMenuToggle?.addEventListener('click', () => this.toggleMobileSidebar());

        // Desktop toggle
        this.sidebarToggleBtn?.addEventListener('click', () => this.toggleDesktopSidebar());

        // Overlay click
        this.sidebarOverlay?.addEventListener('click', () => this.closeMobileSidebar());

        // Close sidebar when clicking a link on mobile
        document.querySelectorAll('.sidebar-menu a').forEach(link => {
            link.addEventListener('click', () => {
                if (window.innerWidth <= 768) {
                    this.closeMobileSidebar();
                }
            });
        });

        // Handle resize
        window.addEventListener('resize', () => this.handleResize());
    }

    toggleMobileSidebar() {
        const isOpening = !this.sidebar.classList.contains('show-mobile');
        this.sidebar.classList.toggle('show-mobile');
        this.sidebar.classList.toggle('hide-mobile');
        this.sidebarOverlay.classList.toggle('show');
        document.body.style.overflow = isOpening ? 'hidden' : '';

        // Animate hamburger → X
        this.mobileMenuToggle?.classList.toggle('is-active', isOpening);
        const icon = this.mobileMenuToggle?.querySelector('i');
        if (icon) {
            icon.classList.toggle('bi-list', !isOpening);
            icon.classList.toggle('bi-x-lg', isOpening);
        }

        this.mobileMenuToggle?.setAttribute('aria-expanded', isOpening.toString());

        if (isOpening) {
            setTimeout(() => {
                const firstFocusable = this.sidebar.querySelector('a, button');
                firstFocusable?.focus();
            }, 100);
        }
    }

    closeMobileSidebar() {
        this.sidebar.classList.remove('show-mobile');
        this.sidebar.classList.add('hide-mobile');
        this.sidebarOverlay.classList.remove('show');
        document.body.style.overflow = '';

        // Reset mobile button back to hamburger
        this.mobileMenuToggle?.classList.remove('is-active');
        const icon = this.mobileMenuToggle?.querySelector('i');
        if (icon) {
            icon.classList.remove('bi-x-lg');
            icon.classList.add('bi-list');
        }

        this.mobileMenuToggle?.setAttribute('aria-expanded', 'false');
    }

    toggleDesktopSidebar() {
        if (window.innerWidth > 768) {
            this.sidebar.classList.toggle('collapsed');
            this.mainContent.classList.toggle('expanded');

            this.isCollapsed = this.sidebar.classList.contains('collapsed');
            localStorage.setItem(this.sidebarStateKey, this.isCollapsed.toString());

            this.initSidebarTooltips();

            // Dispatch event for other components
            window.dispatchEvent(new CustomEvent('sidebarToggle', {
                detail: { isCollapsed: this.isCollapsed, role: this.role }
            }));

            // Close all dropdowns when sidebar collapses
            if (this.isCollapsed) {
                document.querySelectorAll('.dropdown-menu-items.show').forEach(dropdown => {
                    dropdown.classList.remove('show');
                });
                document.querySelectorAll('.dropdown-arrow.rotated').forEach(arrow => {
                    arrow.classList.remove('rotated');
                });
            }
        }
    }

    initSidebarTooltips() {
        // Remove existing tooltips
        const tooltips = bootstrap.Tooltip.getInstance(this.sidebar);
        if (tooltips) tooltips.dispose();

        if (this.sidebar?.classList.contains('collapsed')) {
            document.querySelectorAll('.sidebar-menu a').forEach(item => {
                // Skip dropdown toggles in collapsed mode
                if (item.classList.contains('dropdown-toggle')) return;

                const label = item.querySelector('.menu-text')?.textContent?.trim();
                if (label) {
                    item.setAttribute('data-bs-toggle', 'tooltip');
                    item.setAttribute('data-bs-placement', 'right');
                    item.setAttribute('data-bs-title', label);
                    item.setAttribute('data-bs-custom-class', 'sidebar-tooltip');
                }
            });
        } else {
            document.querySelectorAll('.sidebar-menu a').forEach(item => {
                item.removeAttribute('data-bs-toggle');
                item.removeAttribute('data-bs-placement');
                item.removeAttribute('data-bs-title');
            });
        }

        // Initialize new tooltips
        const tooltipTriggerList = [].slice.call(
            document.querySelectorAll('[data-bs-toggle="tooltip"]')
        );
        tooltipTriggerList.forEach(tooltipTriggerEl => {
            new bootstrap.Tooltip(tooltipTriggerEl, {
                delay: { show: 300, hide: 0 }
            });
        });
    }

    handleResize() {
        if (window.innerWidth <= 768) {
            this.closeMobileSidebar();
            this.mainContent?.classList.add('full-width');
        } else {
            this.mainContent?.classList.remove('full-width');
            this.applySidebarState();
        }

        if (this.sidebar?.classList.contains('collapsed')) {
            this.initSidebarTooltips();
        }
    }

    // ============================================
    // THEME MANAGEMENT
    // ============================================
    initTheme() {
        this.currentTheme = localStorage.getItem(this.themeKey) || 'light';
        this.applyTheme();

        this.themeToggle?.addEventListener('click', () => this.toggleTheme());
    }

    applyTheme() {
        document.documentElement.setAttribute('data-theme', this.currentTheme);
        localStorage.setItem(this.themeKey, this.currentTheme);

        const themeIcon = this.themeToggle?.querySelector('.bi-sun, .bi-moon');
        if (themeIcon) {
            themeIcon.style.transition = 'opacity 0.3s, transform 0.3s';
        }
    }

    toggleTheme() {
        this.currentTheme = this.currentTheme === 'light' ? 'dark' : 'light';
        this.applyTheme();

        document.body.classList.add('theme-transitioning');
        setTimeout(() => {
            document.body.classList.remove('theme-transitioning');
        }, 300);
    }

    // ============================================
    // EVENT LISTENERS
    // ============================================
    initEventListeners() {
        // Escape key handler
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') {
                if (window.innerWidth <= 768) {
                    this.closeMobileSidebar();
                }

                // Close any open dropdowns
                document.querySelectorAll('.dropdown-menu-items.show').forEach(dropdown => {
                    dropdown.classList.remove('show');
                });
                document.querySelectorAll('.dropdown-arrow.rotated').forEach(arrow => {
                    arrow.classList.remove('rotated');
                });
            }
        });

        // Handle resize with debounce
        let resizeTimer;
        window.addEventListener('resize', () => {
            clearTimeout(resizeTimer);
            resizeTimer = setTimeout(() => this.handleResize(), 250);
        });

        // Logout confirmation
        const logoutForm = document.getElementById('logoutForm');
        if (logoutForm) {
            logoutForm.addEventListener('submit', (e) => {
                if (!confirm('Are you sure you want to logout?')) {
                    e.preventDefault();
                }
            });
        }
    }
}

// ============================================
// ADMIN DASHBOARD CLASS (from app.blade.php)
// ============================================
class AdminDashboard extends LayoutManager {
    constructor() {
        super('admin');
        this.initNotifications();
        this.initAccessibility();
    }

    initNotifications() {
        this.notificationBadge = document.getElementById('notificationBadge');
        this.notificationList = document.getElementById('notificationDropdownList');

        const notificationDropdown = document.getElementById('notificationDropdown');
        if (notificationDropdown) {
            notificationDropdown.addEventListener('show.bs.dropdown', () => {
                this.loadNotifications();
            });
        }

        this.loadNotifications();

        this.notificationInterval = setInterval(() => {
            this.loadNotifications(false);
        }, 30000);
    }

    async loadNotifications(showLoading = true) {
        if (showLoading) {
            this.showNotificationSkeleton();
        }

        try {
            const response = await this.fetchWithCSRF('/admin/notifications/recent');
            if (!response.ok) throw new Error('Failed to load notifications');

            const data = await response.json();
            this.updateNotificationBadge(data.unread_count);
            this.renderNotifications(data.notifications);
        } catch (error) {
            console.error('Notification error:', error);
            this.showNotificationError();
        }
    }

    showNotificationSkeleton() {
        if (!this.notificationList) return;

        this.notificationList.innerHTML = `
            <div class="notification-skeleton">
                ${Array(3).fill(`
                    <div class="d-flex align-items-center p-3">
                        <div class="skeleton-circle"></div>
                        <div class="ms-3 w-100">
                            <div class="skeleton-line"></div>
                            <div class="skeleton-line short"></div>
                        </div>
                    </div>
                `).join('')}
            </div>
        `;
    }

    showNotificationError() {
        if (!this.notificationList) return;

        this.notificationList.innerHTML = `
            <div class="notification-empty">
                <i class="bi bi-wifi-off fs-3 d-block mb-2"></i>
                <div class="fw-semibold">Connection Error</div>
                <small>Unable to load notifications</small>
                <button class="btn btn-sm btn-outline-primary mt-2" onclick="window.adminDashboard.loadNotifications()">
                    <i class="bi bi-arrow-clockwise"></i> Retry
                </button>
            </div>
        `;
    }

    updateNotificationBadge(count) {
        if (!this.notificationBadge) return;

        if (count > 0) {
            this.notificationBadge.textContent = count > 99 ? '99+' : count;
            this.notificationBadge.style.display = 'flex';

            if (count > parseInt(this.notificationBadge.textContent || 0)) {
                this.notificationBadge.classList.add('pulse');
                setTimeout(() => {
                    this.notificationBadge.classList.remove('pulse');
                }, 1000);
            }
        } else {
            this.notificationBadge.style.display = 'none';
        }
    }

    renderNotifications(notifications) {
        if (!this.notificationList) return;

        if (!notifications || notifications.length === 0) {
            this.notificationList.innerHTML = `
                <div class="notification-empty">
                    <i class="bi bi-check-circle fs-1 text-success d-block mb-2"></i>
                    <div class="fw-semibold">All caught up!</div>
                    <small>No new notifications</small>
                </div>
            `;
            return;
        }

        const html = notifications.map(n => {
            const unreadClass = !n.is_read ? 'unread' : '';
            const iconClass = this.getNotificationIcon(n.type);
            const timeAgo = this.formatTimeAgo(n.created_at);

            return `
                <a href="${n.link || '#'}" class="notification-item ${unreadClass}"
                   onclick="window.adminDashboard.markNotificationRead(${n.id}, event)">
                    <div class="notification-icon ${iconClass.color}">
                        <i class="bi ${iconClass.icon}"></i>
                    </div>
                    <div class="notification-content">
                        <div class="notification-title">
                            <span>${this.escapeHtml(n.title)}</span>
                            ${!n.is_read ? '<span class="badge bg-primary" style="font-size: 0.6rem;">NEW</span>' : ''}
                        </div>
                        <div class="notification-message">${this.escapeHtml(n.message)}</div>
                        <div class="notification-time">
                            <i class="bi bi-clock me-1"></i>${timeAgo}
                        </div>
                    </div>
                    <div class="notification-actions-inline">
                        <button class="btn btn-sm btn-link text-danger p-0"
                                onclick="window.adminDashboard.deleteNotification(${n.id}, event)">
                            <i class="bi bi-trash"></i>
                        </button>
                    </div>
                </a>
            `;
        }).join('');

        this.notificationList.innerHTML = html;
    }

    getNotificationIcon(type) {
        const icons = {
            'info': { icon: 'bi-info-circle', color: 'text-info' },
            'success': { icon: 'bi-check-circle', color: 'text-success' },
            'warning': { icon: 'bi-exclamation-triangle', color: 'text-warning' },
            'danger': { icon: 'bi-x-circle', color: 'text-danger' },
            'default': { icon: 'bi-bell', color: 'text-primary' }
        };
        return icons[type] || icons.default;
    }

    formatTimeAgo(dateString) {
        const date = new Date(dateString);
        const now = new Date();
        const diffMs = now - date;
        const diffMins = Math.floor(diffMs / 60000);
        const diffHours = Math.floor(diffMs / 3600000);
        const diffDays = Math.floor(diffMs / 86400000);

        if (diffMins < 1) return 'Just now';
        if (diffMins < 60) return `${diffMins}m ago`;
        if (diffHours < 24) return `${diffHours}h ago`;
        if (diffDays < 7) return `${diffDays}d ago`;
        return date.toLocaleDateString();
    }

    async markNotificationRead(id, event) {
        if (event) {
            event.preventDefault();
            event.stopPropagation();
        }

        try {
            await this.fetchWithCSRF(`/admin/notifications/${id}/mark-read`, {
                method: 'POST'
            });

            const notificationItem = document.querySelector(`.notification-item[onclick*="${id}"]`);
            if (notificationItem) {
                notificationItem.classList.remove('unread');
                const badge = notificationItem.querySelector('.badge');
                if (badge) badge.remove();
            }

            setTimeout(() => this.loadNotifications(false), 300);

            if (event?.currentTarget?.href && event.currentTarget.href !== '#') {
                window.location.href = event.currentTarget.href;
            }
        } catch (error) {
            console.error('Error marking notification as read:', error);
            this.showToast('Failed to mark notification as read', 'error');
        }
    }

    async markAllNotificationsRead() {
        try {
            const response = await this.fetchWithCSRF('/admin/notifications/mark-all-read', {
                method: 'POST'
            });

            if (response.ok) {
                await this.loadNotifications(false);
                this.showToast('All notifications marked as read', 'success');

                setTimeout(() => {
                    window.location.reload();
                }, 1000);
            } else {
                throw new Error('Failed to mark all notifications as read');
            }
        } catch (error) {
            console.error('Error marking all notifications as read:', error);
            this.showToast('Failed to mark all notifications as read', 'error');
        }
    }

    async fetchWithCSRF(url, options = {}) {
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

        return fetch(url, {
            ...options,
            headers: {
                'X-CSRF-TOKEN': csrfToken,
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                ...options.headers
            }
        });
    }

    escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    showToast(message, type = 'info') {
        const toast = document.createElement('div');
        toast.className = `custom-toast ${type}`;
        toast.innerHTML = `
            <i class="bi ${this.getToastIcon(type)}"></i>
            <div class="toast-content">
                <div class="toast-message">${this.escapeHtml(message)}</div>
            </div>
            <button class="btn btn-sm btn-link p-0 ms-auto" onclick="this.parentElement.remove()">
                <i class="bi bi-x"></i>
            </button>
        `;

        const container = document.getElementById('toastContainer') || document.body;
        container.appendChild(toast);

        setTimeout(() => {
            if (toast.parentElement) {
                toast.style.opacity = '0';
                setTimeout(() => toast.remove(), 300);
            }
        }, 5000);
    }

    getToastIcon(type) {
        const icons = {
            'success': 'bi-check-circle',
            'error': 'bi-x-circle',
            'warning': 'bi-exclamation-triangle',
            'info': 'bi-info-circle'
        };
        return icons[type] || icons.info;
    }

    initAccessibility() {
        const skipLink = document.createElement('a');
        skipLink.href = '#mainContent';
        skipLink.className = 'skip-to-content';
        skipLink.innerHTML = 'Skip to main content';
        document.body.insertBefore(skipLink, document.body.firstChild);

        this.initFocusTraps();
    }

    initFocusTraps() {
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Tab' && document.querySelector('.dropdown-menu.show')) {
                this.trapFocus(e);
            }
        });
    }

    trapFocus(e) {
        const dropdown = document.querySelector('.dropdown-menu.show');
        if (!dropdown) return;

        const focusable = dropdown.querySelectorAll('button, [href], input, select, textarea, [tabindex]:not([tabindex="-1"])');
        if (focusable.length === 0) return;

        const firstFocusable = focusable[0];
        const lastFocusable = focusable[focusable.length - 1];

        if (e.shiftKey && document.activeElement === firstFocusable) {
            e.preventDefault();
            lastFocusable.focus();
        } else if (!e.shiftKey && document.activeElement === lastFocusable) {
            e.preventDefault();
            firstFocusable.focus();
        }
    }

   handleKeydown(e) {
        if (e.key === 'Escape') {
            if (window.innerWidth <= 768) {
                this.closeMobileSidebar();
            } else {
                this.sidebar?.classList.remove('collapsed');
                this.mainContent?.classList.remove('expanded');
                localStorage.setItem(this.sidebarStateKey, 'false');
                this.isCollapsed = false;
            }
        }
    }

    // ← ADD deleteNotification HERE (still inside the class)
    async deleteNotification(id, event) {
        if (event) {
            event.preventDefault();
            event.stopPropagation();
        }

        try {
            await this.fetchWithCSRF(`/admin/notifications/${id}`, {
                method: 'DELETE'
            });

            const notificationItem = document.querySelector(`.notification-item[onclick*="${id}"]`);
            if (notificationItem) {
                notificationItem.remove();
            }

            setTimeout(() => this.loadNotifications(false), 300);
            this.showToast('Notification deleted', 'success');
        } catch (error) {
            console.error('Error deleting notification:', error);
            this.showToast('Failed to delete notification', 'error');
        }
    }

}
// ============================================
// STAFF NOTIFICATION SYSTEM (from staff.blade.php)
// ============================================
class StaffNotificationSystem {
    constructor() {
        this.badge = document.getElementById('notificationBadge');
        this.menuBadge = document.querySelector('.notification-menu-badge');
        this.list = document.getElementById('notificationList');
        this.content = document.getElementById('notificationContent');
        this.loading = document.getElementById('notificationLoading');
        this.empty = document.getElementById('notificationEmpty');
        this.markAllBtn = document.getElementById('markAllReadBtn');
        this.dropdown = document.getElementById('notificationDropdown');
        this.pollInterval = 30000;
        this.csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;

        this.init();
    }

    init() {
        this.fetchUnreadCount();

        this.dropdown?.addEventListener('show.bs.dropdown', () => {
            this.fetchNotifications();
        });

        this.markAllBtn?.addEventListener('click', (e) => {
            e.preventDefault();
            e.stopPropagation();
            this.markAllAsRead();
        });

        setInterval(() => this.fetchUnreadCount(), this.pollInterval);
    }

    async fetchUnreadCount() {
        try {
            const response = await fetch('/staff/notifications/unread-count', {
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
            const response = await fetch('/staff/notifications/recent?limit=10', {
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
                if (this.markAllBtn) this.markAllBtn.style.display = 'block';
                if (this.menuBadge) {
                    this.menuBadge.textContent = count > 99 ? '99+' : count;
                    this.menuBadge.style.display = 'inline-block';
                }
            } else {
                this.badge.style.display = 'none';
                if (this.markAllBtn) this.markAllBtn.style.display = 'none';
                if (this.menuBadge) this.menuBadge.style.display = 'none';
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

    getNotificationIcon(type) {
        const icons = {
            'order_received': 'bi-bag-check',
            'order_ready': 'bi-check-circle',
            'order_completed': 'bi-trophy',
            'order_cancelled': 'bi-x-circle',
            'pickup_request': 'bi-truck',
            'pickup_accepted': 'bi-check2-circle',
            'pickup_completed': 'bi-box-seam',
            'payment_received': 'bi-credit-card',
            'unclaimed_reminder': 'bi-clock-history',
            'unclaimed_warning': 'bi-exclamation-triangle',
            'new_customer': 'bi-person-plus',
            'system': 'bi-gear',
            'announcement': 'bi-megaphone',
        };
        return icons[type] || 'bi-bell';
    }

    getNotificationColor(type) {
        const colors = {
            'order_received': 'primary',
            'order_ready': 'success',
            'order_completed': 'success',
            'order_cancelled': 'danger',
            'pickup_request': 'info',
            'pickup_accepted': 'primary',
            'pickup_completed': 'success',
            'payment_received': 'success',
            'unclaimed_reminder': 'warning',
            'unclaimed_warning': 'danger',
            'new_customer': 'info',
            'system': 'secondary',
            'announcement': 'primary',
        };
        return colors[type] || 'secondary';
    }

    renderNotifications(notifications) {
        if (this.loading) this.loading.style.display = 'none';

        if (!notifications || notifications.length === 0) {
            this.showEmpty();
            return;
        }

        if (this.empty) this.empty.style.display = 'none';

        const html = notifications.map(notification => {
            const icon = notification.icon || this.getNotificationIcon(notification.type);
            const color = notification.color || this.getNotificationColor(notification.type);
            const url = notification.url || '#';

            return `
                <a href="${url}"
                   class="notification-item ${!notification.is_read ? 'unread' : ''}"
                   data-id="${notification.id}"
                   onclick="window.staffNotifications.markAsRead(${notification.id})">
                    <div class="notification-icon" style="background: var(--bs-${color}-bg-subtle, rgba(var(--bs-${color}-rgb), 0.1));">
                        <i class="bi ${icon} text-${color}"></i>
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
            `;
        }).join('');

        if (this.content) this.content.innerHTML = html;
    }

    async markAsRead(id) {
        try {
            const response = await fetch(`/staff/notifications/${id}/read`, {
                method: 'POST',
                headers: {
                    'Accept': 'application/json',
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': this.csrfToken,
                    'X-Requested-With': 'XMLHttpRequest',
                }
            });

            if (response.ok) {
                const item = document.querySelector(`.notification-item[data-id="${id}"]`);
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
            const response = await fetch('/staff/notifications/mark-all-read', {
                method: 'POST',
                headers: {
                    'Accept': 'application/json',
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': this.csrfToken,
                    'X-Requested-With': 'XMLHttpRequest',
                }
            });

            if (response.ok) {
                document.querySelectorAll('.notification-item.unread').forEach(item => {
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

// ============================================
// STAFF DASHBOARD CLASS (from staff.blade.php)
// ============================================
class StaffDashboard extends LayoutManager {
    constructor() {
        super('staff');
        this.initStaffNotifications();
        this.initSidebarDropdowns();
    }

    initStaffNotifications() {
        window.staffNotifications = new StaffNotificationSystem();
    }

    initSidebarDropdowns() {
        // Initialize any pre-opened dropdowns based on active routes
        const activeDropdown = document.querySelector('.dropdown-container.active');
        if (activeDropdown) {
            const dropdownItems = activeDropdown.querySelector('.dropdown-menu-items');
            const arrow = activeDropdown.querySelector('.dropdown-arrow');
            if (dropdownItems && !dropdownItems.classList.contains('show')) {
                dropdownItems.classList.add('show');
                if (arrow) arrow.classList.add('rotated');
            }
        }
    }
}

// ============================================
// INITIALIZATION
// ============================================
document.addEventListener('DOMContentLoaded', () => {
    console.log('DOM fully loaded and parsed');

    // Determine which dashboard to initialize based on URL or data attribute
    const isAdmin = window.location.pathname.includes('/admin') ||
                    document.body.dataset.role === 'admin';

    if (isAdmin) {
        window.adminDashboard = new AdminDashboard();

        // Add admin-specific global functions
        window.markAllNotificationsRead = () => window.adminDashboard.markAllNotificationsRead();
        window.clearAllNotifications = () => {
            if (confirm('Are you sure you want to clear all notifications?')) {
                console.log('Clear all notifications');
            }
        };
    } else {
        window.staffDashboard = new StaffDashboard();
    }

    // ============================================
    // DROPDOWN FUNCTIONALITY
    // ============================================

    // Initialize dropdown toggles
    document.querySelectorAll('.dropdown-toggle').forEach(toggle => {
        console.log('Dropdown toggle found:', toggle);
    });

    // Close dropdown when clicking outside
    document.addEventListener('click', function(event) {
        const dropdowns = document.querySelectorAll('.dropdown-container');
        dropdowns.forEach(container => {
            if (!container.contains(event.target)) {
                const dropdownMenu = container.querySelector('.dropdown-menu-items');
                const arrow = container.querySelector('.dropdown-arrow');

                if (dropdownMenu && dropdownMenu.classList.contains('show')) {
                    dropdownMenu.classList.remove('show');
                    if (arrow) {
                        arrow.classList.remove('rotated');
                    }
                }
            }
        });
    });

    // Prevent closing when clicking inside dropdown
    document.querySelectorAll('.dropdown-menu-items').forEach(menu => {
        menu.addEventListener('click', function(event) {
            event.stopPropagation();
        });
    });

    // Handle submenu links
    document.querySelectorAll('.dropdown-menu-items .nav-link').forEach(link => {
        link.addEventListener('click', function(event) {
            event.stopPropagation();
        });
    });

    // Handle sidebar collapse state changes
    const sidebar = document.getElementById('sidebar');
    if (sidebar) {
        const observer = new MutationObserver(function(mutations) {
            mutations.forEach(function(mutation) {
                if (mutation.attributeName === 'class') {
                    // Close dropdowns when sidebar collapses
                    if (sidebar.classList.contains('collapsed')) {
                        document.querySelectorAll('.dropdown-menu-items.show').forEach(dropdown => {
                            dropdown.classList.remove('show');
                        });
                        document.querySelectorAll('.dropdown-arrow.rotated').forEach(arrow => {
                            arrow.classList.remove('rotated');
                        });
                    }
                }
            });
        });

        observer.observe(sidebar, { attributes: true });
    }

    // ============================================
    // ADDITIONAL STYLES
    // ============================================

    // Add any additional styles that were dynamically added
    const style = document.createElement('style');
    style.textContent = `
        .skip-to-content {
            position: absolute;
            top: -40px;
            left: 0;
            background: var(--primary-color);
            color: white;
            padding: 0.5rem 1rem;
            text-decoration: none;
            z-index: 9999;
            transition: top 0.3s ease;
        }
        .skip-to-content:focus {
            top: 0;
        }
        .sidebar-tooltip .tooltip-inner {
            background: var(--sidebar-bg);
            color: white;
            font-size: 0.75rem;
            padding: 0.5rem 0.75rem;
        }
        .sidebar-tooltip .tooltip-arrow {
            border-right-color: var(--sidebar-bg) !important;
        }
        .theme-transitioning * {
            transition: background-color 0.3s ease, color 0.3s ease, border-color 0.3s ease !important;
        }
        .custom-toast {
            position: fixed;
            bottom: 20px;
            right: 20px;
            background-color: var(--card-bg);
            color: var(--text-primary);
            border: 1px solid var(--border-color);
            padding: 1rem 1.25rem;
            border-radius: 0.5rem;
            box-shadow: var(--shadow-lg);
            display: flex;
            align-items: center;
            gap: 1rem;
            z-index: 10000;
            animation: slideInRight 0.3s ease;
        }
        @keyframes slideInRight {
            from {
                transform: translateX(100%);
                opacity: 0;
            }
            to {
                transform: translateX(0);
                opacity: 1;
            }
        }
    `;
    document.head.appendChild(style);
});
