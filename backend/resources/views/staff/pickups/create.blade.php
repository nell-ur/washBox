@extends('staff.layouts.app')

@section('title', 'Create Pickup Request')

@section('content')
<div class="container-fluid">
    {{-- Header --}}
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <a href="{{ route('staff.pickups.index') }}" class="btn btn-outline-secondary btn-sm mb-2">
                <i class="bi bi-arrow-left"></i> Back to Pickups
            </a>
            <h1 class="h3 mb-0">Create Pickup Request</h1>
            <small class="text-muted">
                <i class="bi bi-building"></i> {{ auth()->user()->branch->name }} Branch
            </small>
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

    <form action="{{ route('staff.pickups.store') }}" method="POST" id="pickupForm">
        @csrf

        <div class="row">
            {{-- Main Form --}}
            <div class="col-lg-8">
                {{-- Customer Information --}}
                <div class="card mb-4">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0"><i class="bi bi-person"></i> Customer Information</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Customer <span class="text-danger">*</span></label>
                                    <select name="customer_id" class="form-select @error('customer_id') is-invalid @enderror" required>
                                        <option value="">Select Customer</option>
                                        @foreach($customers as $customer)
                                            <option value="{{ $customer->id }}" {{ old('customer_id') == $customer->id ? 'selected' : '' }}>
                                                {{ $customer->name }} - {{ $customer->phone }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('customer_id')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Contact Phone <span class="text-danger">*</span></label>
                                    <input type="text" name="contact_phone" class="form-control @error('contact_phone') is-invalid @enderror"
                                           value="{{ old('contact_phone') }}" placeholder="09XX XXX XXXX" required>
                                    @error('contact_phone')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Service Type & Fees --}}
                <div class="card mb-4">
                    <div class="card-header bg-success text-white">
                        <h5 class="mb-0"><i class="bi bi-truck"></i> Service Type</h5>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label class="form-label">Service Type <span class="text-danger">*</span></label>
                            <div class="row g-3">
                                <div class="col-md-4">
                                    <div class="card border-primary h-100">
                                        <div class="card-body text-center">
                                            <input type="radio" name="service_type" value="pickup_only"
                                                   id="pickup_only" class="form-check-input"
                                                   {{ old('service_type') == 'pickup_only' ? 'checked' : '' }}>
                                            <label for="pickup_only" class="d-block mt-2 cursor-pointer">
                                                <i class="bi bi-arrow-down-circle fs-1 text-primary"></i>
                                                <h6 class="mt-2">Pickup Only</h6>
                                                <p class="text-muted mb-0 small">We pickup laundry from customer</p>
                                                <strong class="text-primary" id="pickup_only_fee">₱50.00</strong>
                                            </label>
                                        </div>
                                    </div>
                                </div>

                                <div class="col-md-4">
                                    <div class="card border-info h-100">
                                        <div class="card-body text-center">
                                            <input type="radio" name="service_type" value="delivery_only"
                                                   id="delivery_only" class="form-check-input"
                                                   {{ old('service_type') == 'delivery_only' ? 'checked' : '' }}>
                                            <label for="delivery_only" class="d-block mt-2 cursor-pointer">
                                                <i class="bi bi-arrow-up-circle fs-1 text-info"></i>
                                                <h6 class="mt-2">Delivery Only</h6>
                                                <p class="text-muted mb-0 small">We deliver cleaned laundry</p>
                                                <strong class="text-info" id="delivery_only_fee">₱50.00</strong>
                                            </label>
                                        </div>
                                    </div>
                                </div>

                                <div class="col-md-4">
                                    <div class="card border-success h-100">
                                        <div class="card-body text-center">
                                            <input type="radio" name="service_type" value="both"
                                                   id="both" class="form-check-input" checked
                                                   {{ old('service_type', 'both') == 'both' ? 'checked' : '' }}>
                                            <label for="both" class="d-block mt-2 cursor-pointer">
                                                <i class="bi bi-arrow-down-up fs-1 text-success"></i>
                                                <h6 class="mt-2">Pickup & Delivery</h6>
                                                <p class="text-muted mb-0 small">Full service (10% discount)</p>
                                                <strong class="text-success" id="both_fee">₱90.00</strong>
                                            </label>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            @error('service_type')
                                <div class="text-danger mt-2">{{ $message }}</div>
                            @enderror
                        </div>

                        {{-- Fee Breakdown --}}
                        <div class="alert alert-info mt-3">
                            <h6><i class="bi bi-calculator"></i> Fee Breakdown</h6>
                            <div id="fee_breakdown">
                                <div class="d-flex justify-content-between">
                                    <span>Pickup Fee:</span>
                                    <strong id="display_pickup_fee">₱50.00</strong>
                                </div>
                                <div class="d-flex justify-content-between">
                                    <span>Delivery Fee:</span>
                                    <strong id="display_delivery_fee">₱50.00</strong>
                                </div>
                                <div class="d-flex justify-content-between" id="discount_row" style="display: block !important;">
                                    <span class="text-success">Discount (10%):</span>
                                    <strong class="text-success" id="display_discount">-₱10.00</strong>
                                </div>
                                <hr>
                                <div class="d-flex justify-content-between">
                                    <span><strong>Total Fee:</strong></span>
                                    <strong class="text-primary fs-5" id="display_total_fee">₱90.00</strong>
                                </div>
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
                                <div class="mb-3">
                                    <label class="form-label">Service <span class="text-danger">*</span></label>
                                    <select name="service_id" class="form-select @error('service_id') is-invalid @enderror" required>
                                        <option value="">Select Service</option>
                                        @foreach($services as $service)
                                            <option value="{{ $service->id }}" {{ old('service_id') == $service->id ? 'selected' : '' }}>
                                                {{ $service->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('service_id')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            <div class="col-md-3">
                                <div class="mb-3">
                                    <label class="form-label">Preferred Date <span class="text-danger">*</span></label>
                                    <input type="date" name="preferred_date" class="form-control @error('preferred_date') is-invalid @enderror"
                                           value="{{ old('preferred_date') }}" min="{{ date('Y-m-d') }}" required>
                                    @error('preferred_date')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            <div class="col-md-3">
                                <div class="mb-3">
                                    <label class="form-label">Time Slot <span class="text-danger">*</span></label>
                                    <select name="preferred_time_slot" class="form-select @error('preferred_time_slot') is-invalid @enderror" required>
                                        <option value="">Select Time</option>
                                        <option value="8:00 AM - 10:00 AM" {{ old('preferred_time_slot') == '8:00 AM - 10:00 AM' ? 'selected' : '' }}>8:00 AM - 10:00 AM</option>
                                        <option value="10:00 AM - 12:00 PM" {{ old('preferred_time_slot') == '10:00 AM - 12:00 PM' ? 'selected' : '' }}>10:00 AM - 12:00 PM</option>
                                        <option value="1:00 PM - 3:00 PM" {{ old('preferred_time_slot') == '1:00 PM - 3:00 PM' ? 'selected' : '' }}>1:00 PM - 3:00 PM</option>
                                        <option value="3:00 PM - 5:00 PM" {{ old('preferred_time_slot') == '3:00 PM - 5:00 PM' ? 'selected' : '' }}>3:00 PM - 5:00 PM</option>
                                    </select>
                                    @error('preferred_time_slot')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Pickup Address <span class="text-danger">*</span></label>
                            <textarea name="pickup_address" class="form-control @error('pickup_address') is-invalid @enderror"
                                      rows="2" placeholder="House No., Street, Barangay" required>{{ old('pickup_address') }}</textarea>
                            @error('pickup_address')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Landmark (Optional)</label>
                            <input type="text" name="landmark" class="form-control @error('landmark') is-invalid @enderror"
                                   value="{{ old('landmark') }}" placeholder="Near Jollibee, Beside Church, etc.">
                            @error('landmark')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Special Instructions (Optional)</label>
                            <textarea name="special_instructions" class="form-control @error('special_instructions') is-invalid @enderror"
                                      rows="3" placeholder="Any special requests or notes...">{{ old('special_instructions') }}</textarea>
                            @error('special_instructions')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                </div>
            </div>

            {{-- Summary Sidebar --}}
            <div class="col-lg-4">
                <div class="card sticky-top" style="top: 20px;">
                    <div class="card-header bg-dark text-white">
                        <h5 class="mb-0"><i class="bi bi-receipt"></i> Pickup Summary</h5>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <small class="text-muted">Service Type</small>
                            <p class="mb-0"><strong id="summary_service_type">Pickup & Delivery</strong></p>
                        </div>

                        <div class="mb-3">
                            <small class="text-muted">Fees</small>
                            <div class="d-flex justify-content-between">
                                <span>Pickup Fee:</span>
                                <span id="summary_pickup_fee">₱50.00</span>
                            </div>
                            <div class="d-flex justify-content-between">
                                <span>Delivery Fee:</span>
                                <span id="summary_delivery_fee">₱50.00</span>
                            </div>
                            <hr>
                            <div class="d-flex justify-content-between">
                                <strong>Total Fee:</strong>
                                <strong class="text-primary" id="summary_total_fee">₱90.00</strong>
                            </div>
                        </div>

                        <div class="alert alert-warning">
                            <i class="bi bi-info-circle"></i>
                            <small>This fee will be added to the final order amount when creating an order from this pickup.</small>
                        </div>

                        <button type="submit" class="btn btn-primary w-100">
                            <i class="bi bi-check-circle"></i> Create Pickup Request
                        </button>

                        <a href="{{ route('staff.pickups.index') }}" class="btn btn-outline-secondary w-100 mt-2">
                            <i class="bi bi-x-circle"></i> Cancel
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>
@endsection

@push('scripts')
<script>
// Fee calculation data (from PHP)
const feeData = {
    pickupFee: {{ $deliveryFees->pickup_fee ?? 50 }},
    deliveryFee: {{ $deliveryFees->delivery_fee ?? 50 }},
    bothDiscount: {{ $deliveryFees->both_discount ?? 10 }}
};

// Calculate and display fees based on service type
function updateFees() {
    const serviceType = document.querySelector('input[name="service_type"]:checked')?.value || 'both';

    let pickupFee = 0;
    let deliveryFee = 0;
    let discount = 0;
    let total = 0;

    switch(serviceType) {
        case 'pickup_only':
            pickupFee = feeData.pickupFee;
            total = pickupFee;
            break;
        case 'delivery_only':
            deliveryFee = feeData.deliveryFee;
            total = deliveryFee;
            break;
        case 'both':
            pickupFee = feeData.pickupFee;
            deliveryFee = feeData.deliveryFee;
            const totalBeforeDiscount = pickupFee + deliveryFee;
            discount = (totalBeforeDiscount * feeData.bothDiscount) / 100;
            total = totalBeforeDiscount - discount;
            pickupFee = pickupFee * (1 - feeData.bothDiscount / 100);
            deliveryFee = deliveryFee * (1 - feeData.bothDiscount / 100);
            break;
    }

    // Update fee breakdown
    document.getElementById('display_pickup_fee').textContent = '₱' + pickupFee.toFixed(2);
    document.getElementById('display_delivery_fee').textContent = '₱' + deliveryFee.toFixed(2);
    document.getElementById('display_total_fee').textContent = '₱' + total.toFixed(2);
    document.getElementById('display_discount').textContent = '-₱' + discount.toFixed(2);

    // Show/hide discount row
    const discountRow = document.getElementById('discount_row');
    if (discount > 0) {
        discountRow.style.display = 'block';
    } else {
        discountRow.style.display = 'none';
    }

    // Update summary
    document.getElementById('summary_service_type').textContent =
        serviceType === 'pickup_only' ? 'Pickup Only' :
        serviceType === 'delivery_only' ? 'Delivery Only' :
        'Pickup & Delivery';
    document.getElementById('summary_pickup_fee').textContent = '₱' + pickupFee.toFixed(2);
    document.getElementById('summary_delivery_fee').textContent = '₱' + deliveryFee.toFixed(2);
    document.getElementById('summary_total_fee').textContent = '₱' + total.toFixed(2);
}

// Event listeners
document.querySelectorAll('input[name="service_type"]').forEach(radio => {
    radio.addEventListener('change', updateFees);
});

// Initialize on page load
document.addEventListener('DOMContentLoaded', updateFees);
</script>
@endpush

@push('styles')
<style>
    .cursor-pointer {
        cursor: pointer;
    }

    .card input[type="radio"] {
        display: none;
    }

    .card input[type="radio"]:checked + label {
        background-color: #f0f8ff;
    }

    .card:has(input[type="radio"]:checked) {
        box-shadow: 0 0 10px rgba(0, 123, 255, 0.3);
        transform: scale(1.05);
        transition: all 0.2s;
    }
</style>
@endpush
