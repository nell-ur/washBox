@extends('staff.layouts.staff')

@section('page-title', 'Pickup Request #' . $pickup->id)

@section('content')
<div class="container-fluid">
    {{-- Header --}}
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <a href="{{ route('staff.pickups.index') }}" class="btn btn-outline-secondary btn-sm mb-2">
                <i class="bi bi-arrow-left"></i> Back to List
            </a>
            <h1 class="h3 mb-0">Pickup Request #{{ $pickup->id }}</h1>
        </div>
        <div>
            @if($pickup->status == 'pending')
                <span class="badge bg-warning fs-6">Pending</span>
            @elseif($pickup->status == 'accepted')
                <span class="badge bg-info fs-6">Accepted</span>
            @elseif($pickup->status == 'en_route')
                <span class="badge bg-primary fs-6">En Route</span>
            @elseif($pickup->status == 'picked_up')
                <span class="badge bg-success fs-6">Picked Up</span>
            @elseif($pickup->status == 'cancelled')
                <span class="badge bg-danger fs-6">Cancelled</span>
            @endif
        </div>
    </div>

    {{-- Success/Error Messages --}}
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

    <div class="row">
        {{-- Main Details --}}
        <div class="col-lg-8">
            {{-- Customer Information --}}
            <div class="card mb-4">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0"><i class="bi bi-person"></i> Customer Information</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <p><strong>Name:</strong> {{ $pickup->customer->name }}</p>
                            <p><strong>Email:</strong> {{ $pickup->customer->email ?? 'N/A' }}</p>
                            <p><strong>Phone:</strong> {{ $pickup->contact_phone }}</p>
                        </div>
                        <div class="col-md-6">
                            <p><strong>Customer ID:</strong> #{{ $pickup->customer->id }}</p>
                            <p><strong>Member Since:</strong> {{ $pickup->customer->created_at->format('M d, Y') }}</p>
                            @if($pickup->customer->total_laundries !== null)
                                <p><strong>Total Laundries:</strong> {{ $pickup->customer->total_laundries }}</p>
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            {{-- Pickup Details --}}
            <div class="card mb-4">
                <div class="card-header bg-info text-white">
                    <h5 class="mb-0"><i class="bi bi-geo-alt"></i> Pickup Details</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <p><strong>Branch:</strong> {{ $pickup->branch->name }}</p>
                            <p><strong>Preferred Date:</strong> {{ \Carbon\Carbon::parse($pickup->preferred_date)->format('F d, Y') }}</p>
                            @if($pickup->preferred_time_slot)
                                <p><strong>Preferred Time:</strong> {{ $pickup->preferred_time_slot }}</p>
                            @endif
                        </div>
                        <div class="col-md-6">
                            @if($pickup->service)
                                <p><strong>Service Requested:</strong> {{ $pickup->service->name }}</p>
                            @endif
                            @if($pickup->estimated_weight)
                                <p><strong>Estimated Weight:</strong> {{ $pickup->estimated_weight }} kg</p>
                            @endif
                        </div>
                    </div>

                    <hr>

                    <p><strong>Pickup Address:</strong></p>
                    <p class="mb-2">{{ $pickup->pickup_address }}</p>

                    @if($pickup->landmark)
                        <p class="mb-2">
                            <strong>Landmark:</strong> {{ $pickup->landmark }}
                        </p>
                    @endif

                    @if($pickup->latitude && $pickup->longitude)
                        <a href="https://www.google.com/maps?q={{ $pickup->latitude }},{{ $pickup->longitude }}"
                           target="_blank"
                           class="btn btn-sm btn-outline-primary">
                            <i class="bi bi-geo-alt"></i> Open in Google Maps
                        </a>
                        <a href="https://www.waze.com/ul?ll={{ $pickup->latitude }},{{ $pickup->longitude }}&navigate=yes"
                           target="_blank"
                           class="btn btn-sm btn-outline-info">
                            <i class="bi bi-signpost"></i> Open in Waze
                        </a>
                    @endif

                    @if($pickup->special_instructions)
                        <hr>
                        <p><strong>Special Instructions:</strong></p>
                        <p class="text-muted">{{ $pickup->special_instructions }}</p>
                    @endif
                </div>
            </div>

            {{-- Timeline --}}
            <div class="card mb-4">
                <div class="card-header bg-secondary text-white">
                    <h5 class="mb-0"><i class="bi bi-clock-history"></i> Status Timeline</h5>
                </div>
                <div class="card-body">
                    <ul class="list-unstyled mb-0">
                        <li class="mb-3">
                            <i class="bi bi-circle-fill text-success"></i>
                            <strong>Created</strong>
                            <span class="text-muted float-end">{{ $pickup->created_at->format('M d, Y g:i A') }}</span>
                            <br>
                            <small class="text-muted ms-3">{{ $pickup->created_at->diffForHumans() }}</small>
                        </li>

                        @if($pickup->accepted_at)
                            <li class="mb-3">
                                <i class="bi bi-circle-fill text-info"></i>
                                <strong>Accepted</strong>
                                <span class="text-muted float-end">{{ $pickup->accepted_at->format('M d, Y g:i A') }}</span>
                                @if($pickup->assignedStaff)
                                    <br>
                                    <small class="text-muted ms-3">By: {{ $pickup->assignedStaff->name }}</small>
                                @endif
                            </li>
                        @endif

                        @if($pickup->en_route_at)
                            <li class="mb-3">
                                <i class="bi bi-circle-fill text-primary"></i>
                                <strong>En Route</strong>
                                <span class="text-muted float-end">{{ $pickup->en_route_at->format('M d, Y g:i A') }}</span>
                            </li>
                        @endif

                        @if($pickup->picked_up_at)
                            <li class="mb-3">
                                <i class="bi bi-circle-fill text-success"></i>
                                <strong>Picked Up</strong>
                                <span class="text-muted float-end">{{ $pickup->picked_up_at->format('M d, Y g:i A') }}</span>
                            </li>
                        @endif

                        @if($pickup->cancelled_at)
                            <li class="mb-3">
                                <i class="bi bi-circle-fill text-danger"></i>
                                <strong>Cancelled</strong>
                                <span class="text-muted float-end">{{ $pickup->cancelled_at->format('M d, Y g:i A') }}</span>
                                @if($pickup->cancellation_reason)
                                    <br><small class="text-muted ms-3">Reason: {{ $pickup->cancellation_reason }}</small>
                                @endif
                            </li>
                        @endif
                    </ul>
                </div>
            </div>

            {{-- GPS Location (if en_route or picked_up) --}}
            @if($pickup->status == 'en_route' && $pickup->current_latitude && $pickup->current_longitude)
                <div class="card mb-4">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0"><i class="bi bi-pin-map"></i> Current Location</h5>
                    </div>
                    <div class="card-body">
                        <p>Last updated: {{ $pickup->location_updated_at->diffForHumans() }}</p>
                        <a href="https://www.google.com/maps?q={{ $pickup->current_latitude }},{{ $pickup->current_longitude }}"
                           target="_blank"
                           class="btn btn-primary">
                            <i class="bi bi-geo-alt"></i> View Current Location
                        </a>
                    </div>
                </div>
            @endif
        </div>

        {{-- Actions Sidebar --}}
        <div class="col-lg-4">
            {{-- Quick Actions --}}
            <div class="card mb-4">
                <div class="card-header bg-success text-white">
                    <h5 class="mb-0"><i class="bi bi-lightning"></i> Quick Actions</h5>
                </div>
                <div class="card-body">
                    @if($pickup->status == 'pending')
                        <form action="{{ route('staff.pickups.accept', $pickup->id) }}" method="POST" class="mb-2">
                            @csrf
                            <button type="submit" class="btn btn-success w-100"
                                    onclick="return confirm('Accept this pickup request and assign it to yourself?')">
                                <i class="bi bi-check-circle"></i> Accept Pickup Request
                            </button>
                        </form>
                    @endif

                    @if($pickup->status == 'accepted' && $pickup->assigned_to == auth()->id())
                        <form action="{{ route('staff.pickups.en-route', $pickup->id) }}" method="POST" class="mb-2">
                            @csrf
                            <button type="submit" class="btn btn-primary w-100">
                                <i class="bi bi-truck"></i> Mark as En Route
                            </button>
                        </form>
                    @endif

                    @if($pickup->status == 'en_route' && $pickup->assigned_to == auth()->id())
                        <form action="{{ route('staff.pickups.picked-up', $pickup->id) }}" method="POST" class="mb-2">
                            @csrf
                            <button type="submit" class="btn btn-success w-100">
                                <i class="bi bi-box-seam"></i> Mark as Picked Up
                            </button>
                        </form>

                        {{-- GPS Update Button --}}
                        <button type="button" class="btn btn-outline-primary w-100 mb-2" onclick="updateGPSLocation()">
                            <i class="bi bi-pin-map"></i> Update My Location
                        </button>
                    @endif

                    @if(in_array($pickup->status, ['pending', 'accepted']) && ($pickup->assigned_to == auth()->id() || $pickup->status == 'pending'))
                        <button type="button" class="btn btn-danger w-100" data-bs-toggle="modal" data-bs-target="#cancelModal">
                            <i class="bi bi-x-circle"></i> Cancel Pickup
                        </button>
                    @endif

                    @if($pickup->status == 'picked_up' && !$pickup->order_id)
                        <hr>
                        <a href="{{ route('staff.laundries.create', ['pickup_id' => $pickup->id]) }}" class="btn btn-outline-primary w-100">
                            <i class="bi bi-plus-circle"></i> Create Laundry from Pickup
                        </a>
                    @endif

                    <hr>

                    {{-- Contact Customer --}}
                    <a href="tel:{{ $pickup->contact_phone }}" class="btn btn-outline-success w-100 mb-2">
                        <i class="bi bi-telephone"></i> Call Customer
                    </a>

                    @if($pickup->customer->email)
                        <a href="mailto:{{ $pickup->customer->email }}" class="btn btn-outline-info w-100">
                            <i class="bi bi-envelope"></i> Email Customer
                        </a>
                    @endif
                </div>
            </div>

            {{-- Assignment --}}
            @if($pickup->status != 'cancelled')
                <div class="card mb-4">
                    <div class="card-header">
                        <h6 class="mb-0">Assigned Staff</h6>
                    </div>
                    <div class="card-body">
                        @if($pickup->assignedStaff)
                            <p><strong>{{ $pickup->assignedStaff->name }}</strong></p>
                            <p class="text-muted">{{ $pickup->assignedStaff->email }}</p>
                        @else
                            <p class="text-muted">Not assigned yet</p>
                        @endif
                    </div>
                </div>
            @endif

            {{-- Linked Laundry --}}
            @if($pickup->order_id)
                <div class="card mb-4">
                    <div class="card-header bg-warning">
                        <h6 class="mb-0">Linked Laundry</h6>
                    </div>
                    <div class="card-body">
                        <p><strong>Laundry #{{ $pickup->order_id }}</strong></p>
                        <a href="{{ route('staff.laundries.show', $pickup->order_id) }}" class="btn btn-sm btn-outline-primary">
                            <i class="bi bi-box-arrow-up-right"></i> View Laundry
                        </a>
                    </div>
                </div>
            @endif
        </div>
    </div>
</div>

{{-- Cancel Modal --}}
<div class="modal fade" id="cancelModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="{{ route('staff.pickups.cancel', $pickup->id) }}" method="POST">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title">Cancel Pickup Request</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="reason" class="form-label">Cancellation Reason <span class="text-danger">*</span></label>
                        <textarea name="reason" id="reason" class="form-control" rows="3" required
                                  placeholder="Please provide a reason for cancellation"></textarea>
                    </div>
                    <p class="text-danger">
                        <i class="bi bi-exclamation-triangle"></i>
                        The customer will be notified about this cancellation.
                    </p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-danger">Cancel Pickup Request</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
// GPS Location Update Function
function updateGPSLocation() {
    if ("geolocation" in navigator) {
        navigator.geolocation.getCurrentPosition(function(position) {
            fetch("{{ route('staff.pickups.update-location', $pickup->id) }}", {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                body: JSON.stringify({
                    latitude: position.coords.latitude,
                    longitude: position.coords.longitude
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Location updated successfully!');
                    window.location.reload();
                } else {
                    alert('Failed to update location. Please try again.');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Failed to update location. Please try again.');
            });
        }, function(error) {
            let message = 'Unable to get your location. ';
            switch(error.code) {
                case error.PERMISSION_DENIED:
                    message += 'Please enable location services.';
                    break;
                case error.POSITION_UNAVAILABLE:
                    message += 'Location information is unavailable.';
                    break;
                case error.TIMEOUT:
                    message += 'Location request timed out.';
                    break;
                default:
                    message += 'Please try again.';
            }
            alert(message);
        });
    } else {
        alert('Geolocation is not supported by your browser.');
    }
}

// Auto-refresh if status is en_route (for live tracking)
@if($pickup->status == 'en_route' && $pickup->assigned_to == auth()->id())
    setInterval(function() {
        if (document.visibilityState === 'visible') {
            // Auto-update GPS every 2 minutes
            updateGPSLocation();
        }
    }, 120000); // 2 minutes
@endif
</script>
@endpush
