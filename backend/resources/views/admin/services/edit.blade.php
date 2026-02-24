@extends('admin.layouts.app')

@section('title', 'Edit Service')

@section('content')
<div class="container-fluid px-4 py-4">
    {{-- Header --}}
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="fw-bold text-dark mb-1">Edit Service</h2>
            <p class="text-muted small mb-0">Update laundry service details</p>
        </div>
        <div>
            <a href="{{ route('admin.services.show', $service) }}" class="btn btn-outline-info me-2">
                <i class="bi bi-eye me-2"></i>View Service
            </a>
            <a href="{{ route('admin.services.index') }}" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left me-2"></i>Back to Services
            </a>
        </div>
    </div>

    <form action="{{ route('admin.services.update', $service) }}" method="POST" enctype="multipart/form-data" id="serviceForm">
        @csrf
        @method('PUT')

        <div class="row g-4">
            {{-- Left Column --}}
            <div class="col-lg-8">

                {{-- Basic Information --}}
                <div class="card border-0 shadow-sm rounded-4 mb-4">
                    <div class="card-header bg-white border-bottom py-3">
                        <h6 class="mb-0 fw-bold text-dark">
                            <i class="bi bi-info-circle me-2" style="color: #3D3B6B;"></i>Basic Information
                        </h6>
                    </div>
                    <div class="card-body p-4">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">
                                    Category <span class="text-danger">*</span>
                                </label>
                                <select name="category" class="form-select @error('category') is-invalid @enderror" id="categorySelect" required>
                                    <option value="">Select Category</option>
                                    <option value="drop_off" {{ old('category', $service->category) == 'drop_off' ? 'selected' : '' }}>🛍 Drop Off</option>
                                    <option value="self_service" {{ old('category', $service->category) == 'self_service' ? 'selected' : '' }}>🧺 Self Service</option>
                                    <option value="addon" {{ old('category', $service->category) == 'addon' ? 'selected' : '' }}>➕ Add-on</option>
                                </select>
                                @error('category')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-6">
                                <label class="form-label fw-semibold">
                                    Service Type <span class="text-danger">*</span>
                                </label>
                                <select name="service_type_id" class="form-select @error('service_type_id') is-invalid @enderror" id="serviceTypeSelect" required>
                                    <option value="">Select Service Type</option>
                                    @foreach($serviceTypes ?? [] as $category => $types)
                                        <optgroup label="{{ ucfirst(str_replace('_', ' ', $category)) }}">
                                            @foreach($types as $type)
                                                <option value="{{ $type->id }}"
                                                    data-category="{{ $type->category }}"
                                                    data-defaults='{{ json_encode($type->defaults) }}'
                                                    data-description="{{ $type->description }}"
                                                    {{ old('service_type_id', $service->service_type_id) == $type->id ? 'selected' : '' }}>
                                                    {{ $type->name }}
                                                </option>
                                            @endforeach
                                        </optgroup>
                                    @endforeach
                                </select>
                                <small class="text-muted">Select a predefined service type</small>
                                @error('service_type_id')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                                <input type="hidden" name="service_type" id="serviceTypeHidden" value="{{ old('service_type', $service->service_type) }}">
                            </div>

                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Service Name <span class="text-danger">*</span></label>
                                <input type="text" name="name" id="nameInput"
                                    class="form-control @error('name') is-invalid @enderror"
                                    value="{{ old('name', $service->name) }}"
                                    placeholder="e.g., Bestseller Package" required>
                                @error('name')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-12">
                                <label class="form-label fw-semibold">Description</label>
                                <textarea name="description" id="descriptionInput" class="form-control @error('description') is-invalid @enderror"
                                    rows="3" placeholder="Describe the service details, inclusions, etc...">{{ old('description', $service->description) }}</textarea>
                                @error('description')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Pricing Configuration --}}
                <div class="card border-0 shadow-sm rounded-4 mb-4">
                    <div class="card-header bg-white border-bottom py-3">
                        <h6 class="mb-0 fw-bold text-dark">
                            <i class="bi bi-cash-coin me-2" style="color: #3D3B6B;"></i>Pricing Configuration
                        </h6>
                    </div>
                    <div class="card-body p-4">

                        {{-- Pricing Type Toggle --}}
                        <div class="mb-4">
                            <label class="form-label fw-semibold">
                                Pricing Type <span class="text-danger">*</span>
                            </label>
                            <div class="btn-group w-100" role="group" id="pricingTypeToggle">
                                <input type="radio" class="btn-check" name="pricing_type"
                                    id="pricingPerLoad" value="per_load"
                                    {{ old('pricing_type', $service->pricing_type ?? 'per_load') === 'per_load' ? 'checked' : '' }}>
                                <label class="btn btn-outline-primary" for="pricingPerLoad">
                                    <i class="bi bi-basket me-1"></i>Per Load
                                </label>

                                <input type="radio" class="btn-check" name="pricing_type"
                                    id="pricingPerPiece" value="per_piece"
                                    {{ old('pricing_type', $service->pricing_type ?? 'per_load') === 'per_piece' ? 'checked' : '' }}>
                                <label class="btn btn-outline-primary" for="pricingPerPiece">
                                    <i class="bi bi-tag me-1"></i>Per Piece
                                </label>
                            </div>
                            <small class="text-muted mt-1 d-block" id="pricingTypeHelp">
                                {{ ($service->pricing_type ?? 'per_load') === 'per_piece'
                                    ? 'Customer will be charged per individual piece'
                                    : 'Customer will be charged per laundry load' }}
                            </small>
                        </div>

                        <div class="row g-3">
                            {{-- Per Load price --}}
                            <div class="col-md-6" id="pricePerLoadContainer"
                                style="{{ old('pricing_type', $service->pricing_type ?? 'per_load') === 'per_piece' ? 'display:none' : '' }}">
                                <label class="form-label fw-semibold" id="pricePerLoadLabel">
                                    Price per Load <span class="text-danger">*</span>
                                </label>
                                <div class="input-group">
                                    <span class="input-group-text">₱</span>
                                    <input type="number" name="price_per_load" id="pricePerLoadInput"
                                        step="0.01" min="0"
                                        class="form-control @error('price_per_load') is-invalid @enderror"
                                        value="{{ old('price_per_load', $service->price_per_load) }}"
                                        placeholder="0.00"
                                        {{ old('pricing_type', $service->pricing_type ?? 'per_load') !== 'per_piece' ? 'required' : '' }}>
                                    @error('price_per_load')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                                <small class="text-muted">Fixed price per load</small>
                            </div>

                            {{-- Per Piece price --}}
                            <div class="col-md-6" id="pricePerPieceContainer"
                                style="{{ old('pricing_type', $service->pricing_type ?? 'per_load') !== 'per_piece' ? 'display:none' : '' }}">
                                <label class="form-label fw-semibold">
                                    Price per Piece <span class="text-danger">*</span>
                                </label>
                                <div class="input-group">
                                    <span class="input-group-text">₱</span>
                                    <input type="number" name="price_per_piece" id="pricePerPieceInput"
                                        step="0.01" min="0"
                                        class="form-control @error('price_per_piece') is-invalid @enderror"
                                        value="{{ old('price_per_piece', $service->price_per_piece) }}"
                                        placeholder="0.00"
                                        {{ old('pricing_type', $service->pricing_type ?? 'per_load') === 'per_piece' ? 'required' : '' }}>
                                    @error('price_per_piece')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                                <small class="text-muted">Fixed price per individual piece (e.g. comforter, suit jacket)</small>
                            </div>

                            {{-- Max Weight --}}
                            <div class="col-md-6" id="editMaxWeightField"
                                style="{{ in_array(old('service_type', $service->service_type), ['special_item', 'addon']) || old('pricing_type', $service->pricing_type ?? 'per_load') === 'per_piece' ? 'display:none' : '' }}">
                                <label class="form-label fw-semibold">Max Weight (kg)</label>
                                <input type="number" name="max_weight" id="maxWeightInput" step="0.1" min="0"
                                    class="form-control @error('max_weight') is-invalid @enderror"
                                    value="{{ old('max_weight', $service->max_weight) }}"
                                    placeholder="e.g. 8">
                                @error('max_weight')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                                <small class="text-muted">Leave blank for no weight limit</small>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Service Details --}}
                <div class="card border-0 shadow-sm rounded-4 mb-4">
                    <div class="card-header bg-white border-bottom py-3">
                        <h6 class="mb-0 fw-bold text-dark">
                            <i class="bi bi-clock-history me-2" style="color: #3D3B6B;"></i>Service Details
                        </h6>
                    </div>
                    <div class="card-body p-4">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Turnaround Time (Hours) <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <input type="number" name="turnaround_time" id="turnaroundInput" min="1" max="168" step="1"
                                        class="form-control @error('turnaround_time') is-invalid @enderror"
                                        value="{{ old('turnaround_time', $service->turnaround_time) }}"
                                        placeholder="24" required>
                                    <span class="input-group-text">hours</span>
                                    @error('turnaround_time')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                                <small class="text-muted" id="turnaroundHelp"></small>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Status</label>
                                <select name="is_active" class="form-select @error('is_active') is-invalid @enderror">
                                    <option value="1" {{ old('is_active', $service->is_active) == '1' ? 'selected' : '' }}>Active (Available)</option>
                                    <option value="0" {{ old('is_active', $service->is_active) == '0' ? 'selected' : '' }}>Inactive (Hidden)</option>
                                </select>
                                <small class="text-muted">Service availability in the system</small>
                                @error('is_active')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-12">
                                <label class="form-label fw-semibold">URL Slug</label>
                                <input type="text" name="slug" id="slugInput"
                                    class="form-control @error('slug') is-invalid @enderror"
                                    value="{{ old('slug', $service->slug) }}"
                                    placeholder="bestseller-package (auto-generated)">
                                @error('slug')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                                <small class="text-muted">Leave blank to auto-generate from service name</small>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Action Buttons --}}
                <div class="d-flex gap-2 mb-4">
                    <button type="submit" class="btn btn-primary px-5 shadow-sm" style="background: #3D3B6B; border: none;">
                        <i class="bi bi-check-circle me-2"></i>Update Service
                    </button>
                    <a href="{{ route('admin.services.index') }}" class="btn btn-outline-secondary px-4">
                        <i class="bi bi-x-circle me-2"></i>Cancel
                    </a>
                </div>
            </div>

            {{-- Right Column --}}
            <div class="col-lg-4">
                <div class="sticky-top" style="top: 20px;">

                    {{-- Icon Upload --}}
                    <div class="card border-0 shadow-sm rounded-4 mb-3">
                        <div class="card-header bg-white border-bottom py-3">
                            <h6 class="mb-0 fw-bold text-dark">
                                <i class="bi bi-image me-2" style="color: #3D3B6B;"></i>Service Icon (Optional)
                            </h6>
                        </div>
                        <div class="card-body p-4 text-center">
                            <div class="mb-3">
                                <div class="rounded-3 mx-auto d-flex align-items-center justify-content-center"
                                    id="iconPreview"
                                    style="width: 150px; height: 150px; background: linear-gradient(135deg, #3D3B6B 0%, #6366F1 100%);">
                                    @if($service->icon_path)
                                        <img src="{{ asset('storage/' . $service->icon_path) }}"
                                            class="w-100 h-100 rounded-3"
                                            style="object-fit: contain; background: white; padding: 10px;">
                                    @else
                                        <i class="bi bi-droplet text-white" style="font-size: 4rem;"></i>
                                    @endif
                                </div>
                            </div>
                            <input type="file" name="icon" id="iconInput"
                                class="form-control @error('icon') is-invalid @enderror" accept="image/*">
                            <small class="text-muted d-block mt-2">Max 2MB (JPG, PNG, SVG, GIF). Leave empty to keep current icon.</small>
                            @error('icon')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    {{-- Service Preview --}}
                    <div class="card border-0 shadow-sm rounded-4 mb-3">
                        <div class="card-header bg-white border-bottom py-3">
                            <h6 class="mb-0 fw-bold text-dark">
                                <i class="bi bi-eye me-2" style="color: #3D3B6B;"></i>Service Preview
                            </h6>
                        </div>
                        <div class="card-body p-4">
                            <div class="mb-3">
                                <label class="form-label fw-semibold">Service Name:</label>
                                <div id="previewName" class="text-dark fw-semibold">{{ $service->name }}</div>
                            </div>
                            <div class="mb-3">
                                <label class="form-label fw-semibold">Service Type:</label>
                                <div id="previewServiceType" class="text-muted">
                                    @if($service->serviceType)
                                        <span class="badge" style="background: #6c757d;">{{ $service->serviceType->name }}</span>
                                    @else
                                        {{ $service->service_type ?? 'N/A' }}
                                    @endif
                                </div>
                            </div>
                            <div class="mb-3">
                                <label class="form-label fw-semibold">Pricing:</label>
                                <div id="previewPricing" class="text-success fw-bold" style="font-size:1.25rem;">
                                    @php
                                        $initPt    = $service->pricing_type ?? 'per_load';
                                        $initPrice = $initPt === 'per_piece'
                                            ? ($service->price_per_piece ?? 0)
                                            : ($service->price_per_load  ?? 0);
                                        $initUnit  = $initPt === 'per_piece' ? 'pc' : 'load';
                                    @endphp
                                    ₱{{ number_format($initPrice, 2) }}/{{ $initUnit }}
                                </div>
                            </div>
                            <div class="mb-3">
                                <label class="form-label fw-semibold">Pricing Type:</label>
                                <div id="previewPricingType">
                                    <span class="badge {{ ($service->pricing_type ?? 'per_load') === 'per_piece' ? 'bg-warning text-dark' : 'bg-primary' }}"
                                          id="previewPricingBadge">
                                        {{ ($service->pricing_type ?? 'per_load') === 'per_piece' ? 'Per Piece' : 'Per Load' }}
                                    </span>
                                </div>
                            </div>
                            <div class="mb-0">
                                <label class="form-label fw-semibold">Turnaround:</label>
                                <div id="previewTime" class="text-muted">{{ $service->turnaround_time }} hours</div>
                            </div>
                        </div>
                    </div>

                    {{-- Quick Tips --}}
                    <div class="card border-0 shadow-sm rounded-4">
                        <div class="card-body p-4">
                            <h6 class="fw-bold mb-3">
                                <i class="bi bi-lightbulb text-warning me-2"></i>Quick Tips
                            </h6>
                            <ul class="small text-muted mb-0 ps-3">
                                <li class="mb-2"><strong>Per Load:</strong> Best for regular/full-service packages</li>
                                <li class="mb-2"><strong>Per Piece:</strong> Best for special items (comforters, blankets)</li>
                                <li class="mb-2"><strong>Max Weight:</strong> Only applies to per-load services</li>
                                <li class="mb-2"><strong>Self Service:</strong> Customer-operated (Wash, Dry, Fold)</li>
                                <li class="mb-0"><strong>Add-ons:</strong> Detergent, fabcon, extra wash, etc.</li>
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
document.addEventListener('DOMContentLoaded', function () {
    // ── Element refs ──────────────────────────────────────────────────────────
    const pricingPerLoad         = document.getElementById('pricingPerLoad');
    const pricingPerPiece        = document.getElementById('pricingPerPiece');
    const pricingTypeHelp        = document.getElementById('pricingTypeHelp');
    const pricePerLoadContainer  = document.getElementById('pricePerLoadContainer');
    const pricePerPieceContainer = document.getElementById('pricePerPieceContainer');
    const pricePerLoadInput      = document.getElementById('pricePerLoadInput');
    const pricePerPieceInput     = document.getElementById('pricePerPieceInput');
    const maxWeightField         = document.getElementById('editMaxWeightField');
    const maxWeightInput         = document.getElementById('maxWeightInput');
    const serviceTypeSelect      = document.getElementById('serviceTypeSelect');
    const categorySelect         = document.getElementById('categorySelect');
    const nameInput              = document.getElementById('nameInput');
    const descriptionInput       = document.getElementById('descriptionInput');
    const slugInput              = document.getElementById('slugInput');
    const turnaroundInput        = document.getElementById('turnaroundInput');
    const serviceTypeHidden      = document.getElementById('serviceTypeHidden');

    // Preview elements
    const previewName         = document.getElementById('previewName');
    const previewPricing      = document.getElementById('previewPricing');
    const previewPricingBadge = document.getElementById('previewPricingBadge');
    const previewTime         = document.getElementById('previewTime');

    // ── Toggle pricing type ───────────────────────────────────────────────────
    function updatePricingType() {
        const isPerPiece = pricingPerPiece.checked;

        // Show/hide price inputs and swap required
        if (isPerPiece) {
            pricePerLoadContainer.style.display  = 'none';
            pricePerPieceContainer.style.display = '';
            pricePerLoadInput.removeAttribute('required');
            pricePerPieceInput.setAttribute('required', '');
            maxWeightField.style.display = 'none'; // weight irrelevant for per-piece
            pricingTypeHelp.textContent  = 'Customer will be charged per individual piece';
        } else {
            pricePerLoadContainer.style.display  = '';
            pricePerPieceContainer.style.display = 'none';
            pricePerLoadInput.setAttribute('required', '');
            pricePerPieceInput.removeAttribute('required');

            // Restore max weight based on service type
            if (serviceTypeSelect.selectedIndex > 0) {
                const selected = serviceTypeSelect.options[serviceTypeSelect.selectedIndex];
                const defaults = JSON.parse(selected.dataset.defaults || '{}');
                if (defaults.max_weight) {
                    maxWeightField.style.display = 'block';
                } else {
                    maxWeightField.style.display = '';
                }
            }
            pricingTypeHelp.textContent  = 'Customer will be charged per laundry load';
        }

        updatePreviewPricing();
    }

    // ── Update preview panel ──────────────────────────────────────────────────
    function updatePreviewPricing() {
        const isPerPiece = pricingPerPiece.checked;
        const price      = isPerPiece
            ? (parseFloat(pricePerPieceInput.value) || 0)
            : (parseFloat(pricePerLoadInput.value)  || 0);
        const unit = isPerPiece ? 'pc' : 'load';

        previewPricing.textContent = `₱${price.toFixed(2)}/${unit}`;

        if (isPerPiece) {
            previewPricingBadge.textContent  = 'Per Piece';
            previewPricingBadge.className    = 'badge bg-warning text-dark';
        } else {
            previewPricingBadge.textContent  = 'Per Load';
            previewPricingBadge.className    = 'badge bg-primary';
        }
    }

    // ── Service type change handler ───────────────────────────────────────────
    if (serviceTypeSelect) {
        serviceTypeSelect.addEventListener('change', function() {
            const selected = this.options[this.selectedIndex];

            if (selected.value) {
                const defaults = JSON.parse(selected.dataset.defaults || '{}');
                const description = selected.dataset.description || '';
                const category = selected.dataset.category;

                // Set category if not already set
                if (categorySelect && !categorySelect.value) {
                    categorySelect.value = category;
                }

                // Set service_type hidden field (for backward compatibility)
                serviceTypeHidden.value = selected.textContent.toLowerCase().replace(/\s+/g, '_');

                // Set pricing type
                if (defaults.pricing_type) {
                    if (defaults.pricing_type === 'per_piece') {
                        pricingPerPiece.checked = true;
                    } else {
                        pricingPerLoad.checked = true;
                    }
                    updatePricingType();
                }

                // Set price
                if (defaults.price) {
                    if (pricingPerPiece.checked) {
                        pricePerPieceInput.value = defaults.price;
                    } else {
                        pricePerLoadInput.value = defaults.price;
                    }
                }

                // Set turnaround
                if (defaults.turnaround) {
                    turnaroundInput.value = defaults.turnaround;
                }

                // Set max weight
                if (defaults.max_weight) {
                    maxWeightInput.value = defaults.max_weight;
                    maxWeightField.style.display = 'block';
                } else {
                    maxWeightInput.value = '';
                    maxWeightField.style.display = defaults.pricing_type === 'per_piece' ? 'none' : 'block';
                }

                // Set description if empty
                if (!descriptionInput.value) {
                    descriptionInput.value = description;
                }

                // Update name preview
                previewName.textContent = nameInput.value || selected.textContent;
            }
        });
    }

    // ── Category change handler ───────────────────────────────────────────────
    if (categorySelect) {
        categorySelect.addEventListener('change', function() {
            // Filter service types by category (client-side filtering)
            const category = this.value;
            Array.from(serviceTypeSelect.options).forEach(option => {
                if (option.value === '') return; // Skip the placeholder
                const optionCategory = option.dataset.category;
                if (category && optionCategory !== category) {
                    option.style.display = 'none';
                } else {
                    option.style.display = '';
                }
            });
        });
    }

    // ── Auto-slug from name ───────────────────────────────────────────────────
    if (nameInput && slugInput) {
        slugInput.dataset.original = slugInput.value;

        nameInput.addEventListener('input', function () {
            if (!slugInput.value || slugInput.value === slugInput.dataset.original) {
                const slug = nameInput.value.toLowerCase()
                    .replace(/[^\w\s-]/g, '')
                    .replace(/\s+/g, '-')
                    .replace(/--+/g, '-')
                    .trim();
                slugInput.value = slug;
                slugInput.dataset.original = slug;
            }
            previewName.textContent = nameInput.value || '—';
        });
    }

    // ── Turnaround preview ────────────────────────────────────────────────────
    if (turnaroundInput) {
        turnaroundInput.addEventListener('input', function () {
            previewTime.textContent = (turnaroundInput.value || '0') + ' hours';
        });
    }

    // ── Icon preview ──────────────────────────────────────────────────────────
    const iconInput = document.getElementById('iconInput');
    if (iconInput) {
        iconInput.addEventListener('change', function (e) {
            const file = e.target.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function (e) {
                    document.getElementById('iconPreview').innerHTML =
                        `<img src="${e.target.result}" class="w-100 h-100 rounded-3"
                              style="object-fit: contain; background: white; padding: 10px;">`;
                };
                reader.readAsDataURL(file);
            }
        });
    }

    // ── Attach listeners ──────────────────────────────────────────────────────
    pricingPerLoad.addEventListener('change',  updatePricingType);
    pricingPerPiece.addEventListener('change', updatePricingType);
    pricePerLoadInput.addEventListener('input',  updatePreviewPricing);
    pricePerPieceInput.addEventListener('input', updatePreviewPricing);

    // Run on load
    updatePricingType();

    // Trigger category filter on load
    if (categorySelect && categorySelect.value) {
        categorySelect.dispatchEvent(new Event('change'));
    }
});
</script>

<style>
.form-check-input:checked { background-color: #3D3B6B; border-color: #3D3B6B; }
#previewPricing            { font-size: 1.25rem; }
#iconPreview img           { object-fit: cover; border-radius: 12px; }
input:disabled             { background-color: #f8f9fa !important; cursor: not-allowed !important; opacity: 0.7 !important; }
</style>
@endpush
@endsection
