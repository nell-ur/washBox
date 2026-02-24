@extends('admin.layouts.app')

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
            <p class="text-muted">
                <strong class="text-primary">{{ $laundry->tracking_number }}</strong>
                &bull; {{ $laundry->customer->name ?? 'N/A' }}
                &bull;
                <span class="badge bg-{{ $laundry->status === 'completed' ? 'success' : ($laundry->status === 'cancelled' ? 'danger' : 'warning') }}">
                    {{ ucfirst($laundry->status) }}
                </span>
            </p>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('admin.laundries.show', $laundry) }}" class="btn btn-outline-info">
                <i class="bi bi-eye me-1"></i>View
            </a>
            <a href="{{ route('admin.laundries.index') }}" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left me-1"></i>Back
            </a>
        </div>
    </div>

    @if(in_array($laundry->status, ['completed', 'cancelled']))
        <div class="alert alert-warning">
            <i class="bi bi-exclamation-triangle me-2"></i>
            This laundry is <strong>{{ $laundry->status }}</strong>. Editing is limited to notes only.
        </div>
    @endif

    <form action="{{ route('admin.laundries.update', $laundry) }}" method="POST" id="laundryForm">
        @csrf
        @method('PUT')

        <div class="row g-4">
            {{-- Left Column --}}
            <div class="col-lg-8">

                {{-- Customer & Branch --}}
                <div class="card border-0 shadow-sm rounded-4 mb-4">
                    <div class="card-header">
                        <h6 class="mb-0"><i class="bi bi-person me-2"></i>Customer & Branch</h6>
                    </div>
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Customer <span class="text-danger">*</span></label>
                                <select name="customer_id" class="form-select @error('customer_id') is-invalid @enderror" required>
                                    @foreach($customers as $customer)
                                        <option value="{{ $customer->id }}"
                                            {{ old('customer_id', $laundry->customer_id) == $customer->id ? 'selected' : '' }}>
                                            {{ $customer->name }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('customer_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Branch <span class="text-danger">*</span></label>
                                <select name="branch_id" class="form-select @error('branch_id') is-invalid @enderror" required>
                                    @foreach($branches as $branch)
                                        <option value="{{ $branch->id }}"
                                            {{ old('branch_id', $laundry->branch_id) == $branch->id ? 'selected' : '' }}>
                                            {{ $branch->name }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('branch_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                        </div>
                    </div>
                </div>

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
                                            @php
                                                $pt           = $service->pricing_type ?? 'per_load';
                                                $displayPrice = $pt === 'per_piece'
                                                    ? ($service->price_per_piece ?? 0)
                                                    : ($service->price_per_load  ?? 0);
                                                $priceUnit    = $pt === 'per_piece' ? 'piece' : 'load';
                                            @endphp
                                            <option value="{{ $service->id }}"
                                                {{ old('service_id', $laundry->service_id) == $service->id ? 'selected' : '' }}
                                                data-price-per-load="{{ $service->price_per_load ?? 0 }}"
                                                data-price-per-piece="{{ $service->price_per_piece ?? 0 }}"
                                                data-pricing-type="{{ $pt }}"
                                                data-service-type="{{ $service->service_type }}"
                                                data-category="{{ $service->category }}"
                                                data-max-weight="{{ $service->max_weight }}"
                                                data-turnaround-time="{{ $service->turnaround_time }}">
                                                {{ $service->name }}
                                                (₱{{ number_format($displayPrice, 2) }}/{{ $priceUnit }}{{ $pt !== 'per_piece' && $service->max_weight ? ', up to '.$service->max_weight.'kg' : '' }})
                                            </option>
                                        @endforeach
                                        </optgroup>
                                    @endif

                                    {{-- Self Service --}}
                                    @php $selfService = $services->where('category', 'self_service'); @endphp
                                    @if($selfService->count())
                                        <optgroup label="🧺 Self Service">
                                        @foreach($selfService as $service)
                                            @php
                                                $ssPt    = $service->pricing_type ?? 'per_load';
                                                $ssPrice = $ssPt === 'per_piece' ? ($service->price_per_piece ?? 0) : ($service->price_per_load ?? 0);
                                                $ssUnit  = $ssPt === 'per_piece' ? 'piece' : 'load';
                                            @endphp
                                            <option value="{{ $service->id }}"
                                                {{ old('service_id', $laundry->service_id) == $service->id ? 'selected' : '' }}
                                                data-price-per-load="{{ $service->price_per_load ?? 0 }}"
                                                data-price-per-piece="{{ $service->price_per_piece ?? 0 }}"
                                                data-pricing-type="{{ $ssPt }}"
                                                data-service-type="{{ $service->service_type }}"
                                                data-category="{{ $service->category }}"
                                                data-max-weight=""
                                                data-turnaround-time="{{ $service->turnaround_time }}">
                                                {{ $service->name }}
                                                (₱{{ number_format($ssPrice, 2) }}/{{ $ssUnit }})
                                            </option>
                                        @endforeach
                                        </optgroup>
                                    @endif

                                </select>
                                @error('service_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>

                            {{-- Staff --}}
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Assign Staff</label>
                                <select name="staff_id" class="form-select @error('staff_id') is-invalid @enderror">
                                    <option value="">Unassigned</option>
                                    @foreach($staff as $member)
                                        <option value="{{ $member->id }}"
                                            {{ old('staff_id', $laundry->staff_id) == $member->id ? 'selected' : '' }}>
                                            {{ $member->name }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('staff_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>

                            {{-- Weight — hidden by JS for per-piece services --}}
                            <div class="col-md-6" id="weightContainer">
                                <label class="form-label fw-semibold" id="weightLabel">
                                    Weight (kg) <span class="text-muted fw-normal small">(optional)</span>
                                </label>
                                <div class="input-group">
                                    {{-- min="0" so value=0 never fails HTML5 validation when hidden --}}
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
                            <div class="col-md-6" id="loadsContainer">
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
                        <textarea name="notes" class="form-control @error('notes') is-invalid @enderror"
                            rows="3" placeholder="Special instructions, stain notes, etc...">{{ old('notes', $laundry->notes) }}</textarea>
                        @error('notes')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                </div>

                {{-- Action Buttons --}}
                <div class="d-flex gap-2 mb-4">
                    <button type="submit" class="btn btn-primary px-5" style="background:#3D3B6B;border:none;">
                        <i class="bi bi-check-circle me-2"></i>Update Laundry
                    </button>
                    <a href="{{ route('admin.laundries.show', $laundry) }}" class="btn btn-outline-secondary px-4">
                        <i class="bi bi-x-circle me-2"></i>Cancel
                    </a>
                </div>

            </div>

            {{-- Right Column --}}
            <div class="col-lg-4">
                <div class="sticky-top" style="top:20px;">

                    {{-- Price Summary --}}
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
                                {{-- Initial label/price correct based on saved pricing_type --}}
                                @php
                                    $initPt    = $laundry->service->pricing_type ?? 'per_load';
                                    $initPrice = $initPt === 'per_piece'
                                        ? ($laundry->service->price_per_piece ?? 0)
                                        : ($laundry->service->price_per_load  ?? 0);
                                    $initUnit  = $initPt === 'per_piece' ? 'piece' : 'load';
                                @endphp
                                <span class="text-muted" id="pvUnitLabel">Price/{{ $initUnit }}:</span>
                                <strong id="pvUnitPrice">₱{{ number_format($initPrice, 2) }}</strong>
                            </div>
                            <div class="d-flex justify-content-between mb-2">
                                <span class="text-muted" id="pvQtyLabel">{{ $initPt === 'per_piece' ? 'Pieces' : 'Loads' }}:</span>
                                <strong id="pvQty">{{ $laundry->number_of_loads ?? 1 }}</strong>
                            </div>
                            <div class="d-flex justify-content-between mb-2 border-top pt-2">
                                <span class="text-muted">Subtotal:</span>
                                <strong id="pvSubtotal">₱{{ number_format($laundry->subtotal ?? 0, 2) }}</strong>
                            </div>
                            @if($laundry->pickup_fee > 0 || $laundry->delivery_fee > 0)
                            <div class="d-flex justify-content-between mb-2">
                                <span class="text-muted">Fees:</span>
                                <strong>₱{{ number_format(($laundry->pickup_fee ?? 0) + ($laundry->delivery_fee ?? 0), 2) }}</strong>
                            </div>
                            @endif
                            @if($laundry->addons_total > 0)
                            <div class="d-flex justify-content-between mb-2">
                                <span class="text-muted">Add-ons:</span>
                                <strong class="text-success">₱{{ number_format($laundry->addons_total, 2) }}</strong>
                            </div>
                            @endif
                            <div class="d-flex justify-content-between border-top pt-2 mt-2">
                                <strong>Grand Total:</strong>
                                <strong class="fs-5 text-primary" id="pvTotal">₱{{ number_format($laundry->total_amount ?? 0, 2) }}</strong>
                            </div>
                            <small class="text-muted d-block mt-2">
                                <i class="bi bi-info-circle me-1"></i>
                                Fees and add-ons are preserved from the original order.
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
                                <div class="mb-1"><strong>Created:</strong> {{ $laundry->created_at->format('M d, Y h:i A') }}</div>
                                <div class="mb-1"><strong>Status:</strong> {{ ucfirst($laundry->status) }}</div>
                                <div class="mb-1"><strong>Payment:</strong> {{ ucfirst($laundry->payment_status) }}</div>
                                @if($laundry->promotion)
                                    <div class="mb-1"><strong>Promo:</strong> {{ $laundry->promotion->name }}</div>
                                @endif
                                @if($laundry->pickupRequest)
                                    <div class="mb-0">
                                        <strong>Pickup:</strong>
                                        <a href="{{ route('admin.pickups.show', $laundry->pickup_request_id) }}">#{{ $laundry->pickup_request_id }}</a>
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
    const serviceSelect   = document.getElementById('serviceSelect');
    const weightContainer = document.getElementById('weightContainer');
    const weightInput     = document.getElementById('weightInput');
    const loadsInput      = document.getElementById('loadsInput');
    const loadsLabel      = document.getElementById('loadsLabel');
    const loadsHelp       = document.getElementById('loadsHelp');
    const pvUnitLabel     = document.getElementById('pvUnitLabel');
    const pvUnitPrice     = document.getElementById('pvUnitPrice');
    const pvQtyLabel      = document.getElementById('pvQtyLabel');
    const pvQty           = document.getElementById('pvQty');
    const pvSubtotal      = document.getElementById('pvSubtotal');
    const pvTotal         = document.getElementById('pvTotal');
    const pvServiceName   = document.getElementById('pvServiceName');

    const fixedFees      = {{ ($laundry->pickup_fee ?? 0) + ($laundry->delivery_fee ?? 0) }};
    const addonsTotal    = {{ $laundry->addons_total ?? 0 }};
    const discountAmount = {{ $laundry->discount_amount ?? 0 }};

    function updateSummary() {
        const opt = serviceSelect.options[serviceSelect.selectedIndex];
        if (!opt || !opt.value) return;

        const pricingType   = opt.dataset.pricingType  || 'per_load';
        const pricePerLoad  = parseFloat(opt.dataset.pricePerLoad)  || 0;
        const pricePerPiece = parseFloat(opt.dataset.pricePerPiece) || 0;
        const isPerPiece    = pricingType === 'per_piece';
        const unitPrice     = isPerPiece ? pricePerPiece : pricePerLoad;
        const unit          = isPerPiece ? 'piece' : 'load';
        const loads         = parseInt(loadsInput.value) || 1;

        // ── Weight field: hide for per-piece, show for per-load ───────────────
        if (isPerPiece) {
            weightContainer.classList.add('d-none');
            weightInput.removeAttribute('required');
            weightInput.removeAttribute('min');   // prevent 0 < min="0.1" error
            weightInput.value = '0';              // satisfies DB NOT NULL
        } else {
            weightContainer.classList.remove('d-none');
            weightInput.setAttribute('min', '0');
        }

        // ── Labels ────────────────────────────────────────────────────────────
        loadsLabel.innerHTML  = (isPerPiece ? 'Number of Pieces' : 'Number of Loads') +
                                ' <span class="text-danger">*</span>';
        loadsHelp.textContent  = isPerPiece ? 'Number of pieces (e.g. 2 comforters)' : 'Number of loads';
        pvUnitLabel.textContent = `Price/${unit}:`;
        pvUnitPrice.textContent = `₱${unitPrice.toFixed(2)}`;
        pvQtyLabel.textContent  = isPerPiece ? 'Pieces:' : 'Loads:';
        pvQty.textContent       = loads;
        pvServiceName.textContent = opt.text.split(' (')[0];

        // ── Totals ────────────────────────────────────────────────────────────
        const subtotal = unitPrice * loads;
        pvSubtotal.textContent = `₱${subtotal.toFixed(2)}`;
        pvTotal.textContent    = `₱${Math.max(0, subtotal - discountAmount + fixedFees + addonsTotal).toFixed(2)}`;
    }

    // ── Pre-submit guard: ensure weight=0 for per-piece before form posts ─────
    document.getElementById('laundryForm').addEventListener('submit', function () {
        const opt        = serviceSelect.options[serviceSelect.selectedIndex];
        const isPerPiece = opt?.dataset.pricingType === 'per_piece';
        if (isPerPiece) {
            weightInput.value = '0';
            weightInput.removeAttribute('required');
            weightInput.removeAttribute('min');
        } else if (!weightInput.value) {
            weightInput.value = '0';
        }
    });

    serviceSelect.addEventListener('change', updateSummary);
    loadsInput.addEventListener('input',  updateSummary);
    updateSummary();
});
</script>
@endpush
@endsection