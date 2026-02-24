@extends('admin.layouts.app')

@section('title', 'Edit Promotion - ' . $promotion->name)

@section('content')
<div class="container-fluid px-4 py-4">
    {{-- Header --}}
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="fw-bold text-dark mb-1">Edit Poster Promotion</h2>
            <p class="text-muted small mb-0">Update promotional campaign details</p>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('admin.promotions.show', $promotion) }}" class="btn btn-outline-secondary btn-sm">
                <i class="bi bi-eye me-2"></i>View Details
            </a>
            <a href="{{ route('admin.promotions.index') }}" class="btn btn-outline-secondary btn-sm">
                <i class="bi bi-arrow-left me-2"></i>Back to Promotions
            </a>
        </div>
    </div>

    {{-- Status Badge --}}
    <div class="mb-4">
        <span class="badge bg-{{ $promotion->is_active ? 'success' : 'secondary' }} bg-opacity-10 text-{{ $promotion->is_active ? 'success' : 'secondary' }} border border-{{ $promotion->is_active ? 'success' : 'secondary' }} rounded-pill px-4 py-2">
            <i class="bi bi-{{ $promotion->is_active ? 'check-circle' : 'pause-circle' }} me-2"></i>
            {{ $promotion->is_active ? 'ACTIVE' : 'INACTIVE' }}
        </span>
    </div>

    <form action="{{ route('admin.promotions.update', $promotion) }}" method="POST" enctype="multipart/form-data">
        @csrf
        @method('PUT')

        <div class="row g-4">
            {{-- Left Column --}}
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
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Promotion Name (Internal) <span class="text-danger">*</span></label>
                                <input type="text" name="name" class="form-control @error('name') is-invalid @enderror"
                                    value="{{ old('name', $promotion->name) }}" required>
                                @error('name')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                                <small class="text-muted">Internal reference name</small>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Poster Title <span class="text-danger">*</span></label>
                                <input type="text" name="poster_title" class="form-control @error('poster_title') is-invalid @enderror"
                                    value="{{ old('poster_title', $promotion->poster_title) }}" required>
                                @error('poster_title')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                                <small class="text-muted">Main heading for poster</small>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Subtitle (Optional)</label>
                                <input type="text" name="poster_subtitle" class="form-control @error('poster_subtitle') is-invalid @enderror"
                                    value="{{ old('poster_subtitle', $promotion->poster_subtitle) }}"
                                    placeholder="e.g., SMALL SIZES">
                                @error('poster_subtitle')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Promo Code (Optional)</label>
                                <input type="text" name="promo_code" class="form-control @error('promo_code') is-invalid @enderror"
                                    value="{{ old('promo_code', $promotion->promo_code) }}" placeholder="e.g., SUMMER2024">
                                @error('promo_code')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-12">
                                <label class="form-label fw-semibold">Description</label>
                                <textarea name="description" class="form-control @error('description') is-invalid @enderror"
                                    rows="2">{{ old('description', $promotion->description) }}</textarea>
                                @error('description')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                                <small class="text-muted">Internal description for admin reference</small>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Price Settings --}}
                <div class="card border-0 shadow-sm rounded-4 mb-4">
                    <div class="card-header bg-white border-bottom py-3">
                        <h6 class="mb-0 fw-bold text-dark">
                            <i class="bi bi-tag me-2" style="color: #3D3B6B;"></i>
                            Price Settings
                        </h6>
                    </div>
                    <div class="card-body p-4">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Display Price (₱) <span class="text-danger">*</span></label>
                                <input type="number" name="display_price" step="0.01" min="0"
                                    class="form-control @error('display_price') is-invalid @enderror"
                                    value="{{ old('display_price', $promotion->display_price) }}" required>
                                @error('display_price')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Price Unit <span class="text-danger">*</span></label>
                                <input type="text" name="price_unit"
                                    class="form-control @error('price_unit') is-invalid @enderror"
                                    value="{{ old('price_unit', $promotion->price_unit) }}"
                                    placeholder="e.g., PER 8KG LOAD" required>
                                @error('price_unit')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Features --}}
                <div class="card border-0 shadow-sm rounded-4 mb-4">
                    <div class="card-header bg-white border-bottom py-3">
                        <h6 class="mb-0 fw-bold text-dark">
                            <i class="bi bi-star me-2" style="color: #3D3B6B;"></i>
                            Promotion Features
                        </h6>
                    </div>
                    <div class="card-body p-4">
                        <label class="form-label fw-semibold">Free Services / Features</label>
                        <div id="features-container" class="mb-2">
                            @php
                                $features = old('poster_features', $promotion->poster_features ?? []);
                                if (empty($features)) {
                                    $features = ['', '', '']; // 3 empty fields
                                }
                            @endphp
                            @foreach($features as $index => $feature)
                            <div class="input-group mb-2">
                                <span class="input-group-text"><i class="bi bi-check-circle"></i></span>
                                <input type="text" name="poster_features[]" class="form-control"
                                    value="{{ $feature }}" placeholder="Add feature...">
                                @if($index >= 3)
                                <button type="button" class="btn btn-outline-danger btn-sm remove-feature">
                                    <i class="bi bi-x"></i>
                                </button>
                                @endif
                            </div>
                            @endforeach
                        </div>
                        <button type="button" class="btn btn-sm btn-outline-primary" id="add-feature">
                            <i class="bi bi-plus-circle"></i> Add More Features
                        </button>

                        <div class="mt-3">
                            <label class="form-label fw-semibold">Additional Notes</label>
                            <textarea name="poster_notes" class="form-control @error('poster_notes') is-invalid @enderror"
                                rows="2" placeholder="e.g., Ariel or Breeze | Downy | Zonrox Colorsoft">{{ old('poster_notes', $promotion->poster_notes) }}</textarea>
                            @error('poster_notes')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                </div>

                {{-- Validity Period --}}
                <div class="card border-0 shadow-sm rounded-4 mb-4">
                    <div class="card-header bg-white border-bottom py-3">
                        <h6 class="mb-0 fw-bold text-dark">
                            <i class="bi bi-calendar-range me-2" style="color: #3D3B6B;"></i>
                            Validity Period
                        </h6>
                    </div>
                    <div class="card-body p-4">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Start Date <span class="text-danger">*</span></label>
                                <input type="date" name="start_date"
                                    class="form-control @error('start_date') is-invalid @enderror"
                                    value="{{ old('start_date', $promotion->start_date?->format('Y-m-d')) }}" required>
                                @error('start_date')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-6">
                                <label class="form-label fw-semibold">End Date <span class="text-danger">*</span></label>
                                <input type="date" name="end_date"
                                    class="form-control @error('end_date') is-invalid @enderror"
                                    value="{{ old('end_date', $promotion->end_date?->format('Y-m-d')) }}" required>
                                @error('end_date')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Target Branch --}}
                <div class="card border-0 shadow-sm rounded-4 mb-4">
                    <div class="card-header bg-white border-bottom py-3">
                        <h6 class="mb-0 fw-bold text-dark">
                            <i class="bi bi-building me-2" style="color: #3D3B6B;"></i>
                            Target Branch
                        </h6>
                    </div>
                    <div class="card-body p-4">
                        <label class="form-label fw-semibold">Applicable Branch</label>
                        <select name="branch_id" class="form-select @error('branch_id') is-invalid @enderror">
                            <option value="">All Branches (Network-wide)</option>
                            @foreach($branches as $branch)
                                <option value="{{ $branch->id }}" {{ old('branch_id', $promotion->branch_id) == $branch->id ? 'selected' : '' }}>
                                    {{ $branch->name }}
                                </option>
                            @endforeach
                        </select>
                        @error('branch_id')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                        <small class="text-muted">Leave as "All Branches" for network-wide promotion</small>
                    </div>
                </div>

                {{-- Design Options --}}
                <div class="card border-0 shadow-sm rounded-4 mb-4">
                    <div class="card-header bg-white border-bottom py-3">
                        <h6 class="mb-0 fw-bold text-dark">
                            <i class="bi bi-palette me-2" style="color: #3D3B6B;"></i>
                            Design Options
                        </h6>
                    </div>
                    <div class="card-body p-4">
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Color Theme</label>
                            <div class="d-flex gap-3">
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="color_theme"
                                        id="theme_blue" value="blue"
                                        {{ old('color_theme', $promotion->color_theme) === 'blue' ? 'checked' : '' }}>
                                    <label class="form-check-label" for="theme_blue">
                                        <span class="badge" style="background: #0EA5E9; width: 40px; height: 20px;"></span> Blue
                                    </label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="color_theme"
                                        id="theme_purple" value="purple"
                                        {{ old('color_theme', $promotion->color_theme) === 'purple' ? 'checked' : '' }}>
                                    <label class="form-check-label" for="theme_purple">
                                        <span class="badge" style="background: #8B5CF6; width: 40px; height: 20px;"></span> Purple
                                    </label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="color_theme"
                                        id="theme_green" value="green"
                                        {{ old('color_theme', $promotion->color_theme) === 'green' ? 'checked' : '' }}>
                                    <label class="form-check-label" for="theme_green">
                                        <span class="badge" style="background: #10B981; width: 40px; height: 20px;"></span> Green
                                    </label>
                                </div>
                            </div>
                        </div>

                        @if($promotion->banner_image)
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Current Background Image</label>
                            <div class="border rounded-3 p-3 bg-light">
                                <img src="{{ asset('storage/' . $promotion->banner_image) }}"
                                    alt="Current Background" class="img-fluid rounded-3" style="max-height: 200px;">
                            </div>
                        </div>
                        @endif

                        <div>
                            <label class="form-label fw-semibold">
                                {{ $promotion->banner_image ? 'Replace Background Image' : 'Upload Background Image' }} (Optional)
                            </label>
                            <input type="file" name="background_image" class="form-control @error('background_image') is-invalid @enderror"
                                accept="image/*" id="backgroundInput">
                            @error('background_image')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <small class="text-muted d-block mt-1">Leave empty to keep current or use default template</small>

                            <div id="backgroundPreview" class="mt-3 d-none">
                                <label class="form-label fw-semibold">Preview</label>
                                <div class="border rounded-3 p-3 bg-light">
                                    <img id="backgroundPreviewImg" src="" alt="Preview" class="img-fluid rounded-3" style="max-height: 200px;">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Action Buttons --}}
                <div class="d-flex gap-2 mb-4">
                    <button type="submit" class="btn btn-primary px-5 shadow-sm" style="background: #3D3B6B; border: none;">
    <i class="bi bi-check-circle me-2"></i>Update Promotion
</button>
<a href="{{ route('admin.promotions.index') }}" class="btn btn-outline-secondary px-4">
    <i class="bi bi-x-circle me-2"></i>Cancel
</a>
<form action="{{ route('admin.promotions.toggleStatus', $promotion) }}" method="POST" class="ms-auto">
    @csrf
    @method('PATCH')
    <button type="submit" class="btn btn-outline-{{ $promotion->is_active ? 'warning' : 'success' }}">
        <i class="bi bi-{{ $promotion->is_active ? 'pause-circle' : 'play-circle' }} me-2"></i>
        {{ $promotion->is_active ? 'Deactivate' : 'Activate' }}
    </button>
</form>
                </div>
            </div>

            {{-- Right Column - Info & Stats --}}
            <div class="col-lg-4">
                <div class="sticky-top" style="top: 20px;">
                    {{-- Current Stats --}}
                    <div class="card border-0 shadow-sm rounded-4 mb-3">
                        <div class="card-header bg-white border-bottom py-3">
                            <h6 class="mb-0 fw-bold text-dark">
                                <i class="bi bi-graph-up me-2" style="color: #3D3B6B;"></i>
                                Current Stats
                            </h6>
                        </div>
                        <div class="card-body p-4">
                            <div class="mb-3">
                                <small class="text-muted text-uppercase fw-bold d-block mb-1">Total Uses</small>
                                <div class="fw-bold text-dark fs-4">{{ $promotion->usage_count ?? 0 }}</div>
                                @if($promotion->max_usage)
                                    <small class="text-muted">of {{ $promotion->max_usage }} maximum</small>
                                    <div class="progress mt-2" style="height: 6px;">
                                        <div class="progress-bar" style="width: {{ min(100, ($promotion->usage_count / $promotion->max_usage) * 100) }}%; background: #3D3B6B;"></div>
                                    </div>
                                @else
                                    <small class="text-muted">Unlimited uses</small>
                                @endif
                            </div>
                            <div class="mb-3">
                                <small class="text-muted text-uppercase fw-bold d-block mb-1">Created</small>
                                <div class="text-dark">{{ $promotion->created_at->format('M d, Y') }}</div>
                            </div>
                            <div>
                                <small class="text-muted text-uppercase fw-bold d-block mb-1">Last Updated</small>
                                <div class="text-dark">{{ $promotion->updated_at->diffForHumans() }}</div>
                            </div>
                        </div>
                    </div>

                    {{-- Active Status --}}
                    <div class="card border-0 shadow-sm rounded-4 mb-3">
                        <div class="card-body p-4">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" name="is_active" id="is_active"
                                    {{ old('is_active', $promotion->is_active) ? 'checked' : '' }}>
                                <label class="form-check-label fw-semibold" for="is_active">
                                    Active (Visible to customers)
                                </label>
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
                                <li class="mb-2">Use eye-catching poster titles</li>
                                <li class="mb-2">Keep features short and clear</li>
                                <li class="mb-2">Choose colors that match your brand</li>
                                <li class="mb-2">Set realistic validity periods</li>
                                <li class="mb-2">Test on one branch first</li>
                                <li class="mb-0">Update regularly to keep fresh</li>
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
// Add feature button
document.getElementById('add-feature').addEventListener('click', function() {
    const container = document.getElementById('features-container');
    const newInput = document.createElement('div');
    newInput.className = 'input-group mb-2';
    newInput.innerHTML = `
        <span class="input-group-text"><i class="bi bi-check-circle"></i></span>
        <input type="text" name="poster_features[]" class="form-control" placeholder="Add feature...">
        <button type="button" class="btn btn-outline-danger btn-sm remove-feature">
            <i class="bi bi-x"></i>
        </button>
    `;
    container.appendChild(newInput);

    // Add remove functionality
    newInput.querySelector('.remove-feature').addEventListener('click', function() {
        newInput.remove();
    });
});

// Remove feature buttons (for dynamically added ones)
document.addEventListener('click', function(e) {
    if (e.target.closest('.remove-feature')) {
        e.target.closest('.input-group').remove();
    }
});

// Background image preview
document.getElementById('backgroundInput').addEventListener('change', function(e) {
    const file = e.target.files[0];
    if (file) {
        const reader = new FileReader();
        reader.onload = function(e) {
            document.getElementById('backgroundPreviewImg').src = e.target.result;
            document.getElementById('backgroundPreview').classList.remove('d-none');
        };
        reader.readAsDataURL(file);
    }
});
</script>
@endpush

<style>
    .x-small { font-size: 0.7rem; }
</style>
@endsection
