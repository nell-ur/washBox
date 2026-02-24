<div class="topbar d-flex justify-content-between align-items-center px-4 py-2 bg-white border-bottom shadow-sm">
    <div class="topbar-left d-flex align-items-center gap-4">
        {{-- Page Title --}}
        <div class="topbar-title fw-bold text-dark fs-5">
            {{ $pageTitle ?? 'Dashboard' }}
        </div>

        {{-- Integrated System Pulse (Objective C.5) --}}
        <div class="system-pulse-mini d-none d-md-flex align-items-center gap-3 ps-4 border-start">
            <div class="d-flex align-items-center gap-2" title="Database Status">
                <span class="pulse-dot {{ $stats['system_pulse']['db_connected'] ?? true ? 'bg-success' : 'bg-danger' }}"></span>
                <span class="x-small fw-bold text-muted uppercase">DB</span>
            </div>
            <div class="d-flex align-items-center gap-2" title="FCM Engine Status">
                <span class="pulse-dot {{ $stats['fcm_ready'] ?? true ? 'bg-success' : 'bg-warning' }}"></span>
                <span class="x-small fw-bold text-muted uppercase">FCM</span>
            </div>
        </div>
    </div>

    <div class="topbar-right d-flex align-items-center gap-3">
        {{-- Notification & User Menus --}}
        <x-notification-menu />
        <div class="vr mx-2 opacity-10"></div>
        <x-user-menu />
    </div>
</div>

<style>
    .topbar { min-height: 70px; }
    .x-small { font-size: 0.65rem; }
    .uppercase { text-transform: uppercase; letter-spacing: 0.5px; }

    /* Pulse Dot Styling for Real-time Monitoring */
    .pulse-dot {
        width: 8px;
        height: 8px;
        border-radius: 50%;
        display: inline-block;
        box-shadow: 0 0 0 rgba(25, 135, 84, 0.4);
        animation: pulse-animation 2s infinite;
    }

    @keyframes pulse-animation {
        0% { transform: scale(0.95); box-shadow: 0 0 0 0 rgba(25, 135, 84, 0.7); }
        70% { transform: scale(1); box-shadow: 0 0 0 6px rgba(25, 135, 84, 0); }
        100% { transform: scale(0.95); box-shadow: 0 0 0 0 rgba(25, 135, 84, 0); }
    }
</style>
