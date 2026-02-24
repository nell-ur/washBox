@extends('staff.layouts.staff')

@section('title', 'Confirm Pickup Requests')

@section('content')
<div class="container-fluid">
    <h1 class="h3 mb-4">
        <i class="bi bi-clipboard-check"></i> Confirm Pickup Requests
        <br>
        <small class="text-muted">
            <i class="bi bi-building"></i> {{ auth()->user()->branch->name }} Branch
        </small>
    </h1>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="bi bi-check-circle"></i> {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="bi bi-exclamation-circle"></i> {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    {{-- Quick Stats --}}
    <div class="row mb-4">
        <div class="col-md-4">
            <div class="card border-warning">
                <div class="card-body">
                    <h6 class="text-muted mb-1">Pending Confirmation</h6>
                    <h3 class="mb-0">{{ $pendingPickups->count() }}</h3>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-success">
                <div class="card-body">
                    <h6 class="text-muted mb-1">Confirmed Today</h6>
                    <h3 class="mb-0">{{ $confirmedPickups->count() }}</h3>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-info">
                <div class="card-body">
                    <h6 class="text-muted mb-1">My Assigned</h6>
                    <h3 class="mb-0">{{ $confirmedPickups->where('assigned_to', auth()->id())->count() }}</h3>
                </div>
            </div>
        </div>
    </div>

    {{-- Pending Pickups (Need Confirmation) --}}
    <div class="card mb-4">
        <div class="card-header bg-warning text-dark">
            <h5 class="mb-0">
                <i class="bi bi-clock-history"></i> Pending Confirmation
                <span class="badge bg-dark">{{ $pendingPickups->count() }}</span>
            </h5>
        </div>
        <div class="card-body">
            @if($pendingPickups->count() > 0)
                <div class="row">
                    @foreach($pendingPickups as $pickup)
                        <div class="col-md-6 col-lg-4 mb-3">
                            <div class="card border-warning h-100">
                                <div class="card-body">
                                    {{-- Customer Info --}}
                                    <h6 class="card-title">
                                        <i class="bi bi-person-fill"></i> {{ $pickup->customer->name }}
                                    </h6>

                                    {{-- Pickup Details --}}
                                    <p class="mb-1">
                                        <small class="text-muted">
                                            <i class="bi bi-geo-alt"></i> {{ Str::limit($pickup->pickup_address, 40) }}
                                        </small>
                                    </p>

                                    <p class="mb-1">
                                        <small class="text-muted">
                                            <i class="bi bi-calendar"></i> {{ \Carbon\Carbon::parse($pickup->preferred_date)->format('M d, Y') }}
                                            @if($pickup->preferred_time_slot)
                                                at {{ $pickup->preferred_time_slot }}
                                            @endif
                                        </small>
                                    </p>

                                    <p class="mb-1">
                                        <small class="text-muted">
                                            <i class="bi bi-telephone"></i> {{ $pickup->contact_phone }}
                                        </small>
                                    </p>

                                    @if($pickup->service)
                                        <p class="mb-2">
                                            <small class="text-muted">
                                                <i class="bi bi-box"></i> {{ $pickup->service->name }}
                                            </small>
                                        </p>
                                    @endif

                                    @if($pickup->special_instructions)
                                        <p class="mb-2">
                                            <small><i class="bi bi-chat-left-text"></i> {{ Str::limit($pickup->special_instructions, 50) }}</small>
                                        </p>
                                    @endif

                                    <hr>

                                    {{-- One-Click Accept & Assign Button --}}
                                    <form action="{{ route('staff.pickups.accept', $pickup->id) }}" method="POST" class="d-inline">
                                        @csrf
                                        <button type="submit" class="btn btn-success w-100 mb-2"
                                                onclick="return confirm('Accept this pickup and assign it to yourself? Customer will be notified.')">
                                            <i class="bi bi-check-circle-fill"></i> Accept & Assign to Me
                                        </button>
                                    </form>

                                    <div class="d-flex gap-2">
                                        <a href="{{ route('staff.pickups.show', $pickup->id) }}"
                                           class="btn btn-sm btn-outline-primary flex-fill">
                                            <i class="bi bi-eye"></i> Details
                                        </a>

                                        <a href="tel:{{ $pickup->contact_phone }}"
                                           class="btn btn-sm btn-outline-info flex-fill">
                                            <i class="bi bi-telephone"></i> Call
                                        </a>

                                        <button type="button"
                                                class="btn btn-sm btn-outline-danger flex-fill"
                                                data-bs-toggle="modal"
                                                data-bs-target="#cancelModal{{ $pickup->id }}">
                                            <i class="bi bi-x-circle"></i> Cancel
                                        </button>
                                    </div>

                                    <small class="text-muted d-block mt-2">
                                        <i class="bi bi-clock"></i> Requested {{ $pickup->created_at->diffForHumans() }}
                                    </small>
                                </div>
                            </div>
                        </div>

                        {{-- Cancel Modal --}}
                        <div class="modal fade" id="cancelModal{{ $pickup->id }}" tabindex="-1">
                            <div class="modal-dialog">
                                <div class="modal-content">
                                    <form action="{{ route('staff.pickups.cancel', $pickup->id) }}" method="POST">
                                        @csrf
                                        <div class="modal-header">
                                            <h5 class="modal-title">Cancel Pickup Request #{{ $pickup->id }}</h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                        </div>
                                        <div class="modal-body">
                                            <div class="mb-3">
                                                <label class="form-label">Reason for cancellation <span class="text-danger">*</span></label>
                                                <textarea name="reason" class="form-control" rows="3" required
                                                          placeholder="e.g., Outside service area, Customer unavailable"></textarea>
                                            </div>
                                            <div class="alert alert-warning">
                                                <i class="bi bi-exclamation-triangle"></i>
                                                The customer will be notified about this cancellation.
                                            </div>
                                        </div>
                                        <div class="modal-footer">
                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                            <button type="submit" class="btn btn-danger">
                                                <i class="bi bi-x-circle"></i> Cancel Request
                                            </button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @else
                <div class="text-center py-5">
                    <i class="bi bi-check-circle fs-1 text-success"></i>
                    <p class="text-muted mt-2">All caught up! No pending confirmations.</p>
                    <a href="{{ route('staff.pickups.index') }}" class="btn btn-outline-primary mt-2">
                        <i class="bi bi-arrow-left"></i> View All Pickups
                    </a>
                </div>
            @endif
        </div>
    </div>

    {{-- Recently Confirmed (My Pickups) --}}
    <div class="card">
        <div class="card-header bg-success text-white">
            <h5 class="mb-0">
                <i class="bi bi-check-circle"></i> My Recently Confirmed Pickups
                <span class="badge bg-light text-dark">{{ $confirmedPickups->where('assigned_to', auth()->id())->count() }}</span>
            </h5>
        </div>
        <div class="card-body">
            @if($confirmedPickups->where('assigned_to', auth()->id())->count() > 0)
                <div class="table-responsive">
                    <table class="table table-sm table-hover">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Customer</th>
                                <th>Address</th>
                                <th>Pickup Date</th>
                                <th>Confirmed</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($confirmedPickups->where('assigned_to', auth()->id()) as $pickup)
                                <tr>
                                    <td><strong>#{{ $pickup->id }}</strong></td>
                                    <td>
                                        {{ $pickup->customer->name }}
                                        <br>
                                        <small class="text-muted">{{ $pickup->contact_phone }}</small>
                                    </td>
                                    <td><small>{{ Str::limit($pickup->pickup_address, 30) }}</small></td>
                                    <td>{{ \Carbon\Carbon::parse($pickup->preferred_date)->format('M d') }}</td>
                                    <td><small>{{ $pickup->accepted_at->diffForHumans() }}</small></td>
                                    <td>
                                        @if($pickup->status == 'accepted')
                                            <span class="badge bg-info">Accepted</span>
                                        @elseif($pickup->status == 'en_route')
                                            <span class="badge bg-primary">En Route</span>
                                        @elseif($pickup->status == 'picked_up')
                                            <span class="badge bg-success">Picked Up</span>
                                        @endif
                                    </td>
                                    <td>
                                        <div class="btn-group">
                                            @if($pickup->status == 'accepted')
                                                <form action="{{ route('staff.pickups.en-route', $pickup->id) }}" method="POST" class="d-inline">
                                                    @csrf
                                                    <button type="submit" class="btn btn-sm btn-primary" title="Mark as En Route">
                                                        <i class="bi bi-truck"></i>
                                                    </button>
                                                </form>
                                            @elseif($pickup->status == 'en_route')
                                                <form action="{{ route('staff.pickups.picked-up', $pickup->id) }}" method="POST" class="d-inline">
                                                    @csrf
                                                    <button type="submit" class="btn btn-sm btn-success" title="Mark as Picked Up">
                                                        <i class="bi bi-box-seam"></i>
                                                    </button>
                                                </form>
                                            @endif

                                            <a href="{{ route('staff.pickups.show', $pickup->id) }}"
                                               class="btn btn-sm btn-outline-secondary"
                                               title="View Details">
                                                <i class="bi bi-eye"></i>
                                            </a>

                                            <a href="tel:{{ $pickup->contact_phone }}"
                                               class="btn btn-sm btn-outline-success"
                                               title="Call Customer">
                                                <i class="bi bi-telephone"></i>
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <p class="text-muted text-center mb-0">No confirmed pickups assigned to you yet</p>
            @endif
        </div>
    </div>
</div>
@endsection

@push('styles')
<style>
    .card {
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    }

    .card-body {
        padding: 1.25rem;
    }

    .btn-group {
        gap: 0.25rem;
    }
</style>
@endpush

@push('scripts')
<script>
// Auto-refresh every 20 seconds to check for new requests
setInterval(function() {
    if (document.visibilityState === 'visible') {
        window.location.reload();
    }
}, 20000);

// Play sound notification when page loads if there are pending requests
@if($pendingPickups->count() > 0)
    console.log('{{ $pendingPickups->count() }} pickup requests pending confirmation');

    // Optional: Show browser notification
    if ("Notification" in window && Notification.permission === "granted") {
        new Notification("WashBox Alert", {
            body: "{{ $pendingPickups->count() }} pickup request(s) need confirmation",
            icon: "/images/logo.png"
        });
    }
@endif

// Request notification permission on page load
if ("Notification" in window && Notification.permission === "default") {
    Notification.requestPermission();
}
</script>
@endpush
