@extends('staff.layouts.staff')

@section('page-title', 'Edit Laundry #' . $laundry->tracking_number)

@push('styles')
    <link rel="stylesheet" href="{{ asset('assets/css/laundry.css') }}">
@endpush

@section('content')
<div class="container-fluid px-4 py-4">

    {{-- Header --}}
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="fw-bold">Edit Laundry</h2>
            <p class="text-muted mb-0">
                <strong class="text-primary">{{ $laundry->tracking_number }}</strong>
                &bull; {{ $laundry->customer->name ?? 'N/A' }}
                &bull;
                <span class="badge bg-{{
                    $laundry->status === 'completed' ? 'success' :
                    ($laundry->status === 'cancelled' ? 'danger' :
                    ($laundry->status === 'ready' ? 'info' :
                    ($laundry->status === 'processing' ? 'warning' : 'secondary')))
                }}">
                    {{ ucfirst($laundry->status) }}
                </span>
            </p>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('staff.laundries.show', $laundry) }}" class="btn btn-outline-info">
                <i class="bi bi-eye me-1"></i>View
            </a>
            <a href="{{ route('staff.laundries.index') }}" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left me-1"></i>Back
            </a>
        </div>
    </div>

    @if(in_array($laundry->status, ['completed', 'cancelled']))
        <div class="alert alert-warning">
            <i class="bi bi-exclamation-triangle me-2"></i>
            This laundry is <strong>{{ $laundry->status }}</strong>. Only notes can be updated.
        </div>
    @endif

    @if($errors->any())
        <div class="alert alert-danger alert-dismissible fade show">
            <i class="bi bi-exclamation-circle me-2"></i>
            Please fix the errors below.
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <form action="{{ route('staff.laundries.update', $laundry) }}" method="POST" id="laundryForm">
        @csrf
        @method('PUT')

        <div class="row g-4">

            {{-- ══════ LEFT ══════ --}}
            <div class="col-lg-8">

                {{-- Service Details --}}
                <div class="card border-0 shadow-sm rounded-4 mb-4">
                    <div class="card-header">
                        <h6 class="mb-0"><i class="bi bi-droplet me-2"></i>Service Details</h6>
                    </div>
                    <div class="card-body">
                        <div class="row g-3">

                            {{-- Service --}}
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Service <span class="text-danger">*</span></label>
                                <select name="service_id" id="serviceSelect"
                                    class="form-select @error('service_id') is-invalid @enderror" required>

                                    {{-- Drop Off --}}
                                    @php $dropOff = $services->whereIn('category', ['drop_off']); @endphp
                                    @if($dropOff->count())
                                        <optgroup label="🛍 Drop Off">
                                        @foreach($dropOff as $service)
                                            <option value="{{ $service->id }}"
                                                {{ old('service_id', $laundry->service_id) == $service->id ? 'selected' : '' }}
                                                data-price-per-load="{{ $service->price_per_load }}"
                                                data-pricing-type="per_load"
                                                data-service-type="{{ $service->service_type }}"
                                                data-category="{{ $service->category }}"
                                                data-max-weight="{{ $service->max_weight }}"
                                                data-turnaround-time="{{ $service->turnaround_time }}">
                                                {{ $service->name }}
                                                @if($service->service_type === 'special_item')
                                                    (₱{{ number_format($service->price_per_load ?? 0, 2) }}/piece)
                                                @else
                                                    (₱{{ number_format($service->price_per_load ?? 0, 2) }}/load{{ $service->max_weight ? ', up to '.$service->max_weight.'kg' : '' }})
                                                @endif
                                            </option>
                                        @endforeach
                                        </optgroup>
                                    @endif

                                    {{-- Self Service --}}
                                    @php $selfService = $services->where('category', 'self_service'); @endphp
                                    @if($selfService->count())
                                        <optgroup label="🧺 Self Service">
                                        @foreach($selfService as $service)
                                            <option value="{{ $service->id }}"
                                                {{ old('service_id', $laundry->service_id) == $service->id ? 'selected' : '' }}
                                                data-price-per-load="{{ $service->price_per_load }}"
                                                data-pricing-type="per_load"
                                                data-service-type="{{ $service->service_type }}"
                                                data-category="{{ $service->category }}"
                                                data-max-weight=""
                                                data-turnaround-time="{{ $service->turnaround_time }}">
                                                {{ $service->name }}
                                                (₱{{ number_format($service->price_per_load ?? 0, 2) }}/load)
                                            </option>
                                        @endforeach
                                        </optgroup>
                                    @endif
                                </select>
                                @error('service_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>

                            {{-- Branch (read-only for staff) --}}
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Branch</label>
                                <input type="hidden" name="branch_id" value="{{ $laundry->branch_id }}">
                                <input type="text" class="form-control" value="{{ $laundry->branch->name ?? '—' }}" readonly>
                                <small class="text-muted">Branch cannot be changed</small>
                            </div>

                            {{-- Weight --}}
                            <div class="col-md-6">
                                <label class="form-label fw-semibold" id="weightLabel">
                                    Weight (kg) <span class="text-muted fw-normal small">(optional)</span>
                                </label>
                                <div class="input-group">
                                    <input type="number" name="weight" step="0.1" min="0"
                                        class="form-control @error('weight') is-invalid @enderror"
                                        value="{{ old('weight', $laundry->weight) }}"
                                        placeholder="0.0" id="weightInput">
                                    <span class="input-group-text">kg</span>
                                </div>
                                @error('weight')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                <small class="text-muted">Record actual weight for reference</small>
                            </div>

                            {{-- Loads / Pieces --}}
                            <div class="col-md-6">
                                <label class="form-label fw-semibold" id="loadsLabel">
                                    Number of Loads <span class="text-danger">*</span>
                                </label>
                                <input type="number" name="number_of_loads" min="1" id="loadsInput"
                                    class="form-control @error('number_of_loads') is-invalid @enderror"
                                    value="{{ old('number_of_loads', $laundry->number_of_loads ?? 1) }}"
                                    placeholder="1" required>
                                @error('number_of_loads')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                <small class="text-muted" id="loadsHelp">Number of loads</small>
                            </div>

                        </div>
                    </div>
                </div>

                {{-- Notes --}}
                <div class="card border-0 shadow-sm rounded-4 mb-4">
                    <div class="card-header">
                        <h6 class="mb-0"><i class="bi bi-chat-left-text me-2"></i>Notes</h6>
                    </div>
                    <div class="card-body">
                        <textarea name="notes"
                            class="form-control @error('notes') is-invalid @enderror"
                            rows="3" placeholder="Special instructions, stain notes, etc...">{{ old('notes', $laundry->notes) }}</textarea>
                        @error('notes')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                </div>

                {{-- Actions --}}
                <div class="d-flex gap-2 mb-4">
                    <button type="submit" class="btn btn-primary px-5"
                        style="background:#3D3B6B;border:none;">
                        <i class="bi bi-check-circle me-2"></i>Update Laundry
                    </button>
                    <a href="{{ route('staff.laundries.show', $laundry) }}"
                       class="btn btn-outline-secondary px-4">
                        <i class="bi bi-x-circle me-2"></i>Cancel
                    </a>
                </div>

            </div>

            {{-- ══════ RIGHT ══════ --}}
            <div class="col-lg-4">
                <div class="sticky-top" style="top:20px;">

                    {{-- Live Price Summary --}}
                    <div class="card border-0 shadow-sm rounded-4 mb-3">
                        <div class="card-header">
                            <h6 class="mb-0"><i class="bi bi-calculator me-2"></i>Price Summary</h6>
                        </div>
                        <div class="card-body">
                            <div class="d-flex justify-content-between mb-2">
                                <span class="text-muted">Service:</span>
                                <strong id="pvServiceName">{{ $laundry->service->name ?? '—' }}</strong>
                            </div>
                            <div class="d-flex justify-content-between mb-2">
                                <span class="text-muted" id="pvUnitLabel">Price/load:</span>
                                <strong id="pvUnitPrice">₱{{ number_format($laundry->service->price_per_load ?? 0, 2) }}</strong>
                            </div>
                            <div class="d-flex justify-content-between mb-2">
                                <span class="text-muted" id="pvQtyLabel">Loads:</span>
                                <strong id="pvQty">{{ $laundry->number_of_loads ?? 1 }}</strong>
                            </div>
                            <div class="d-flex justify-content-between mb-2 border-top pt-2">
                                <span class="text-muted">Subtotal:</span>
                                <strong id="pvSubtotal">₱{{ number_format($laundry->subtotal ?? 0, 2) }}</strong>
                            </div>
                            @if(($laundry->pickup_fee ?? 0) + ($laundry->delivery_fee ?? 0) > 0)
                            <div class="d-flex justify-content-between mb-2">
                                <span class="text-muted">Fees:</span>
                                <strong>₱{{ number_format(($laundry->pickup_fee ?? 0) + ($laundry->delivery_fee ?? 0), 2) }}</strong>
                            </div>
                            @endif
                            @if(($laundry->addons_total ?? 0) > 0)
                            <div class="d-flex justify-content-between mb-2">
                                <span class="text-muted">Add-ons:</span>
                                <strong class="text-success">₱{{ number_format($laundry->addons_total, 2) }}</strong>
                            </div>
                            @endif
                            <div class="d-flex justify-content-between border-top pt-2 mt-1">
                                <strong>Grand Total:</strong>
                                <strong class="fs-5 text-primary" id="pvTotal">
                                    ₱{{ number_format($laundry->total_amount ?? 0, 2) }}
                                </strong>
                            </div>
                            <small class="text-muted d-block mt-2">
                                <i class="bi bi-info-circle me-1"></i>
                                Fees and add-ons are locked from the original order.
                            </small>
                        </div>
                    </div>

                    {{-- Original Info --}}
                    <div class="card border-0 shadow-sm rounded-4">
                        <div class="card-body p-4">
                            <h6 class="fw-bold mb-3">
                                <i class="bi bi-clock-history text-muted me-2"></i>Original Order
                            </h6>
                            <div class="small text-muted">
                                <div class="mb-1">
                                    <strong>Created:</strong>
                                    {{ $laundry->created_at->format('M d, Y h:i A') }}
                                </div>
                                <div class="mb-1">
                                    <strong>Status:</strong> {{ ucfirst($laundry->status) }}
                                </div>
                                <div class="mb-1">
                                    <strong>Payment:</strong> {{ ucfirst($laundry->payment_status) }}
                                </div>
                                @if($laundry->promotion)
                                    <div class="mb-1">
                                        <strong>Promo:</strong> {{ $laundry->promotion->name }}
                                    </div>
                                @endif
                                @if($laundry->pickupRequest)
                                    <div class="mb-0">
                                        <strong>Pickup:</strong>
                                        <a href="{{ route('staff.pickups.show', $laundry->pickup_request_id) }}">
                                            #{{ $laundry->pickup_request_id }}
                                        </a>
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>

                </div>
            </div>

        </div>
    </form>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const serviceSelect = document.getElementById('serviceSelect');
    const loadsInput    = document.getElementById('loadsInput');
    const loadsLabel    = document.getElementById('loadsLabel');
    const loadsHelp     = document.getElementById('loadsHelp');
    const pvUnitLabel   = document.getElementById('pvUnitLabel');
    const pvUnitPrice   = document.getElementById('pvUnitPrice');
    const pvQtyLabel    = document.getElementById('pvQtyLabel');
    const pvQty         = document.getElementById('pvQty');
    const pvSubtotal    = document.getElementById('pvSubtotal');
    const pvTotal       = document.getElementById('pvTotal');
    const pvServiceName = document.getElementById('pvServiceName');

    const fixedFees    = {{ ($laundry->pickup_fee ?? 0) + ($laundry->delivery_fee ?? 0) }};
    const addonsTotal  = {{ $laundry->addons_total ?? 0 }};
    const discount     = {{ $laundry->discount_amount ?? 0 }};

    function updateSummary() {
        const opt = serviceSelect.options[serviceSelect.selectedIndex];
        if (!opt || !opt.value) return;

        const serviceType  = opt.dataset.serviceType;
        const pricePerLoad = parseFloat(opt.dataset.pricePerLoad) || 0;
        const isSpecial    = serviceType === 'special_item';
        const loads        = parseInt(loadsInput.value) || 1;
        const unit         = isSpecial ? 'piece' : 'load';
        const units        = isSpecial ? 'pieces' : 'loads';

        loadsLabel.innerHTML = (isSpecial ? 'Number of Pieces' : 'Number of Loads')
            + ' <span class="text-danger">*</span>';
        loadsHelp.textContent = isSpecial
            ? 'Number of pieces (e.g. 2 comforters)'
            : 'Number of loads';

        pvUnitLabel.textContent  = `Price/${unit}:`;
        pvUnitPrice.textContent  = `₱${pricePerLoad.toFixed(2)}`;
        pvQtyLabel.textContent   = isSpecial ? 'Pieces:' : 'Loads:';
        pvQty.textContent        = loads;
        pvServiceName.textContent = opt.text.split(' (')[0];

        const subtotal = pricePerLoad * loads;
        pvSubtotal.textContent = `₱${subtotal.toFixed(2)}`;

        const total = Math.max(0, subtotal - discount + fixedFees + addonsTotal);
        pvTotal.textContent = `₱${total.toFixed(2)}`;
    }

    if (serviceSelect) serviceSelect.addEventListener('change', updateSummary);
    if (loadsInput)    loadsInput.addEventListener('input', updateSummary);
    updateSummary();
});
</script>
@endpush
@endsection
