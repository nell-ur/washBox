@extends('staff.layouts.staff')

@section('title', 'Notifications')
@section('page-title', 'Notifications')
@section('page-icon', 'bi-bell')

@section('content')
<div class="container-fluid">
    {{-- Header --}}
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-3">
        <div>
            <h4 class="fw-bold mb-1">All Notifications</h4>
            <p class="text-muted small mb-0">Stay updated with your latest activities</p>
        </div>
        <div class="d-flex gap-2">
            @if($counts['unread'] > 0)
                <form action="{{ route('staff.notifications.mark-all-read') }}" method="POST" class="d-inline">
                    @csrf
                    <button type="submit" class="btn btn-outline-primary btn-sm">
                        <i class="bi bi-check-all me-1"></i> Mark All Read
                    </button>
                </form>
            @endif
            @if($counts['read'] > 0)
                <form action="{{ route('staff.notifications.delete-read') }}" method="POST" class="d-inline"
                      onsubmit="return confirm('Delete all read notifications?')">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-outline-danger btn-sm">
                        <i class="bi bi-trash me-1"></i> Clear Read
                    </button>
                </form>
            @endif
        </div>
    </div>

    {{-- Success/Error Messages --}}
    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show border-0 shadow-sm rounded-3 mb-4">
            <i class="bi bi-check-circle-fill me-2"></i>{{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    {{-- Filters --}}
    <div class="card border-0 shadow-sm rounded-4 mb-4">
        <div class="card-body py-3">
            <div class="row g-3 align-items-center">
                <div class="col-auto">
                    <span class="text-muted small fw-semibold">Filter:</span>
                </div>
                <div class="col-auto">
                    <div class="btn-group" role="group">
                        <a href="{{ route('staff.notifications.index') }}"
                           class="btn btn-sm {{ !request('status') ? 'btn-primary' : 'btn-outline-secondary' }}">
                            All <span class="badge bg-white text-dark ms-1">{{ $counts['all'] }}</span>
                        </a>
                        <a href="{{ route('staff.notifications.index', ['status' => 'unread']) }}"
                           class="btn btn-sm {{ request('status') === 'unread' ? 'btn-primary' : 'btn-outline-secondary' }}">
                            Unread <span class="badge bg-danger ms-1">{{ $counts['unread'] }}</span>
                        </a>
                        <a href="{{ route('staff.notifications.index', ['status' => 'read']) }}"
                           class="btn btn-sm {{ request('status') === 'read' ? 'btn-primary' : 'btn-outline-secondary' }}">
                            Read <span class="badge bg-secondary ms-1">{{ $counts['read'] }}</span>
                        </a>
                    </div>
                </div>
                @if($types->count() > 0)
                <div class="col-auto">
                    <select class="form-select form-select-sm" onchange="window.location.href=this.value" style="min-width: 150px;">
                        <option value="{{ route('staff.notifications.index', request()->except('type')) }}">All Types</option>
                        @foreach($types as $type)
                            <option value="{{ route('staff.notifications.index', array_merge(request()->all(), ['type' => $type])) }}"
                                    {{ request('type') === $type ? 'selected' : '' }}>
                                {{ ucwords(str_replace('_', ' ', $type)) }}
                            </option>
                        @endforeach
                    </select>
                </div>
                @endif
            </div>
        </div>
    </div>

    {{-- Notifications List --}}
    <div class="card border-0 shadow-sm rounded-4">
        <div class="card-body p-0">
            @forelse($notifications as $notification)
                @php
                    // Use stored icon/color or fallback
                    $icon = $notification->icon ? 'bi-' . $notification->icon : 'bi-bell';
                    $color = $notification->color ?? 'secondary';
                @endphp
                <div class="notification-item d-flex align-items-start gap-3 p-3 border-bottom {{ !$notification->is_read ? 'bg-light' : '' }}"
                     data-notification-id="{{ $notification->id }}">
                    {{-- Icon --}}
                    <div class="notification-icon rounded-circle d-flex align-items-center justify-content-center flex-shrink-0"
                         style="width: 48px; height: 48px; background: var(--bs-{{ $color }}-bg-subtle, rgba(var(--bs-{{ $color }}-rgb), 0.1));">
                        <i class="bi {{ $icon }} text-{{ $color }}" style="font-size: 1.25rem;"></i>
                    </div>

                    {{-- Content --}}
                    <div class="flex-grow-1 min-width-0">
                        <div class="d-flex align-items-start justify-content-between gap-2">
                            <div>
                                <h6 class="mb-1 fw-semibold {{ !$notification->is_read ? 'text-dark' : 'text-muted' }}">
                                    @if($notification->link)
                                        <a href="{{ $notification->link }}" class="text-decoration-none text-inherit">
                                            {{ $notification->title }}
                                        </a>
                                    @else
                                        {{ $notification->title }}
                                    @endif
                                    @if(!$notification->is_read)
                                        <span class="badge bg-primary ms-2" style="font-size: 0.65rem;">NEW</span>
                                    @endif
                                </h6>
                                <p class="mb-1 text-muted small">{{ $notification->message }}</p>
                                <div class="d-flex align-items-center gap-3 flex-wrap">
                                    <small class="text-muted">
                                        <i class="bi bi-clock me-1"></i>{{ $notification->created_at->diffForHumans() }}
                                    </small>
                                    <small>
                                        <span class="badge bg-light text-dark">{{ ucwords(str_replace('_', ' ', $notification->type)) }}</span>
                                    </small>
                                </div>
                            </div>

                            {{-- Actions --}}
                            <div class="dropdown">
                                <button class="btn btn-sm btn-light rounded-circle" data-bs-toggle="dropdown"
                                        style="width: 32px; height: 32px; padding: 0;">
                                    <i class="bi bi-three-dots-vertical"></i>
                                </button>
                                <ul class="dropdown-menu dropdown-menu-end shadow-sm">
                                    @if(!$notification->is_read)
                                        <li>
                                            <form action="{{ route('staff.notifications.mark-read', $notification->id) }}" method="POST">
                                                @csrf
                                                <button type="submit" class="dropdown-item">
                                                    <i class="bi bi-check2 me-2"></i> Mark as Read
                                                </button>
                                            </form>
                                        </li>
                                    @endif
                                    @if($notification->link)
                                        <li>
                                            <a class="dropdown-item" href="{{ $notification->link }}">
                                                <i class="bi bi-eye me-2"></i> View Details
                                            </a>
                                        </li>
                                    @endif
                                    <li><hr class="dropdown-divider"></li>
                                    <li>
                                        <form action="{{ route('staff.notifications.destroy', $notification->id) }}" method="POST"
                                              onsubmit="return confirm('Delete this notification?')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="dropdown-item text-danger">
                                                <i class="bi bi-trash me-2"></i> Delete
                                            </button>
                                        </form>
                                    </li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            @empty
                <div class="text-center py-5">
                    <div class="mb-3">
                        <i class="bi bi-bell-slash text-muted" style="font-size: 4rem;"></i>
                    </div>
                    <h5 class="text-muted">No notifications</h5>
                    <p class="text-muted small mb-0">You're all caught up! Check back later.</p>
                </div>
            @endforelse
        </div>

        {{-- Pagination --}}
        @if($notifications->hasPages())
            <div class="card-footer bg-transparent border-0 py-3">
                {{ $notifications->withQueryString()->links() }}
            </div>
        @endif
    </div>
</div>

<style>
    .notification-item {
        transition: all 0.2s ease;
    }
    .notification-item:hover {
        background-color: var(--bs-light) !important;
    }
    .notification-item.bg-light {
        border-left: 3px solid var(--bs-primary);
    }
    .min-width-0 {
        min-width: 0;
    }
    .text-inherit {
        color: inherit !important;
    }
    .text-inherit:hover {
        color: var(--bs-primary) !important;
    }
</style>
@endsection
