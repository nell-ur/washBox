@extends('admin.layouts.app')

@section('page-title', 'Confirm Pickup Requests')

@section('content')
<div class="container-fluid">
    <h1 class="h3 mb-4">
        <i class="bi bi-clipboard-check"></i> Confirm Pickup Requests
    </h1>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="bi bi-check-circle"></i> {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

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
                            <div class="card border-warning">
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
                                            <i class="bi bi-calendar"></i> {{ $pickup->preferred_date->format('M d, Y') }}
                                            @if($pickup->preferred_time)
                                                at {{ date('g:i A', strtotime($pickup->preferred_time)) }}
                                            @endif
                                        </small>
                                    </p>

                                    <p class="mb-1">
                                        <small class="text-muted">
                                            <i class="bi bi-telephone"></i> {{ $pickup->phone_number }}
                                        </small>
                                    </p>

                                    <p class="mb-2">
                                        <small class="text-muted">
                                            <i class="bi bi-building"></i> {{ $pickup->branch->name }}
                                        </small>
                                    </p>

                                    @if($pickup->notes)
                                        <p class="mb-2">
                                            <small><i class="bi bi-chat-left-text"></i> {{ Str::limit($pickup->notes, 50) }}</small>
                                        </p>
                                    @endif

                                    <hr>

                                    {{-- One-Click Confirm Button --}}
                                    <form action="{{ route('admin.pickups.confirm-quick', $pickup->id) }}" method="POST" class="d-inline">
                                        @csrf
                                        <button type="submit" class="btn btn-success w-100 mb-2" onclick="return confirm('Confirm this pickup request? Customer will be notified that rider is on the way.')">
                                            <i class="bi bi-check-circle-fill"></i> Confirm Pickup
                                        </button>
                                    </form>

                                    <div class="d-flex gap-2">
                                        <a href="{{ route('admin.pickups.show', $pickup->id) }}" class="btn btn-sm btn-outline-primary flex-fill">
                                            <i class="bi bi-eye"></i> Details
                                        </a>

                                        <button type="button" class="btn btn-sm btn-outline-danger flex-fill"
                                                data-bs-toggle="modal"
                                                data-bs-target="#rejectModal{{ $pickup->id }}">
                                            <i class="bi bi-x-circle"></i> Reject
                                        </button>
                                    </div>

                                    <small class="text-muted d-block mt-2">
                                        <i class="bi bi-clock"></i> Requested {{ $pickup->created_at->diffForHumans() }}
                                    </small>
                                </div>
                            </div>
                        </div>

                        {{-- Reject Modal --}}
                        <div class="modal fade" id="rejectModal{{ $pickup->id }}" tabindex="-1">
                            <div class="modal-dialog">
                                <div class="modal-content">
                                    <form action="{{ route('admin.pickups.cancel', $pickup->id) }}" method="POST">
                                        @csrf
                                        @method('PUT')
                                        <div class="modal-header">
                                            <h5 class="modal-title">Reject Pickup Request</h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                        </div>
                                        <div class="modal-body">
                                            <div class="mb-3">
                                                <label class="form-label">Reason for rejection *</label>
                                                <textarea name="reason" class="form-control" rows="3" required
                                                          placeholder="e.g., Outside service area, Fully booked"></textarea>
                                            </div>
                                        </div>
                                        <div class="modal-footer">
                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                            <button type="submit" class="btn btn-danger">Reject Request</button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>

                {{-- Bulk Confirm All --}}
                @if($pendingPickups->count() > 1)
                    <hr>
                    <form action="{{ route('admin.pickups.confirm-all') }}" method="POST" class="text-center">
                        @csrf
                        <button type="submit" class="btn btn-lg btn-success"
                                onclick="return confirm('Confirm ALL {{ $pendingPickups->count() }} pending pickup requests?')">
                            <i class="bi bi-check-all"></i> Confirm All {{ $pendingPickups->count() }} Requests
                        </button>
                    </form>
                @endif
            @else
                <div class="text-center py-5">
                    <i class="bi bi-check-circle fs-1 text-success"></i>
                    <p class="text-muted mt-2">All caught up! No pending confirmations.</p>
                </div>
            @endif
        </div>
    </div>

    {{-- Recently Confirmed --}}
    <div class="card">
        <div class="card-header bg-success text-white">
            <h5 class="mb-0">
                <i class="bi bi-check-circle"></i> Recently Confirmed
                <span class="badge bg-light text-dark">{{ $confirmedPickups->count() }}</span>
            </h5>
        </div>
        <div class="card-body">
            @if($confirmedPickups->count() > 0)
                <div class="table-responsive">
                    <table class="table table-sm">
                        <thead>
                            <tr>
                                <th>Customer</th>
                                <th>Address</th>
                                <th>Pickup Date</th>
                                <th>Confirmed</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($confirmedPickups as $pickup)
                                <tr>
                                    <td>{{ $pickup->customer->name }}</td>
                                    <td><small>{{ Str::limit($pickup->pickup_address, 30) }}</small></td>
                                    <td>{{ $pickup->preferred_date->format('M d') }}</td>
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
                                        @if($pickup->status == 'accepted')
                                            <form action="{{ route('admin.pickups.en-route', $pickup->id) }}" method="POST" class="d-inline">
                                                @csrf
                                                @method('PUT')
                                                <button type="submit" class="btn btn-sm btn-primary">
                                                    <i class="bi bi-truck"></i> En Route
                                                </button>
                                            </form>
                                        @elseif($pickup->status == 'en_route')
                                            <form action="{{ route('admin.pickups.picked-up', $pickup->id) }}" method="POST" class="d-inline">
                                                @csrf
                                                @method('PUT')
                                                <button type="submit" class="btn btn-sm btn-success">
                                                    <i class="bi bi-box-seam"></i> Picked Up
                                                </button>
                                            </form>
                                        @endif

                                        <a href="{{ route('admin.pickups.show', $pickup->id) }}" class="btn btn-sm btn-outline-secondary">
                                            <i class="bi bi-eye"></i>
                                        </a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <p class="text-muted text-center mb-0">No recently confirmed pickups</p>
            @endif
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
// Auto-refresh every 20 seconds to check for new requests
setInterval(function() {
    if (document.visibilityState === 'visible') {
        window.location.reload();
    }
}, 20000);

// Play sound when page loads if there are pending requests
@if($pendingPickups->count() > 0)
    // You can add a notification sound here
    console.log('{{ $pendingPickups->count() }} pickup requests pending confirmation');
@endif
</script>
@endpush
