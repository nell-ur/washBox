@extends('admin.layouts.app')
@section('page-title', 'Promotions Management')
@section('title', 'Create Fixed-Price Promotion')

@section('content')
<div class="container-fluid px-4 py-4">
    {{-- Header --}}
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="fw-bold text-dark mb-1">Create Fixed-Price Promotion</h2>
            <p class="text-muted small">Set a special fixed price per load (e.g., ₱179 per load)</p>
        </div>
        <a href="{{ route('admin.promotions.index') }}" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left"></i> Back to Promotions
        </a>
    </div>

    {{-- Example Preview --}}
    <div class="row mb-4">
        <div class="col-md-4">
            <div class="card border-0 shadow-sm">
                <div class="card-body text-center p-4" style="background: linear-gradient(135deg, #0EA5E9 0%, #0284C7 100%);">
                    <div class="text-white">
                        <h5 class="mb-2 fw-bold">WASHBOX DROP-OFF PROMO</h5>
                        <div class="display-4 fw-bold mb-2">₱179</div>
                        <p class="mb-0 opacity-75">PER LOAD</p>
                        <small class="d-block mt-3 opacity-50">Example fixed-price promotion</small>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-8">
            <div class="alert alert-info border-0 mb-0">
                <h6 class="alert-heading"><i class="bi bi-info-circle me-2"></i>What is a Fixed-Price Promotion?</h6>
                <p class="mb-2">Fixed-price promotions set a special price per load, regardless of the regular service price.</p>
                <p class="mb-0"><strong>Example:</strong> Instead of charging ₱45/kg (₱360 for 8kg), you offer a fixed ₱179 for the same load.</p>
            </div>
        </div>
    </div>

    <form action="{{ route('admin.promotions.store') }}" method="POST" enctype="multipart/form-data">
        @csrf
        {{-- Hidden field to set application type --}}
        <input type="hidden" name="application_type" value="per_load_override">

        <div class="row g-4">
            {{-- Left Column - Form --}}
            <div class="col-lg-8">
                {{-- Basic Information --}}
                <div class="card border-0 shadow-sm rounded-4 mb-4">
                    <div class="card-header bg-white border-bottom py-3">
                        <h6 class="mb-0 fw-bold text-dark">
                            <i class="bi bi-info-circle me-2" style="color: #3D3B6B;"></i>
                            Basic Information
                        </h6>
                    </div>
                    <div class="card-body p-4">
                        <div class="row g-3">
                            <div class="col-md-8">
                                <label class="form-label fw-semibold">Promotion Name <span class="text-danger">*</span></label>
                                <input type="text"
                                       name="name"
                                       class="form-control @error('name') is-invalid @enderror"
                                       placeholder="e.g., Drop-Off Special, Weekend Promo"
                                       value="{{ old('name') }}"
                                       required>
                                @error('name')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-4">
                                <label class="form-label fw-semibold">Promo Code</label>
                                <input type="text"
                                       name="promo_code"
                                       class="form-control @error('promo_code') is-invalid @enderror"
                                       placeholder="e.g., DROP179"
                                       value="{{ old('promo_code') }}"
                                       style="text-transform: uppercase;">
                                <small class="text-muted">Optional</small>
                                @error('promo_code')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-12">
                                <label class="form-label fw-semibold">Description</label>
                                <textarea name="description"
                                          class="form-control @error('description') is-invalid @enderror"
                                          rows="2"
                                          placeholder="Brief description of the promotion">{{ old('description') }}</textarea>
                                @error('description')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Pricing --}}
                <div class="card border-0 shadow-sm rounded-4 mb-4">
                    <div class="card-header bg-primary bg-opacity-10 border-bottom border-primary">
                        <h6 class="mb-0 fw-bold text-primary">
                            <i class="bi bi-tag me-2"></i>Fixed Price Settings
                        </h6>
                    </div>
                    <div class="card-body p-4">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Fixed Price (₱) <span class="text-danger">*</span></label>
                                <div class="input-group input-group-lg">
                                    <span class="input-group-text">₱</span>
                                    <input type="number"
                                           name="display_price"
                                           id="display_price"
                                           class="form-control @error('display_price') is-invalid @enderror"
                                           placeholder="179.00"
                                           step="0.01"
                                           min="0"
                                           value="{{ old('display_price') }}"
                                           required>
                                    @error('display_price')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                                <small class="text-muted">The special price per load</small>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Price Unit <span class="text-danger">*</span></label>
                                <input type="text"
                                       name="price_unit"
                                       id="price_unit"
                                       class="form-control @error('price_unit') is-invalid @enderror"
                                       placeholder="e.g., per load, per 8kg load"
                                       value="{{ old('price_unit', 'per load') }}"
                                       required>
                                @error('price_unit')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-12">
                                <div class="alert alert-warning border-0 mb-0">
                                    <i class="bi bi-exclamation-triangle me-2"></i>
                                    <strong>Note:</strong> When this promotion is applied, the laundry total will be:
                                    <strong>₱<span id="pricePreview">{{ old('display_price', '179') }}</span> × Number of Loads + Add-ons</strong>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Schedule --}}
                <div class="card border-0 shadow-sm rounded-4 mb-4">
                    <div class="card-header bg-white border-bottom py-3">
                        <h6 class="mb-0 fw-bold text-dark">
                            <i class="bi bi-calendar-check me-2" style="color: #3D3B6B;"></i>
                            Validity Period
                        </h6>
                    </div>
                    <div class="card-body p-4">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Start Date <span class="text-danger">*</span></label>
                                <input type="date"
                                       name="start_date"
                                       class="form-control @error('start_date') is-invalid @enderror"
                                       value="{{ old('start_date', now()->format('Y-m-d')) }}"
                                       required>
                                @error('start_date')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-6">
                                <label class="form-label fw-semibold">End Date <span class="text-danger">*</span></label>
                                <input type="date"
                                       name="end_date"
                                       class="form-control @error('end_date') is-invalid @enderror"
                                       value="{{ old('end_date', now()->addDays(30)->format('Y-m-d')) }}"
                                       required>
                                @error('end_date')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Optional Settings --}}
                <div class="card border-0 shadow-sm rounded-4 mb-4">
                    <div class="card-header bg-white border-bottom py-3">
                        <h6 class="mb-0 fw-bold text-dark">
                            <i class="bi bi-sliders me-2" style="color: #3D3B6B;"></i>
                            Additional Settings
                        </h6>
                    </div>
                    <div class="card-body p-4">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Branch</label>
                                <select name="branch_id" class="form-select @error('branch_id') is-invalid @enderror">
                                    <option value="">All Branches</option>
                                    @foreach($branches as $branch)
                                        <option value="{{ $branch->id }}" {{ old('branch_id') == $branch->id ? 'selected' : '' }}>
                                            {{ $branch->name }}
                                        </option>
                                    @endforeach
                                </select>
                                <small class="text-muted">Leave empty for all branches</small>
                                @error('branch_id')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Display Laundry</label>
                                <input type="number"
                                       name="display_laundry"
                                       class="form-control"
                                       value="{{ old('display_laundry', 0) }}"
                                       min="0">
                                <small class="text-muted">Lower numbers appear first</small>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Maximum Usage</label>
                                <input type="number"
                                       name="max_usage"
                                       class="form-control"
                                       value="{{ old('max_usage') }}"
                                       min="1"
                                       placeholder="Unlimited">
                                <small class="text-muted">Leave empty for unlimited</small>
                            </div>

                            <div class="col-md-6">
                                <div class="form-check form-switch mt-4">
                                    <input type="checkbox"
                                           name="is_active"
                                           class="form-check-input"
                                           id="isActive"
                                           {{ old('is_active', true) ? 'checked' : '' }}>
                                    <label class="form-check-label fw-semibold" for="isActive">
                                        Active (visible to staff)
                                    </label>
                                </div>
                            </div>

                            <div class="col-12">
                                <label class="form-label fw-semibold">Banner Image</label>
                                <input type="file"
                                       name="banner_image"
                                       class="form-control @error('banner_image') is-invalid @enderror"
                                       accept="image/*">
                                <small class="text-muted">Optional promotional banner (800x400px recommended)</small>
                                @error('banner_image')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Action Buttons --}}
                <div class="d-flex gap-2 mb-4">
                    <button type="submit" class="btn btn-primary px-5" style="background: #3D3B6B; border: none;">
                        <i class="bi bi-check-circle me-2"></i>Create Promotion
                    </button>
                    <a href="{{ route('admin.promotions.index') }}" class="btn btn-outline-secondary px-4">
                        <i class="bi bi-x-circle me-2"></i>Cancel
                    </a>
                </div>
            </div>

            {{-- Right Column - Preview --}}
            <div class="col-lg-4">
                <div class="sticky-top" style="top: 20px;">
                    <div class="card border-0 shadow-sm rounded-4">
                        <div class="card-header text-white py-3" style="background: #3D3B6B;">
                            <h6 class="mb-0 fw-bold">
                                <i class="bi bi-eye me-2"></i>Preview
                            </h6>
                        </div>
                        <div class="card-body p-0">
                            {{-- Preview Display --}}
                            <div class="p-4 text-center" style="background: linear-gradient(135deg, #0EA5E9 0%, #0284C7 100%); min-height: 250px;">
                                <div class="text-white">
                                    <i class="bi bi-tag-fill display-1 mb-3 opacity-50"></i>
                                    <div class="display-4 fw-bold mb-2" id="preview-price">₱179</div>
                                    <h5 class="mb-0" id="preview-unit">per load</h5>
                                </div>
                            </div>

                            {{-- Preview Details --}}
                            <div class="p-4">
                                <div class="mb-3">
                                    <small class="text-muted text-uppercase fw-semibold d-block mb-1">Promotion Name</small>
                                    <div class="text-dark fw-bold" id="preview-name">Fixed-Price Promo</div>
                                </div>

                                <div class="mb-3">
                                    <small class="text-muted text-uppercase fw-semibold d-block mb-1">Example Calculation</small>
                                    <div class="bg-light p-2 rounded">
                                        <small>
                                            <strong>2 loads</strong> × <strong id="calc-price">₱179</strong> =
                                            <strong class="text-success" id="calc-total">₱358</strong>
                                        </small>
                                        <br>
                                        <small class="text-muted">+ Add-ons (if selected)</small>
                                    </div>
                                </div>

                                <div class="mb-3">
                                    <small class="text-muted text-uppercase fw-semibold d-block mb-1">Status</small>
                                    <span class="badge bg-success">Active & Ready</span>
                                </div>
                            </div>
                        </div>
                        <div class="card-footer bg-light text-center py-3">
                            <small class="text-muted">
                                <i class="bi bi-info-circle"></i> Preview updates as you type
                            </small>
                        </div>
                    </div>

                    {{-- Help Card --}}
                    <div class="card border-0 shadow-sm rounded-4 mt-3">
                        <div class="card-body p-4">
                            <h6 class="fw-bold mb-3">
                                <i class="bi bi-lightbulb text-warning me-2"></i>Tips
                            </h6>
                            <ul class="small text-muted mb-0 ps-3">
                                <li class="mb-2">Perfect for drop-off or self-service promos</li>
                                <li class="mb-2">Staff will see this in the laundry creation form</li>
                                <li class="mb-2">Price applies per load, plus any add-ons</li>
                                <li class="mb-2">Can be limited to specific branches</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Update price preview
    const priceInput = document.getElementById('display_price');
    const unitInput = document.getElementById('price_unit');
    const nameInput = document.querySelector('input[name="name"]');

    function updatePreview() {
        const price = priceInput.value || '179';
        const unit = unitInput.value || 'per load';
        const name = nameInput.value || 'Fixed-Price Promo';

        // Update preview elements
        document.getElementById('preview-price').textContent = '₱' + price;
        document.getElementById('preview-unit').textContent = unit;
        document.getElementById('preview-name').textContent = name;
        document.getElementById('pricePreview').textContent = price;

        // Update calculation example
        const total = (parseFloat(price) || 0) * 2;
        document.getElementById('calc-price').textContent = '₱' + price;
        document.getElementById('calc-total').textContent = '₱' + total.toFixed(0);
    }

    priceInput.addEventListener('input', updatePreview);
    unitInput.addEventListener('input', updatePreview);
    nameInput.addEventListener('input', updatePreview);

    // Initial update
    updatePreview();
});
</script>
@endpush

@push('styles')
<style>
    .sticky-top {
        position: sticky;
        z-index: 1020;
    }

    .form-label {
        color: #374151;
        font-size: 0.875rem;
    }

    input[name="promo_code"] {
        text-transform: uppercase;
    }
</style>
@endpush
@endsection
