@extends('admin.layouts.app')

@section('page-title', 'Edit Promotion - ' . $promotion->name)

@section('content')
<div class="container-fluid px-4 py-4">
    {{-- Header Section --}}
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-1">
                    <li class="breadcrumb-item"><a href="{{ route('admin.promotions.index') }}" class="text-decoration-none">Promotions</a></li>
                    <li class="breadcrumb-item active">Edit Campaign</li>
                </ol>
            </nav>
            <h2 class="fw-bold text-dark mb-0">Modify Promotion</h2>
        </div>
        <a href="{{ route('admin.promotions.index') }}" class="btn btn-outline-secondary shadow-sm">
            <i class="bi bi-arrow-left me-1"></i> Back to List
        </a>
    </div>

    <form action="{{ route('admin.promotions.update', $promotion->id) }}" method="POST" enctype="multipart/form-data" id="promotionForm">
        @csrf
        @method('PUT')

        <div class="row g-4">
            {{-- Left Column: Form Fields --}}
            <div class="col-xl-8">
                <div class="card border-0 shadow-sm rounded-4 mb-4">
                    <div class="card-header bg-white py-3 border-bottom">
                        <h5 class="fw-bold mb-0 text-primary">General Information</h5>
                    </div>
                    <div class="card-body p-4">
                        <div class="row g-3">
                            <div class="col-12">
                                <label class="form-label fw-bold">Banner Image</label>
                                <div class="mb-3">
                                    @if($promotion->banner_image)
                                        <img src="{{ asset('storage/'.$promotion->banner_image) }}" class="rounded mb-2 border d-block" style="height: 120px; width: 240px; object-fit: cover;">
                                    @endif
                                    <input type="file" name="banner_image" class="form-control" id="imageInput" accept="image/*">
                                    <small class="text-muted">Upload a new banner to replace the current one (Recommended: 1200x600px).</small>
                                </div>
                            </div>

                            <div class="col-md-12">
                                <label class="form-label fw-bold">Promotion Name</label>
                                <input type="text" name="name" class="form-control" value="{{ old('name', $promotion->name) }}" required placeholder="e.g., Grand Opening Special">
                            </div>

                            <div class="col-md-6">
                                <label class="form-label fw-bold">Discount Percentage (%)</label>
                                <div class="input-group">
                                    <input type="number" name="discount_percent" class="form-control"
                                           value="{{ old('discount_percent', $promotion->pricing_data['percentage'] ?? '') }}" required>
                                    <span class="input-group-text bg-light">%</span>
                                </div>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label fw-bold">Promo Code</label>
                                <input type="text" name="promo_code" class="form-control font-monospace" value="{{ old('promo_code', $promotion->promo_code) }}" placeholder="e.g., WASHBOX20">
                            </div>

                            <div class="col-md-6">
                                <label class="form-label fw-bold">Start Date</label>
                                <input type="date" name="start_date" class="form-control"
                                       value="{{ old('start_date', $promotion->start_date->format('Y-m-d')) }}" required>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label fw-bold">End Date</label>
                                <input type="date" name="end_date" class="form-control"
                                       value="{{ old('end_date', $promotion->end_date->format('Y-m-d')) }}" required>
                            </div>

                            <div class="col-12">
                                <label class="form-label fw-bold">Target Branch</label>
                                <select name="branch_id" class="form-select">
                                    <option value="">All Branches</option>
                                    @foreach($branches as $branch)
                                        <option value="{{ $branch->id }}" {{ $promotion->branch_id == $branch->id ? 'selected' : '' }}>
                                            {{ $branch->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Right Column: Preview & Status --}}
            <div class="col-xl-4">
                <div class="card border-0 shadow-sm rounded-4 mb-4 sticky-top" style="top: 20px;">
                    <div class="card-header bg-dark text-white py-3 rounded-top-4">
                        <h6 class="mb-0 fw-bold text-center"><i class="bi bi-phone me-2"></i>Live App Preview</h6>
                    </div>
                    <div class="card-body p-0">
                        {{-- Mockup Screen --}}
                        <div class="bg-light p-3">
                            <div class="border rounded-3 bg-white shadow-sm overflow-hidden mx-auto" style="max-width: 280px;">
                                <img id="prev-img" src="{{ $promotion->banner_image ? asset('storage/'.$promotion->banner_image) : 'https://via.placeholder.com/600x300?text=Banner' }}"
                                     class="w-100" style="height: 140px; object-fit: cover;">
                                <div class="p-3">
                                    <div class="d-flex justify-content-between align-items-start mb-2">
                                        <h6 id="prev-name" class="fw-bold mb-0 small text-truncate" style="max-width: 150px;">{{ $promotion->name }}</h6>
                                        <span id="prev-disc" class="badge bg-danger" style="font-size: 0.7rem;">{{ $promotion->pricing_data['percentage'] ?? 0 }}% OFF</span>
                                    </div>
                                    <div class="bg-light p-2 text-center rounded border border-dashed mb-2">
                                        <small class="x-small text-muted d-block">USE CODE</small>
                                        <span id="prev-code" class="fw-bold text-primary small">{{ $promotion->promo_code ?? '----' }}</span>
                                    </div>
                                    <div class="d-flex justify-content-between x-small text-muted" style="font-size: 0.65rem;">
                                        <span><i class="bi bi-calendar"></i> Starts: {{ $promotion->start_date->format('M d') }}</span>
                                        <span><i class="bi bi-clock"></i> Ends: {{ $promotion->end_date->format('M d') }}</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="p-4 border-top">
                            <button type="submit" class="btn btn-success w-100 py-2 fw-bold shadow-sm">
                                <i class="bi bi-check-circle me-2"></i> Save Changes
                            </button>
                            <p class="text-center text-muted small mt-3 mb-0">Updates will be visible immediately to customers in the selected branch.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>
@endsection

@push('scripts')
<script>
    const form = document.getElementById('promotionForm');

    // Live Text Preview Logic
    form.addEventListener('input', () => {
        document.getElementById('prev-name').innerText = form.name.value || 'Promotion Name';
        document.getElementById('prev-disc').innerText = (form.discount_percent.value || '0') + '% OFF';
        document.getElementById('prev-code').innerText = (form.promo_code.value || '----').toUpperCase();
    });

    // Image Preview Logic
    document.getElementById('imageInput').onchange = evt => {
        const [file] = document.getElementById('imageInput').files;
        if (file) {
            document.getElementById('prev-img').src = URL.createObjectURL(file);
        }
    }
</script>
@endpush
