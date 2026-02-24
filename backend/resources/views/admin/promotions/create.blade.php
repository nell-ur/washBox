@extends('admin.layouts.app')
@section('page-title', 'Promotions Management')

@section('title', 'Create Promotion')

@section('content')
<div class="container-fluid px-4 py-4">
    {{-- Header --}}
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="fw-bold text-dark mb-1">Create New Promotion</h2>
            <p class="text-muted small">Set up a new discount campaign for your branches</p>
        </div>
        <a href="{{ route('admin.promotions.index') }}" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left"></i> Back to Promotions
        </a>
    </div>

    <form action="{{ route('admin.promotions.store') }}" method="POST" enctype="multipart/form-data">
        @csrf

        <div class="row g-4">
            {{-- Left Column - Main Form --}}
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
                        <div class="row">
                            <div class="col-12 mb-3">
                                <label class="form-label fw-semibold">Promotion Name <span class="text-danger">*</span></label>
                                <input type="text" name="name" class="form-control @error('name') is-invalid @enderror"
                                    placeholder="e.g., Summer Sale 2024" value="{{ old('name') }}" required>
                                <small class="text-muted">Internal name for this promotion</small>
                                @error('name')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-semibold">Discount Percentage <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <input type="number" name="discount_percent"
                                        class="form-control @error('discount_percent') is-invalid @enderror"
                                        min="1" max="100" step="1" placeholder="20"
                                        value="{{ old('discount_percent') }}" required>
                                    <span class="input-group-text">%</span>
                                </div>
                                <small class="text-muted">Percentage discount (1-100)</small>
                                @error('discount_percent')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-semibold">Promo Code</label>
                                <input type="text" name="promo_code"
                                    class="form-control @error('promo_code') is-invalid @enderror"
                                    placeholder="e.g., SUMMER20" value="{{ old('promo_code') }}"
                                    style="text-transform: uppercase;">
                                <small class="text-muted">Leave empty for auto-apply</small>
                                @error('promo_code')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-12 mb-3">
                                <label class="form-label fw-semibold">Description</label>
                                <textarea name="description" class="form-control" rows="3"
                                    placeholder="Brief description of this promotion">{{ old('description') }}</textarea>
                                <small class="text-muted">Optional description for internal reference</small>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Schedule & Targeting --}}
                <div class="card border-0 shadow-sm rounded-4 mb-4">
                    <div class="card-header bg-white border-bottom py-3">
                        <h6 class="mb-0 fw-bold text-dark">
                            <i class="bi bi-calendar-check me-2" style="color: #3D3B6B;"></i>
                            Schedule & Target
                        </h6>
                    </div>
                    <div class="card-body p-4">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-semibold">Start Date <span class="text-danger">*</span></label>
                                <input type="date" name="start_date"
                                    class="form-control @error('start_date') is-invalid @enderror"
                                    value="{{ old('start_date', date('Y-m-d')) }}" required>
                                @error('start_date')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-semibold">End Date <span class="text-danger">*</span></label>
                                <input type="date" name="end_date"
                                    class="form-control @error('end_date') is-invalid @enderror"
                                    value="{{ old('end_date') }}" required>
                                @error('end_date')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-semibold">Target Branch</label>
                                <select name="branch_id" class="form-select">
                                    <option value="">All Branches (Network Wide)</option>
                                    @foreach($branches as $branch)
                                        <option value="{{ $branch->id }}" {{ old('branch_id') == $branch->id ? 'selected' : '' }}>
                                            {{ $branch->name }}
                                        </option>
                                    @endforeach
                                </select>
                                <small class="text-muted">Leave blank for network-wide promotion</small>
                            </div>

                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-semibold">Minimum Amount (₱)</label>
                                <input type="number" name="min_amount" class="form-control"
                                    min="0" step="0.01" placeholder="0" value="{{ old('min_amount', 0) }}">
                                <small class="text-muted">Minimum Laundry amount required</small>
                            </div>

                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-semibold">Maximum Usage</label>
                                <input type="number" name="max_usage" class="form-control"
                                    min="1" placeholder="Unlimited" value="{{ old('max_usage') }}">
                                <small class="text-muted">Leave empty for unlimited usage</small>
                            </div>

                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-semibold">Status</label>
                                <select name="is_active" class="form-select">
                                    <option value="1" {{ old('is_active', '1') == '1' ? 'selected' : '' }}>Active</option>
                                    <option value="0" {{ old('is_active') == '0' ? 'selected' : '' }}>Inactive</option>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Banner Image (Optional) --}}
                <div class="card border-0 shadow-sm rounded-4 mb-4">
                    <div class="card-header bg-white border-bottom py-3">
                        <h6 class="mb-0 fw-bold text-dark">
                            <i class="bi bi-image me-2" style="color: #3D3B6B;"></i>
                            Banner Image (Optional)
                        </h6>
                    </div>
                    <div class="card-body p-4">
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Upload Banner</label>
                            <input type="file" name="banner_image" id="banner_image"
                                class="form-control @error('banner_image') is-invalid @enderror"
                                accept="image/*">
                            <small class="text-muted">Recommended: 800x400px, Max 2MB (JPG, PNG)</small>
                            @error('banner_image')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        {{-- Image Preview --}}
                        <div id="imagePreview" class="mt-3" style="display: none;">
                            <img id="previewImg" src="" class="img-fluid rounded border" style="max-height: 200px;">
                        </div>
                    </div>
                </div>

                {{-- Advanced Options (Optional) --}}
                <div class="card border-0 shadow-sm rounded-4 mb-4">
                    <div class="card-header bg-white border-bottom py-3">
                        <h6 class="mb-0 fw-bold text-dark">
                            <i class="bi bi-sliders me-2" style="color: #3D3B6B;"></i>
                            Advanced Options
                        </h6>
                    </div>
                    <div class="card-body p-4">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-semibold">Display Laundry</label>
                                <input type="number" name="display_laundry" class="form-control"
                                    min="0" value="{{ old('display_laundry', 0) }}">
                                <small class="text-muted">Lower numbers appear first</small>
                            </div>

                            <div class="col-md-6 mb-3">
                                <div class="form-check form-switch mt-4">
                                    <input class="form-check-input" type="checkbox" name="featured"
                                        id="featured" value="1" {{ old('featured') ? 'checked' : '' }}>
                                    <label class="form-check-label fw-semibold" for="featured">
                                        Featured Promotion
                                        <small class="d-block text-muted fw-normal">Show on homepage</small>
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Action Buttons --}}
                <div class="d-flex gap-2 mb-4">
                    <button type="submit" class="btn btn-primary px-5 shadow-sm" style="background: #3D3B6B; border: none;">
                        <i class="bi bi-check-circle me-2"></i>Create Promotion
                    </button>
                    <a href="{{ route('admin.promotions.index') }}" class="btn btn-outline-secondary px-4">
                        <i class="bi bi-x-circle me-2"></i>Cancel
                    </a>
                </div>
            </div>

            {{-- Right Column - Preview Card --}}
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
                            <div class="p-4 text-center" style="background: linear-gradient(135deg, #3D3B6B 0%, #2D2850 100%); min-height: 200px;">
                                <div class="text-white">
                                    <i class="bi bi-percent display-1 mb-3 opacity-50"></i>
                                    <h2 class="fw-bold mb-2" id="preview-discount">20% OFF</h2>
                                    <h5 class="mb-0" id="preview-name">Promotion Name</h5>
                                </div>
                            </div>

                            {{-- Preview Details --}}
                            <div class="p-4">
                                <div class="mb-3">
                                    <small class="text-muted text-uppercase fw-semibold d-block mb-1">Promo Code</small>
                                    <div class="bg-light p-2 rounded text-center">
                                        <code id="preview-code" class="text-primary fw-bold">----</code>
                                    </div>
                                </div>

                                <div class="mb-3">
                                    <small class="text-muted text-uppercase fw-semibold d-block mb-1">Valid Period</small>
                                    <div class="text-dark">
                                        <i class="bi bi-calendar-event me-2"></i>
                                        <span id="preview-dates">Select dates</span>
                                    </div>
                                </div>

                                <div class="mb-3">
                                    <small class="text-muted text-uppercase fw-semibold d-block mb-1">Branch</small>
                                    <div class="text-dark">
                                        <i class="bi bi-shop me-2"></i>
                                        <span id="preview-branch">All Branches</span>
                                    </div>
                                </div>

                                <div>
                                    <small class="text-muted text-uppercase fw-semibold d-block mb-1">Status</small>
                                    <span id="preview-status" class="badge bg-success">Active</span>
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
                                <li class="mb-2">Leave promo code empty for auto-apply discounts</li>
                                <li class="mb-2">Set minimum amount to encourage larger laundry</li>
                                <li class="mb-2">Use display laundry to prioritize promotions</li>
                                <li class="mb-2">Featured promotions show on homepage</li>
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
    // Real-time preview updates
    const form = document.querySelector('form');
    const previewDiscount = document.getElementById('preview-discount');
    const previewName = document.getElementById('preview-name');
    const previewCode = document.getElementById('preview-code');
    const previewDates = document.getElementById('preview-dates');
    const previewBranch = document.getElementById('preview-branch');
    const previewStatus = document.getElementById('preview-status');

    // Update discount
    const discountInput = document.querySelector('input[name="discount_percent"]');
    if (discountInput) {
        discountInput.addEventListener('input', function() {
            const value = this.value || '0';
            previewDiscount.textContent = value + '% OFF';
        });
    }

    // Update name
    const nameInput = document.querySelector('input[name="name"]');
    if (nameInput) {
        nameInput.addEventListener('input', function() {
            previewName.textContent = this.value || 'Promotion Name';
        });
    }

    // Update promo code
    const codeInput = document.querySelector('input[name="promo_code"]');
    if (codeInput) {
        codeInput.addEventListener('input', function() {
            previewCode.textContent = this.value.toUpperCase() || '----';
        });
    }

    // Update dates
    const startDate = document.querySelector('input[name="start_date"]');
    const endDate = document.querySelector('input[name="end_date"]');
    function updateDates() {
        if (startDate.value && endDate.value) {
            const start = new Date(startDate.value).toLocaleDateString('en-US', { month: 'short', day: 'numeric' });
            const end = new Date(endDate.value).toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' });
            previewDates.textContent = `${start} - ${end}`;
        } else {
            previewDates.textContent = 'Select dates';
        }
    }
    if (startDate) startDate.addEventListener('change', updateDates);
    if (endDate) endDate.addEventListener('change', updateDates);

    // Update branch
    const branchSelect = document.querySelector('select[name="branch_id"]');
    if (branchSelect) {
        branchSelect.addEventListener('change', function() {
            const selectedOption = this.options[this.selectedIndex];
            previewBranch.textContent = selectedOption.text;
        });
    }

    // Update status
    const statusSelect = document.querySelector('select[name="is_active"]');
    if (statusSelect) {
        statusSelect.addEventListener('change', function() {
            if (this.value === '1') {
                previewStatus.className = 'badge bg-success';
                previewStatus.textContent = 'Active';
            } else {
                previewStatus.className = 'badge bg-secondary';
                previewStatus.textContent = 'Inactive';
            }
        });
    }

    // Image preview
    const imageInput = document.getElementById('banner_image');
    const imagePreview = document.getElementById('imagePreview');
    const previewImg = document.getElementById('previewImg');

    if (imageInput) {
        imageInput.addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    previewImg.src = e.target.result;
                    imagePreview.style.display = 'block';
                }
                reader.readAsDataURL(file);
            } else {
                imagePreview.style.display = 'none';
            }
        });
    }

    // Initial updates
    updateDates();
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
