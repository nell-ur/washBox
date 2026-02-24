@extends('admin.layouts.app')

@section('title', 'Receipt for Order #' . $laundry->tracking_number)

@section('content')
<div class="container-fluid py-4">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card border-0 shadow-lg">
                {{-- Receipt Header --}}
                <div class="card-header bg-white py-4 border-bottom">
                    <div class="row align-items-center">
                        <div class="col-md-6">
                            <h1 class="h3 fw-bold text-dark mb-0">RECEIPT</h1>
                            <p class="text-muted mb-0">WashBox Laundry Service</p>
                        </div>
                        <div class="col-md-6 text-end">
                            <div class="d-flex flex-column">
                                <span class="text-muted small">Receipt No:</span>
                                <strong class="fs-5">{{ $laundry->tracking_number }}</strong>
                                <span class="text-muted small mt-2">{{ $laundry->created_at->format('M d, Y h:i A') }}</span>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Receipt Body --}}
                <div class="card-body p-4">
                    {{-- Customer Info --}}
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <h6 class="text-muted mb-2">BILLED TO</h6>
                            <p class="mb-1">
                                <strong>{{ $laundry->customer->name }}</strong>
                            </p>
                            @if($laundry->customer->phone)
                                <p class="mb-1">
                                    <i class="bi bi-telephone"></i> {{ $laundry->customer->phone }}
                                </p>
                            @endif
                            @if($laundry->customer->address)
                                <p class="mb-0">
                                    <i class="bi bi-geo-alt"></i> {{ $laundry->customer->address }}
                                </p>
                            @endif
                        </div>
                        <div class="col-md-6 text-end">
                            <h6 class="text-muted mb-2">ORDER INFO</h6>
                            <p class="mb-1">
                                <strong>Status:</strong>
                                <span class="badge bg-{{ $laundry->status == 'completed' ? 'success' : ($laundry->status == 'cancelled' ? 'danger' : 'warning') }}">
                                    {{ ucfirst($laundry->status) }}
                                </span>
                            </p>
                            <p class="mb-1">
                                <strong>Branch:</strong> {{ $laundry->branch->name }}
                            </p>
                            @if($laundry->staff)
                                <p class="mb-0">
                                    <strong>Staff:</strong> {{ $laundry->staff->name }}
                                </p>
                            @endif
                        </div>
                    </div>

                    {{-- Order Details Table --}}
                    <div class="table-responsive mb-4">
                        <table class="table table-bordered">
                            <thead class="table-light">
                                <tr>
                                    <th width="50%">Description</th>
                                    <th class="text-center">Qty</th>
                                    <th class="text-end">Price</th>
                                    <th class="text-end">Amount</th>
                                </tr>
                            </thead>
                            <tbody>
                                {{-- Service Item --}}
                                @if($laundry->service)
                                <tr>
                                    <td>
                                        <strong>{{ $laundry->service->name }}</strong>
                                        <div class="small text-muted">
                                            @if($laundry->service->pricing_type == 'per_load')
                                                {{ $laundry->service->service_type == 'special_item' ? 'Per piece' : 'Per load' }}
                                            @else
                                                Per kg
                                            @endif
                                        </div>
                                    </td>
                                    <td class="text-center">
                                        @if($laundry->service->pricing_type == 'per_load')
                                            {{ $laundry->number_of_loads ?? 1 }}
                                            {{ $laundry->service->service_type == 'special_item' ? 'pcs' : 'loads' }}
                                        @else
                                            {{ number_format($laundry->weight, 2) }} kg
                                        @endif
                                    </td>
                                    <td class="text-end">
                                        @if($laundry->service->pricing_type == 'per_load')
                                            ₱{{ number_format($laundry->service->price_per_load, 2) }}
                                        @else
                                            ₱{{ number_format($laundry->service->price_per_piece, 2) }}
                                        @endif
                                    </td>
                                    <td class="text-end fw-bold">
                                        ₱{{ number_format($laundry->subtotal, 2) }}
                                    </td>
                                </tr>
                                @endif

                                {{-- Add-ons --}}
                                @if($laundry->addons->count() > 0)
                                    @foreach($laundry->addons as $addon)
                                    <tr>
                                        <td>
                                            <strong>{{ $addon->name }}</strong>
                                            <div class="small text-muted">Add-on</div>
                                        </td>
                                        <td class="text-center">{{ $addon->pivot->quantity }}</td>
                                        <td class="text-end">₱{{ number_format($addon->pivot->price_at_purchase, 2) }}</td>
                                        <td class="text-end">₱{{ number_format($addon->pivot->price_at_purchase * $addon->pivot->quantity, 2) }}</td>
                                    </tr>
                                    @endforeach
                                @endif

                                {{-- Pickup Fee --}}
                                @if($laundry->pickup_fee > 0)
                                <tr>
                                    <td colspan="3">
                                        <strong><i class="bi bi-arrow-down-circle text-primary"></i> Pickup Fee</strong>
                                    </td>
                                    <td class="text-end">₱{{ number_format($laundry->pickup_fee, 2) }}</td>
                                </tr>
                                @endif

                                {{-- Delivery Fee --}}
                                @if($laundry->delivery_fee > 0)
                                <tr>
                                    <td colspan="3">
                                        <strong><i class="bi bi-arrow-up-circle text-success"></i> Delivery Fee</strong>
                                    </td>
                                    <td class="text-end">₱{{ number_format($laundry->delivery_fee, 2) }}</td>
                                </tr>
                                @endif

                                {{-- Discount --}}
                                @if($laundry->discount_amount > 0)
                                <tr class="table-success">
                                    <td colspan="3">
                                        <strong><i class="bi bi-tag text-success"></i> Discount</strong>
                                        @if($laundry->promotion)
                                            <div class="small text-muted">{{ $laundry->promotion->name }}</div>
                                        @endif
                                    </td>
                                    <td class="text-end text-success">-₱{{ number_format($laundry->discount_amount, 2) }}</td>
                                </tr>
                                @endif
                            </tbody>
                            <tfoot class="table-light">
                                <tr>
                                    <th colspan="3" class="text-end">Subtotal:</th>
                                    <th class="text-end">₱{{ number_format($laundry->subtotal, 2) }}</th>
                                </tr>

                                @if($laundry->addons_total > 0)
                                <tr>
                                    <th colspan="3" class="text-end">Add-ons Total:</th>
                                    <th class="text-end">₱{{ number_format($laundry->addons_total, 2) }}</th>
                                </tr>
                                @endif

                                @if($laundry->pickup_fee > 0 || $laundry->delivery_fee > 0)
                                <tr>
                                    <th colspan="3" class="text-end">Service Fees:</th>
                                    <th class="text-end">₱{{ number_format($laundry->pickup_fee + $laundry->delivery_fee, 2) }}</th>
                                </tr>
                                @endif

                                @if($laundry->discount_amount > 0)
                                <tr>
                                    <th colspan="3" class="text-end">Discount:</th>
                                    <th class="text-end text-success">-₱{{ number_format($laundry->discount_amount, 2) }}</th>
                                </tr>
                                @endif

                                <tr>
                                    <th colspan="3" class="text-end fs-5">GRAND TOTAL:</th>
                                    <th class="text-end fs-4 text-primary">₱{{ number_format($laundry->total_amount, 2) }}</th>
                                </tr>
                            </tfoot>
                        </table>
                    </div>

                    {{-- Payment Info --}}
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <h6 class="text-muted mb-2">PAYMENT DETAILS</h6>
                            @if($laundry->payment_method)
                                <p class="mb-1">
                                    <strong>Method:</strong> {{ ucfirst($laundry->payment_method) }}
                                </p>
                            @endif
                            @if($laundry->payment_status)
                                <p class="mb-1">
                                    <strong>Status:</strong>
                                    <span class="badge bg-{{ $laundry->payment_status == 'paid' ? 'success' : 'warning' }}">
                                        {{ ucfirst($laundry->payment_status) }}
                                    </span>
                                </p>
                            @endif
                            @if($laundry->paid_at)
                                <p class="mb-0">
                                    <strong>Paid at:</strong> {{ $laundry->paid_at->format('M d, Y h:i A') }}
                                </p>
                            @endif
                        </div>
                        <div class="col-md-6 text-end">
                            <h6 class="text-muted mb-2">ORDER TIMELINE</h6>
                            @if($laundry->received_at)
                                <p class="mb-1 small">
                                    <strong>Received:</strong> {{ $laundry->received_at->format('M d, Y') }}
                                </p>
                            @endif
                            @if($laundry->ready_at)
                                <p class="mb-1 small">
                                    <strong>Ready:</strong> {{ $laundry->ready_at->format('M d, Y') }}
                                </p>
                            @endif
                            @if($laundry->completed_at)
                                <p class="mb-0 small">
                                    <strong>Completed:</strong> {{ $laundry->completed_at->format('M d, Y') }}
                                </p>
                            @endif
                        </div>
                    </div>

                    {{-- Notes --}}
                    @if($laundry->notes)
                    <div class="alert alert-light border mb-4">
                        <h6 class="text-muted mb-2">NOTES</h6>
                        <p class="mb-0">{{ $laundry->notes }}</p>
                    </div>
                    @endif

                    {{-- Footer --}}
                    <div class="text-center border-top pt-4">
                        <p class="text-muted mb-1">
                            <strong>WashBox Laundry Service</strong>
                        </p>
                        <p class="text-muted small mb-1">
                            {{ $laundry->branch->address ?? 'Main Branch' }} |
                            {{ $laundry->branch->phone ?? 'Contact: N/A' }}
                        </p>
                        <p class="text-muted small mb-0">
                            Thank you for choosing our service!
                        </p>
                    </div>
                </div>

                {{-- Receipt Actions --}}
                <div class="card-footer bg-white py-3 border-top">
                    <div class="d-flex justify-content-between">
                        <a href="{{ route('admin.laundries.show', $laundry) }}" class="btn btn-outline-secondary">
                            <i class="bi bi-arrow-left"></i> Back to Order
                        </a>
                        <div class="btn-group">
                            <button onclick="window.print()" class="btn btn-primary">
                                <i class="bi bi-printer"></i> Print Receipt
                            </button>
                            <a href="{{ route('admin.laundries.show', $laundry) }}?download=1" class="btn btn-success">
                                <i class="bi bi-download"></i> Download PDF
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Print Styles --}}
<style>
@media print {
    body * {
        visibility: hidden;
    }
    .card, .card * {
        visibility: visible;
    }
    .card {
        position: absolute;
        left: 0;
        top: 0;
        width: 100%;
        border: none !important;
        box-shadow: none !important;
    }
    .btn, .card-footer {
        display: none !important;
    }
}
</style>
@endsection
