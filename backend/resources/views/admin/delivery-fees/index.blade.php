@extends('admin.layouts.app')

@section('title', 'Delivery Fees Management')

@section('content')
<div class="container-fluid">
    {{-- Header --}}
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0">
            <i class="bi bi-truck"></i> Delivery Fees Management
        </h1>
        <a href="{{ route('admin.settings') }}" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left"></i> Back to Settings
        </a>
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

    {{-- Info Alert --}}
    <div class="alert alert-info mb-4">
        <i class="bi bi-info-circle"></i>
        Configure pickup and delivery fees for each branch. These fees will be automatically calculated when creating pickup requests.
    </div>

    {{-- Delivery Fees Table --}}
    <div class="card">
        <div class="card-header">
            <h5 class="mb-0">Branch Delivery Fees</h5>
        </div>
        <div class="card-body">
            @if($branches->count() > 0)
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Branch</th>
                                <th>Pickup Fee</th>
                                <th>Delivery Fee</th>
                                <th>Both Services Discount</th>
                                <th>Free Delivery Minimum</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($branches as $branch)
                                @php
                                    $fee = $branch->deliveryFees ?? null;
                                @endphp
                                <tr>
                                    <td>
                                        <strong>{{ $branch->name }}</strong>
                                        <br>
                                        <small class="text-muted">{{ $branch->address }}</small>
                                    </td>
                                    <td>
                                        @if($fee)
                                            <strong class="text-primary">₱{{ number_format($fee->pickup_fee, 2) }}</strong>
                                        @else
                                            <span class="text-muted">Not set</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($fee)
                                            <strong class="text-success">₱{{ number_format($fee->delivery_fee, 2) }}</strong>
                                        @else
                                            <span class="text-muted">Not set</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($fee)
                                            <span class="badge bg-info">{{ $fee->both_discount }}% OFF</span>
                                        @else
                                            <span class="text-muted">-</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($fee && $fee->minimum_laundry_for_free)
                                            <span class="badge bg-warning text-dark">
                                                ₱{{ number_format($fee->minimum_laundry_for_free, 2) }}
                                            </span>
                                        @else
                                            <span class="text-muted">No minimum</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($fee && $fee->is_active)
                                            <span class="badge bg-success">Active</span>
                                        @else
                                            <span class="badge bg-secondary">Inactive</span>
                                        @endif
                                    </td>
                                    <td>
                                        <div class="btn-group">
                                            <button type="button"
                                                    class="btn btn-sm btn-primary"
                                                    data-bs-toggle="modal"
                                                    data-bs-target="#editModal{{ $branch->id }}">
                                                <i class="bi bi-pencil"></i> Configure
                                            </button>
                                        </div>
                                    </td>
                                </tr>

                                {{-- Edit Modal for each branch --}}
                                <div class="modal fade" id="editModal{{ $branch->id }}" tabindex="-1">
                                    <div class="modal-dialog modal-lg">
                                        <div class="modal-content">
                                            <form action="{{ route('admin.delivery-fees.update', $branch->id) }}" method="POST">
                                                @csrf
                                                @method('PUT')
                                                <div class="modal-header">
                                                    <h5 class="modal-title">Configure Fees - {{ $branch->name }}</h5>
                                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                </div>
                                                <div class="modal-body">
                                                    <div class="row">
                                                        <div class="col-md-6">
                                                            <div class="mb-3">
                                                                <label class="form-label">Pickup Fee (₱) <span class="text-danger">*</span></label>
                                                                <input type="number"
                                                                       name="pickup_fee"
                                                                       class="form-control"
                                                                       value="{{ $fee->pickup_fee ?? 50 }}"
                                                                       step="0.01"
                                                                       min="0"
                                                                       required>
                                                                <small class="text-muted">Fee charged for picking up laundry from customer</small>
                                                            </div>
                                                        </div>

                                                        <div class="col-md-6">
                                                            <div class="mb-3">
                                                                <label class="form-label">Delivery Fee (₱) <span class="text-danger">*</span></label>
                                                                <input type="number"
                                                                       name="delivery_fee"
                                                                       class="form-control"
                                                                       value="{{ $fee->delivery_fee ?? 50 }}"
                                                                       step="0.01"
                                                                       min="0"
                                                                       required>
                                                                <small class="text-muted">Fee charged for delivering cleaned laundry</small>
                                                            </div>
                                                        </div>
                                                    </div>

                                                    <div class="row">
                                                        <div class="col-md-6">
                                                            <div class="mb-3">
                                                                <label class="form-label">Discount for Both Services (%) <span class="text-danger">*</span></label>
                                                                <input type="number"
                                                                       name="both_discount"
                                                                       class="form-control"
                                                                       value="{{ $fee->both_discount ?? 10 }}"
                                                                       step="0.01"
                                                                       min="0"
                                                                       max="100"
                                                                       required>
                                                                <small class="text-muted">Discount when customer uses both pickup & delivery</small>
                                                            </div>
                                                        </div>

                                                        <div class="col-md-6">
                                                            <div class="mb-3">
                                                                <label class="form-label">Free Delivery Minimum (₱)</label>
                                                                <input type="number"
                                                                       name="minimum_laundry_for_free"
                                                                       class="form-control"
                                                                       value="{{ $fee->minimum_laundry_for_free ?? '' }}"
                                                                       step="0.01"
                                                                       min="0"
                                                                       placeholder="Leave empty for no minimum">
                                                                <small class="text-muted">Free delivery when laundry total reaches this amount</small>
                                                            </div>
                                                        </div>
                                                    </div>

                                                    <div class="mb-3">
                                                        <div class="form-check form-switch">
                                                            <input class="form-check-input"
                                                                   type="checkbox"
                                                                   name="is_active"
                                                                   id="is_active{{ $branch->id }}"
                                                                   value="1"
                                                                   {{ ($fee && $fee->is_active) || !$fee ? 'checked' : '' }}>
                                                            <label class="form-check-label" for="is_active{{ $branch->id }}">
                                                                Active (Apply these fees for new pickup requests)
                                                            </label>
                                                        </div>
                                                    </div>

                                                    {{-- Fee Preview --}}
                                                    <div class="alert alert-info">
                                                        <h6><i class="bi bi-calculator"></i> Fee Preview</h6>
                                                        <div class="row">
                                                            <div class="col-md-4">
                                                                <small class="text-muted">Pickup Only</small>
                                                                <h5 class="text-primary">₱{{ number_format($fee->pickup_fee ?? 50, 2) }}</h5>
                                                            </div>
                                                            <div class="col-md-4">
                                                                <small class="text-muted">Delivery Only</small>
                                                                <h5 class="text-success">₱{{ number_format($fee->delivery_fee ?? 50, 2) }}</h5>
                                                            </div>
                                                            <div class="col-md-4">
                                                                <small class="text-muted">Both (with discount)</small>
                                                                @php
                                                                    $pickupFee = $fee->pickup_fee ?? 50;
                                                                    $deliveryFee = $fee->delivery_fee ?? 50;
                                                                    $discount = $fee->both_discount ?? 10;
                                                                    $bothTotal = ($pickupFee + $deliveryFee) * (1 - $discount / 100);
                                                                @endphp
                                                                <h5 class="text-warning">₱{{ number_format($bothTotal, 2) }}</h5>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="modal-footer">
                                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                                    <button type="submit" class="btn btn-primary">
                                                        <i class="bi bi-save"></i> Save Fees
                                                    </button>
                                                </div>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <div class="text-center py-5">
                    <i class="bi bi-building fs-1 text-muted"></i>
                    <p class="text-muted mt-2">No branches found. Please create branches first.</p>
                    <a href="{{ route('admin.branches.create') }}" class="btn btn-primary">
                        <i class="bi bi-plus-circle"></i> Create Branch
                    </a>
                </div>
            @endif
        </div>
    </div>

    {{-- Fee Calculation Example --}}
    <div class="card mt-4">
        <div class="card-header bg-light">
            <h5 class="mb-0"><i class="bi bi-lightbulb"></i> How Fee Calculation Works</h5>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-4">
                    <h6>Pickup Only</h6>
                    <p class="text-muted">Customer only needs pickup service</p>
                    <div class="border rounded p-3 bg-light">
                        <div class="d-flex justify-content-between">
                            <span>Pickup Fee:</span>
                            <strong>₱50.00</strong>
                        </div>
                        <div class="d-flex justify-content-between">
                            <span>Delivery Fee:</span>
                            <span>₱0.00</span>
                        </div>
                        <hr>
                        <div class="d-flex justify-content-between">
                            <strong>Total:</strong>
                            <strong class="text-primary">₱50.00</strong>
                        </div>
                    </div>
                </div>

                <div class="col-md-4">
                    <h6>Delivery Only</h6>
                    <p class="text-muted">Customer brings laundry, needs delivery</p>
                    <div class="border rounded p-3 bg-light">
                        <div class="d-flex justify-content-between">
                            <span>Pickup Fee:</span>
                            <span>₱0.00</span>
                        </div>
                        <div class="d-flex justify-content-between">
                            <span>Delivery Fee:</span>
                            <strong>₱50.00</strong>
                        </div>
                        <hr>
                        <div class="d-flex justify-content-between">
                            <strong>Total:</strong>
                            <strong class="text-primary">₱50.00</strong>
                        </div>
                    </div>
                </div>

                <div class="col-md-4">
                    <h6>Both Services (Full Service)</h6>
                    <p class="text-muted">Pickup AND delivery with discount</p>
                    <div class="border rounded p-3 bg-light">
                        <div class="d-flex justify-content-between">
                            <span>Pickup Fee:</span>
                            <span>₱50.00</span>
                        </div>
                        <div class="d-flex justify-content-between">
                            <span>Delivery Fee:</span>
                            <span>₱50.00</span>
                        </div>
                        <div class="d-flex justify-content-between text-success">
                            <span>Discount (10%):</span>
                            <span>-₱10.00</span>
                        </div>
                        <hr>
                        <div class="d-flex justify-content-between">
                            <strong>Total:</strong>
                            <strong class="text-success">₱90.00</strong>
                        </div>
                    </div>
                </div>
            </div>

            <div class="alert alert-warning mt-3">
                <i class="bi bi-star-fill"></i>
                <strong>Free Delivery Feature:</strong> When a customer's laundry reaches the minimum amount you set,
                both pickup and delivery fees are automatically waived!
            </div>
        </div>
    </div>
</div>
@endsection
