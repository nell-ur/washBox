@extends('admin.layouts.app')

@section('title', 'Notifications')
@section('page-title', 'Notifications')

@section('content')
<div class="container-fluid px-4 py-4">
    {{-- Header --}}
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="fw-bold mb-1">Notifications</h4>
            <p class="text-muted small mb-0">Stay updated with the latest activities</p>
        </div>
        <div class="d-flex gap-2">
            <form action="{{ route('admin.notifications.mark-all-read') }}" method="POST" class="d-inline">
                @csrf
                <button type="submit" class="btn btn-outline-primary shadow-sm">
                    <i class="bi bi-check-all me-1"></i>Mark All as Read
                </button>
            </form>
            <form action="{{ route('admin.notifications.delete-all-read') }}" method="POST" class="d-inline">
                @csrf
                @method('DELETE')
                <button type="submit" class="btn btn-outline-danger shadow-sm" onclick="return confirm('Delete all read notifications?')">
                    <i class="bi bi-trash me-1"></i>Clear Read
                </button>
            </form>
        </div>
    </div>

    {{-- Stats --}}
    <div class="row g-3 mb-4">
        <div class="col-md-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body p-4">
                    <div class="d-flex align-items-center">
                        <div class="bg-primary bg-opacity-10 p-3 rounded-3 me-3">
                            <i class="bi bi-bell fs-3 text-primary"></i>
                        </div>
                        <div>
                            <h6 class="text-muted mb-1 small">Total</h6>
                            <h3 class="fw-bold mb-0">{{ $stats['total'] }}</h3>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body p-4">
                    <div class="d-flex align-items-center">
                        <div class="bg-warning bg-opacity-10 p-3 rounded-3 me-3">
                            <i class="bi bi-envelope fs-3 text-warning"></i>
                        </div>
                        <div>
                            <h6 class="text-muted mb-1 small">Unread</h6>
                            <h3 class="fw-bold mb-0">{{ $stats['unread'] }}</h3>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body p-4">
                    <div class="d-flex align-items-center">
                        <div class="bg-success bg-opacity-10 p-3 rounded-3 me-3">
                            <i class="bi bi-calendar-check fs-3 text-success"></i>
                        </div>
                        <div>
                            <h6 class="text-muted mb-1 small">Today</h6>
                            <h3 class="fw-bold mb-0">{{ $stats['today'] }}</h3>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Filters --}}
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body">
            <form method="GET" class="row g-3 align-items-end">
                <div class="col-md-3">
                    <label class="form-label small text-muted mb-1 fw-semibold">Type</label>
                    <select name="type" class="form-select" onchange="this.form.submit()">
                        <option value="">All Types</option>
                        @foreach($types as $key => $label)
                            <option value="{{ $key }}" {{ request('type') == $key ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label small text-muted mb-1 fw-semibold">Status</label>
                    <select name="status" class="form-select" onchange="this.form.submit()">
                        <option value="">All Status</option>
                        <option value="unread" {{ request('status') == 'unread' ? 'selected' : '' }}>Unread</option>
                        <option value="read" {{ request('status') == 'read' ? 'selected' : '' }}>Read</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <a href="{{ route('admin.notifications.index') }}" class="btn btn-outline-secondary w-100">
                        <i class="bi bi-x-circle me-1"></i>Clear Filters
                    </a>
                </div>
            </form>
        </div>
    </div>

    {{-- Notifications List --}}
    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white border-bottom">
            <div class="d-flex justify-content-between align-items-center">
                <h6 class="mb-0 fw-bold">
                    <i class="bi bi-list-ul me-2 text-primary"></i>All Notifications
                </h6>
                <span class="badge bg-light text-dark border">{{ $notifications->total() }} total</span>
            </div>
        </div>
        <div class="card-body p-0">
            @forelse($notifications as $notification)
                <div class="notification-item d-flex align-items-start p-3 border-bottom {{ !$notification->is_read ? 'bg-light' : '' }}">
                    {{-- Icon --}}
                    <div class="rounded-circle d-flex align-items-center justify-content-center me-3 flex-shrink-0"
                         style="width: 48px; height: 48px; background: var(--bs-{{ $notification->color }}-bg-subtle, #e9ecef);">
                        <i class="bi {{ $notification->icon_class }} fs-5 text-{{ $notification->color }}"></i>
                    </div>

                    {{-- Content --}}
                    <div class="flex-grow-1">
                        <div class="d-flex justify-content-between align-items-start">
                            <div class="flex-grow-1">
                                <h6 class="mb-1 fw-semibold">
                                    {{ $notification->title }}
                                    @if(!$notification->is_read)
                                        <span class="badge bg-primary ms-2">New</span>
                                    @endif
                                </h6>
                                <p class="mb-2 text-muted small">{{ $notification->message }}</p>
                                <small class="text-muted">
                                    <i class="bi bi-clock me-1"></i>{{ $notification->time_ago }}
                                    @if($notification->branch)
                                        • <i class="bi bi-building me-1"></i>{{ $notification->branch->name }}
                                    @endif
                                </small>
                            </div>
                            <div class="d-flex gap-2 ms-3">
                                @if($notification->link)
                                    <a href="{{ $notification->link }}" class="btn btn-sm btn-outline-primary" title="View">
                                        <i class="bi bi-eye"></i>
                                    </a>
                                @endif
                                @if(!$notification->is_read)
                                    <form action="{{ route('admin.notifications.mark-read', $notification) }}" method="POST" class="d-inline">
                                        @csrf
                                        <button type="submit" class="btn btn-sm btn-outline-success" title="Mark as read">
                                            <i class="bi bi-check"></i>
                                        </button>
                                    </form>
                                @endif
                                <form action="{{ route('admin.notifications.destroy', $notification) }}" method="POST" class="d-inline">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-outline-danger" title="Delete" onclick="return confirm('Delete this notification?')">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            @empty
                <div class="text-center py-5">
                    <div class="py-4">
                        <i class="bi bi-bell-slash display-1 text-muted opacity-25"></i>
                        <h5 class="text-muted mt-3 mb-2">No notifications found</h5>
                        <p class="text-muted mb-0">You're all caught up!</p>
                    </div>
                </div>
            @endforelse
        </div>

        @if($notifications->hasPages())
            <div class="card-footer bg-white border-top">
                <div class="d-flex justify-content-between align-items-center">
                    <div class="text-muted small">
                        Showing {{ $notifications->firstItem() }} to {{ $notifications->lastItem() }} of {{ $notifications->total() }} notifications
                    </div>
                    <div>
                        {{ $notifications->links() }}
                    </div>
                </div>
            </div>
        @endif
    </div>
</div>
@endsection

@push('styles')
<style>
    /* Card Hover Effects */
    .card {
        border-radius: 12px;
        transition: transform 0.2s, box-shadow 0.2s;
    }

    .card:hover {
        transform: translateY(-2px);
        box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15) !important;
    }

    /* Notification Item Hover */
    .notification-item {
        transition: background-color 0.2s;
    }

    .notification-item:hover {
        background-color: rgba(13, 110, 253, 0.03) !important;
    }

    .notification-item.bg-light:hover {
        background-color: rgba(13, 110, 253, 0.08) !important;
    }

    /* Badge Styles */
    .badge {
        font-weight: 600;
        padding: 0.35em 0.65em;
    }

    /* Button Styles */
    .btn-sm {
        border-radius: 0.375rem;
    }

    /* Form Styles */
    .form-select,
    .form-control {
        border-radius: 0.5rem;
        border: 1px solid #dee2e6;
        transition: border-color 0.15s, box-shadow 0.15s;
    }

    .form-select:focus,
    .form-control:focus {
        border-color: #0d6efd;
        box-shadow: 0 0 0 0.25rem rgba(13, 110, 253, 0.25);
    }

    /* Notification Icon Background */
    .notification-item .rounded-circle {
        border: 2px solid rgba(0, 0, 0, 0.05);
    }
</style>
@endpush

