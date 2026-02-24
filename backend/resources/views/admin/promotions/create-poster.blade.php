@extends('admin.layouts.app')
@section('page-title', 'Promotions Management')
@section('title', 'Create Promotion Poster')

@section('content')
<div class="container-fluid px-4 py-4">
    {{-- Header --}}
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="fw-bold mb-1" style="color: #1F2937;">Create Promotion Poster</h2>
            <p class="text-muted small">Design beautiful promotional posters for your laundry services</p>
        </div>
        <a href="{{ route('admin.promotions.index') }}" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left"></i> Back to Promotions
        </a>
    </div>

    <form action="{{ route('admin.promotions.store') }}" method="POST" enctype="multipart/form-data" id="promotionForm">
        @csrf
        {{-- Hidden field to identify this as poster promotion --}}
        <input type="hidden" name="type" value="poster_promo">

        <div class="row g-4">
            {{-- Left Column - Form --}}
            <div class="col-lg-7">
                {{-- Basic Information --}}
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-header bg-white border-bottom py-3">
                        <h6 class="mb-0 fw-bold" style="color: #3D3B6B;">
                            <i class="bi bi-info-circle"></i> Basic Information
                        </h6>
                    </div>
                    <div class="card-body p-4">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-semibold">Promotion Name (Internal)</label>
                                <input type="text" name="name" id="name" class="form-control"
                                    placeholder="e.g., Drop Off Promo - Sibulan" required>
                                <small class="text-muted">Internal reference name</small>
                            </div>

                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-semibold">Poster Title</label>
                                <input type="text" name="poster_title" id="poster_title" class="form-control"
                                    placeholder="e.g., DROP OFF PROMO" required>
                                <small class="text-muted">Main heading for the poster</small>
                            </div>

                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-semibold">Price (₱)</label>
                                <input type="number" name="display_price" id="display_price" class="form-control"
                                    placeholder="179" min="0" step="1" required>
                                <small class="text-muted">Main price to display</small>
                            </div>

                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-semibold">Price Unit</label>
                                <input type="text" name="price_unit" id="price_unit" class="form-control"
                                    placeholder="e.g., PER 8KG LOAD" required>
                                <small class="text-muted">What the price is for</small>
                            </div>

                            <div class="col-12 mb-3">
                                <label class="form-label fw-semibold">Subtitle (Optional)</label>
                                <input type="text" name="poster_subtitle" id="poster_subtitle" class="form-control"
                                    placeholder="e.g., SMALL SIZES or COMFORTER">
                            </div>

                            <div class="col-12 mb-3">
                                <label class="form-label fw-semibold">Description</label>
                                <textarea name="description" class="form-control" rows="2"
                                    placeholder="Internal description for admin reference"></textarea>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Features Section --}}
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-header bg-white border-bottom py-3">
                        <h6 class="mb-0 fw-bold" style="color: #3D3B6B;">
                            <i class="bi bi-star"></i> Promotion Features
                        </h6>
                    </div>
                    <div class="card-body p-4">
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Free Services / Features</label>
                            <div id="features-container">
                                <div class="input-group mb-2">
                                    <span class="input-group-text"><i class="bi bi-check-circle"></i></span>
                                    <input type="text" name="poster_features[]" class="form-control"
                                        placeholder="e.g., FREE Laundring Detergent">
                                </div>
                                <div class="input-group mb-2">
                                    <span class="input-group-text"><i class="bi bi-check-circle"></i></span>
                                    <input type="text" name="poster_features[]" class="form-control"
                                        placeholder="e.g., FREE Fabcon">
                                </div>
                                <div class="input-group mb-2">
                                    <span class="input-group-text"><i class="bi bi-check-circle"></i></span>
                                    <input type="text" name="poster_features[]" class="form-control"
                                        placeholder="e.g., FREE Fold">
                                </div>
                            </div>
                            <button type="button" class="btn btn-sm btn-outline-primary mt-2" id="add-feature">
                                <i class="bi bi-plus-circle"></i> Add More Features
                            </button>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-semibold">Additional Notes</label>
                            <textarea name="poster_notes" id="poster_notes" class="form-control" rows="2"
                                placeholder="e.g., Ariel or Breeze | Zonrox Colorsoft"></textarea>
                        </div>
                    </div>
                </div>

                {{-- Schedule & Target --}}
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-header bg-white border-bottom py-3">
                        <h6 class="mb-0 fw-bold" style="color: #3D3B6B;">
                            <i class="bi bi-calendar-check"></i> Schedule & Target
                        </h6>
                    </div>
                    <div class="card-body p-4">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-semibold">Start Date</label>
                                <input type="date" name="start_date" id="start_date" class="form-control"
                                    value="{{ date('Y-m-d') }}" required>
                            </div>

                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-semibold">End Date</label>
                                <input type="date" name="end_date" id="end_date" class="form-control" required>
                            </div>

                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-semibold">Promo Code (Optional)</label>
                                <input type="text" name="promo_code" class="form-control"
                                    placeholder="e.g., SUMMER2024">
                                <small class="text-muted">Leave empty if not needed</small>
                            </div>

                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-semibold">Target Branch</label>
                                <select name="branch_id" id="branch_id" class="form-select">
                                    <option value="">All Branches</option>
                                    @foreach($branches as $branch)
                                        <option value="{{ $branch->id }}">{{ $branch->name }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="col-12 mb-3">
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" name="is_active" id="is_active" checked>
                                    <label class="form-check-label fw-semibold" for="is_active">
                                        Active (Visible to customers)
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Design Options --}}
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-header bg-white border-bottom py-3">
                        <h6 class="mb-0 fw-bold" style="color: #3D3B6B;">
                            <i class="bi bi-palette"></i> Design Options
                        </h6>
                    </div>
                    <div class="card-body p-4">
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Color Theme</label>
                            <div class="d-flex gap-2">
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="color_theme"
                                        id="theme_blue" value="blue" checked>
                                    <label class="form-check-label" for="theme_blue">
                                        <span class="badge" style="background: #0EA5E9; width: 40px; height: 20px;"></span> Blue
                                    </label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="color_theme"
                                        id="theme_purple" value="purple">
                                    <label class="form-check-label" for="theme_purple">
                                        <span class="badge" style="background: #8B5CF6; width: 40px; height: 20px;"></span> Purple
                                    </label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="color_theme"
                                        id="theme_green" value="green">
                                    <label class="form-check-label" for="theme_green">
                                        <span class="badge" style="background: #10B981; width: 40px; height: 20px;"></span> Green
                                    </label>
                                </div>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-semibold">Background Image (Optional)</label>
                            <input type="file" name="background_image" id="background_image"
                                class="form-control" accept="image/*">
                            <small class="text-muted">Leave empty to use default template</small>
                        </div>
                    </div>
                </div>

                {{-- Action Buttons --}}
                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-primary px-4" style="background: #3D3B6B; border: none;">
                        <i class="bi bi-check-circle"></i> Create Promotion
                    </button>
                    <button type="button" class="btn btn-success px-4" id="download-poster">
                        <i class="bi bi-download"></i> Download Poster
                    </button>
                    <button type="button" class="btn btn-outline-secondary px-4" id="reset-form">
                        <i class="bi bi-arrow-clockwise"></i> Reset
                    </button>
                </div>
            </div>

            {{-- Right Column - Live Preview --}}
            <div class="col-lg-5">
                <div class="sticky-top" style="top: 20px;">
                    <div class="card border-0 shadow-sm">
                        <div class="card-header bg-dark text-white py-3">
                            <h6 class="mb-0 fw-bold">
                                <i class="bi bi-eye"></i> Live Poster Preview
                            </h6>
                        </div>
                        <div class="card-body p-0">
                            {{-- Poster Preview Canvas --}}
                            <div id="poster-preview" class="position-relative"
                                style="width: 100%; aspect-ratio: 1/1; background: linear-gradient(135deg, #0EA5E9 0%, #0284C7 100%); overflow: hidden;">

                                {{-- Decorative Elements --}}
                                <div class="position-absolute" style="top: 20px; left: 20px;">
                                    <img src="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='80' height='80' viewBox='0 0 24 24' fill='white' opacity='0.3'%3E%3Cpath d='M12 2L2 7l10 5 10-5-10-5zM2 17l10 5 10-5M2 12l10 5 10-5'/%3E%3C/svg%3E"
                                        style="width: 60px; opacity: 0.2;">
                                </div>

                                {{-- Washing Machine Icon --}}
                                <div class="position-absolute" style="bottom: 20px; right: 20px;">
                                    <i class="bi bi-asterisk" style="font-size: 80px; color: rgba(255,255,255,0.1);"></i>
                                </div>

                                {{-- Content Container --}}
                                <div class="d-flex flex-column justify-content-center align-items-center h-100 p-4 text-center">
                                    {{-- Logo/Brand --}}
                                    <div class="mb-3">
                                        <h4 class="text-white fw-bold mb-0" style="letter-spacing: 2px;">WASHBOX</h4>
                                    </div>

                                    {{-- Title --}}
                                    <div id="preview-title" class="text-white fw-bold mb-2"
                                        style="font-size: 1.2rem; letter-spacing: 1px;">
                                        DROP OFF PROMO!
                                    </div>

                                    {{-- Subtitle --}}
                                    <div id="preview-subtitle" class="text-white mb-3"
                                        style="font-size: 0.9rem; background: rgba(255,255,255,0.2); padding: 4px 16px; border-radius: 20px; display: none;">
                                    </div>

                                    {{-- Price Badge --}}
                                    <div class="position-relative mb-3">
                                        <div class="d-flex align-items-center gap-2">
                                            <div style="background: #1F2937; color: white; padding: 4px 12px; border-radius: 50%; font-size: 1.2rem; font-weight: bold;">
                                                ₱
                                            </div>
                                            <div id="preview-price" class="text-white fw-bold"
                                                style="font-size: 4rem; line-height: 1; text-shadow: 2px 2px 8px rgba(0,0,0,0.3);">
                                                179
                                            </div>
                                        </div>
                                    </div>

                                    {{-- Price Unit --}}
                                    <div id="preview-price-unit" class="text-white fw-bold mb-3"
                                        style="font-size: 1rem; letter-spacing: 1px;">
                                        PER 8KG LOAD
                                    </div>

                                    {{-- Features --}}
                                    <div id="preview-features" class="d-flex flex-wrap justify-content-center gap-2">
                                        <span class="badge bg-white text-primary" style="font-size: 0.7rem; padding: 6px 12px;">FREE Detergent</span>
                                        <span class="badge bg-white text-primary" style="font-size: 0.7rem; padding: 6px 12px;">FREE Fabcon</span>
                                        <span class="badge bg-white text-primary" style="font-size: 0.7rem; padding: 6px 12px;">FREE Fold</span>
                                    </div>

                                    {{-- Notes --}}
                                    <div id="preview-notes" class="text-white mt-3"
                                        style="font-size: 0.7rem; opacity: 0.8;">
                                    </div>
                                </div>
                            </div>

                            {{-- Preview Info --}}
                            <div class="p-3 bg-light border-top">
                                <small class="text-muted">
                                    <i class="bi bi-info-circle"></i> This preview shows how your poster will look.
                                    Adjust the form fields above to see real-time updates.
                                </small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>

@push('scripts')
<script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('promotionForm');
    const posterPreview = document.getElementById('poster-preview');

    // Color themes
    const themes = {
        blue: 'linear-gradient(135deg, #0EA5E9 0%, #0284C7 100%)',
        purple: 'linear-gradient(135deg, #8B5CF6 0%, #7C3AED 100%)',
        green: 'linear-gradient(135deg, #10B981 0%, #059669 100%)'
    };

    // Update preview in real-time
    function updatePreview() {
        const title = document.getElementById('poster_title').value || 'DROP OFF PROMO!';
        const subtitle = document.getElementById('poster_subtitle').value;
        const price = document.getElementById('display_price').value || '179';
        const priceUnit = document.getElementById('price_unit').value || 'PER 8KG LOAD';
        const notes = document.getElementById('poster_notes').value;

        document.getElementById('preview-title').textContent = title.toUpperCase();
        document.getElementById('preview-subtitle').textContent = subtitle.toUpperCase();
        document.getElementById('preview-subtitle').style.display = subtitle ? 'block' : 'none';
        document.getElementById('preview-price').textContent = price;
        document.getElementById('preview-price-unit').textContent = priceUnit.toUpperCase();
        document.getElementById('preview-notes').textContent = notes;

        // Update features
        const features = Array.from(document.querySelectorAll('input[name="poster_features[]"]'))
            .map(input => input.value.trim())
            .filter(val => val !== '');

        const featuresHTML = features.map(feature =>
            `<span class="badge bg-white text-primary" style="font-size: 0.7rem; padding: 6px 12px;">${feature}</span>`
        ).join('');

        document.getElementById('preview-features').innerHTML = featuresHTML;
    }

    // Update color theme
    document.querySelectorAll('input[name="color_theme"]').forEach(radio => {
        radio.addEventListener('change', function() {
            posterPreview.style.background = themes[this.value];
        });
    });

    // Listen to all form inputs
    form.addEventListener('input', updatePreview);
    form.addEventListener('change', updatePreview);

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
            updatePreview();
        });

        // Update preview when typing in new input
        newInput.querySelector('input').addEventListener('input', updatePreview);
    });

    // Download poster as image
    document.getElementById('download-poster').addEventListener('click', function() {
        html2canvas(posterPreview, {
            scale: 2,
            backgroundColor: null,
            logging: false
        }).then(canvas => {
            const link = document.createElement('a');
            const title = document.getElementById('poster_title').value || 'promotion';
            link.download = `washbox-promo-${title.toLowerCase().replace(/\s+/g, '-')}.png`;
            link.href = canvas.toDataURL('image/png');
            link.click();
        });
    });

    // Reset form
    document.getElementById('reset-form').addEventListener('click', function() {
        if (confirm('Are you sure you want to reset the form?')) {
            form.reset();
            updatePreview();
        }
    });

    // Initial preview
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

    #poster-preview {
        position: relative;
        border-radius: 0;
    }

    .form-label {
        color: #374151;
        font-size: 0.875rem;
    }

    .card-header {
        background: #F9FAFB !important;
    }

    .badge {
        font-weight: 600;
    }
</style>
@endpush
@endsection
