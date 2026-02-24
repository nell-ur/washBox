@extends('admin.layouts.app')

@section('title', 'Services & Add-Ons Management')
@section('page-title', 'Services & Add-Ons Management')

@php
    // Fetch add-ons with usage count
    $addons = $addons ?? \App\Models\AddOn::withCount('laundries')->latest()->get();

    // IMPORTANT: Load services with laundries count
    // Make sure your ServiceController index method uses withCount('laundries')
    $services = $services ?? \App\Models\Service::withCount('laundries')->latest()->get();
@endphp

@push('styles')
    <link rel="stylesheet" href="{{ asset('assets/css/services.css') }}">
@endpush

@section('content')
<div class="container-fluid px-4 py-4">
    {{-- Header --}}
    <div class="page-header">
        <div>
            <h4 class="fw-bold mb-1">Services & Add-Ons</h4>
            <p class="text-muted mb-0">Manage laundry services and additional options</p>
        </div>
        <div class="btn-group shadow-sm">
            <button type="button" class="btn btn-primary px-4" data-bs-toggle="modal" data-bs-target="#createServiceModal">
                <i class="bi bi-plus-circle me-2"></i>New Service
            </button>
            <button type="button" class="btn btn-success px-4" data-bs-toggle="modal" data-bs-target="#createAddonModal">
                <i class="bi bi-plus-lg me-2"></i>New Add-On
            </button>
        </div>
    </div>

    {{-- Stats Cards --}}
    <div class="stats-grid">
        <div class="stat-card">
            <div class="d-flex align-items-center">
                <div class="stat-icon-wrapper primary me-3">
                    <i class="bi bi-droplet"></i>
                </div>
                <div>
                    <div class="stat-label">Total Services</div>
                    <div class="stat-value">{{ $services->count() }}</div>
                </div>
            </div>
        </div>

        <div class="stat-card">
            <div class="d-flex align-items-center">
                <div class="stat-icon-wrapper success me-3">
                    <i class="bi bi-plus-circle"></i>
                </div>
                <div>
                    <div class="stat-label">Total Add-Ons</div>
                    <div class="stat-value">{{ $addons->count() }}</div>
                </div>
            </div>
        </div>

        <div class="stat-card">
            <div class="d-flex align-items-center">
                <div class="stat-icon-wrapper info me-3">
                    <i class="bi bi-check-circle"></i>
                </div>
                <div>
                    <div class="stat-label">Active Services</div>
                    <div class="stat-value">{{ $services->where('is_active', true)->count() }}</div>
                </div>
            </div>
        </div>

        <div class="stat-card">
            <div class="d-flex align-items-center">
                <div class="stat-icon-wrapper warning me-3">
                    <i class="bi bi-tag"></i>
                </div>
                <div>
                    <div class="stat-label">Active Add-Ons</div>
                    <div class="stat-value">{{ $addons->where('is_active', true)->count() }}</div>
                </div>
            </div>
        </div>
    </div>

    {{-- Services Section --}}
    <div class="table-card">
        <div class="table-header">
            <h6>
                <i class="bi bi-droplet"></i> Laundry Services
            </h6>
            <div class="form-check form-switch">
                <input class="form-check-input" type="checkbox" id="showInactiveServices" checked>
                <label class="form-check-label small fw-semibold" for="showInactiveServices">Show Inactive</label>
            </div>
        </div>

        <div class="table-responsive">
            @if($services->count() > 0)
            <table class="services-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Service Name</th>
                        <th>Category</th>
                        <th>Type</th>
                        <th>Pricing</th>
                        <th>Turnaround</th>
                        <th>Price</th>
                        <th>Weight Limit</th>
                        <th>Status</th>
                        <th>Usage</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($services as $service)
                    <tr class="service-row" data-active="{{ $service->is_active }}" data-id="{{ $service->id }}">
                        <td>
                            <span class="fw-bold text-primary">#{{ $service->id }}</span>
                        </td>
                        <td>
                            <div class="service-name">{{ $service->name }}</div>
                            @if($service->description)
                            <div class="service-description">{{ Str::limit($service->description, 50) }}</div>
                            @endif
                        </td>
                        <td>
                            @php
                                $catConfig = [
                                    'drop_off'    => ['label' => 'Drop Off',    'color' => '#3D3B6B', 'icon' => 'bi-bag-check'],
                                    'self_service'=> ['label' => 'Self Service','color' => '#10B981', 'icon' => 'bi-person-workspace'],
                                    'addon'       => ['label' => 'Add-on',      'color' => '#F59E0B', 'icon' => 'bi-plus-circle'],
                                ];
                                $cat = $catConfig[$service->category ?? 'drop_off'] ?? $catConfig['drop_off'];
                            @endphp
                            <span style="
                                display:inline-flex;align-items:center;gap:5px;
                                background:{{ $cat['color'] }}18;
                                color:{{ $cat['color'] }};
                                border:1px solid {{ $cat['color'] }}40;
                                padding:3px 10px;border-radius:20px;
                                font-size:12px;font-weight:600;white-space:nowrap;">
                                <i class="bi {{ $cat['icon'] }}" style="font-size:11px;"></i>
                                {{ $cat['label'] }}
                            </span>
                        </td>
                        <td>
                            <span class="type-badge {{ $service->service_type }}">
                                {{ ucfirst(str_replace('_', ' ', $service->service_type)) }}
                            </span>
                        </td>
                        <td>
                            @php
                                $pricingType = $service->pricing_type ?? 'per_load';
                            @endphp
                            <span class="pricing-badge">
                                {{ $pricingType === 'per_piece' ? 'Per piece' : 'Per load' }}
                            </span>
                        </td>
                        <td>
                            <span class="pricing-badge">{{ $service->turnaround_time }}h</span>
                        </td>
                        <td>
                            @php
                                $pricingType = $service->pricing_type ?? 'per_load';
                                $priceUnit   = $pricingType === 'per_piece' ? 'piece' : 'load';
                                $priceValue  = $pricingType === 'per_piece'
                                    ? ($service->price_per_piece ?? 0)
                                    : ($service->price_per_load  ?? 0);
                            @endphp
                            <span class="price-display primary">
                                ₱{{ number_format($priceValue, 2) }}/{{ $priceUnit }}
                            </span>
                        </td>
                        <td>
                            @if($service->min_weight && $service->max_weight)
                                {{ $service->min_weight }}-{{ $service->max_weight }}kg
                            @elseif($service->max_weight)
                                up to {{ $service->max_weight }}kg
                            @else
                                <span class="text-muted">—</span>
                            @endif
                        </td>
                        <td>
                            <div class="status-wrapper">
                                <span class="status-badge-sm {{ $service->is_active ? 'active' : 'inactive' }}">
                                    {{ $service->is_active ? 'Active' : 'Inactive' }}
                                </span>
                                <div class="toggle-switch {{ $service->is_active ? 'active' : '' }} service-status-toggle"
                                     data-id="{{ $service->id }}"></div>
                            </div>
                        </td>
                        <td>
                            @php
                                $usageCount = $service->laundries_count ?? 0;
                            @endphp
                            <span class="usage-badge {{ $usageCount > 0 ? 'has-usage' : '' }}">
                                <i class="bi bi-{{ $usageCount > 0 ? 'check-circle-fill' : 'dash-circle' }}"></i>
                                {{ $usageCount }} {{ Str::plural('use', $usageCount) }}
                            </span>
                        </td>
                        <td class="text-end">
                            <div class="action-group">
                                <a href="{{ route('admin.services.show', $service) }}" class="action-btn view" title="View Service">
                                    <i class="bi bi-eye"></i>
                                </a>
                                <a href="{{ route('admin.services.edit', $service) }}" class="action-btn edit" title="Edit Service">
                                    <i class="bi bi-pencil"></i>
                                </a>
                                <button type="button" class="action-btn delete delete-service" title="Delete Service"
                                        data-id="{{ $service->id }}"
                                        data-name="{{ $service->name }}"
                                        data-usage="{{ $usageCount }}">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
            @else
            <div class="empty-state">
                <i class="bi bi-droplet"></i>
                <h5>No services yet</h5>
                <p>Start by creating your first laundry service</p>
                <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createServiceModal">
                    <i class="bi bi-plus-circle me-2"></i>Create First Service
                </button>
            </div>
            @endif
        </div>
    </div>

    {{-- Add-Ons Section --}}
    <div class="table-card">
        <div class="table-header">
            <h6>
                <i class="bi bi-plus-circle"></i> Add-On Services
            </h6>
            <div class="form-check form-switch">
                <input class="form-check-input" type="checkbox" id="showInactiveAddons" checked>
                <label class="form-check-label small fw-semibold" for="showInactiveAddons">Show Inactive</label>
            </div>
        </div>

        <div class="table-responsive">
            @if($addons->count() > 0)
            <table class="services-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Add-On Name</th>
                        <th>Slug</th>
                        <th>Description</th>
                        <th>Price</th>
                        <th>Status</th>
                        <th>Usage</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($addons as $addon)
                    <tr class="addon-row" data-active="{{ $addon->is_active }}" data-id="{{ $addon->id }}">
                        <td>
                            <span class="fw-bold text-success">#{{ $addon->id }}</span>
                        </td>
                        <td>
                            <div class="service-name">{{ $addon->name }}</div>
                        </td>
                        <td>
                            <span class="pricing-badge">{{ $addon->slug }}</span>
                        </td>
                        <td>
                            @if($addon->description)
                                <div class="service-description">{{ Str::limit($addon->description, 80) }}</div>
                            @else
                                <span class="text-muted small">—</span>
                            @endif
                        </td>
                        <td>
                            <span class="price-display">₱{{ number_format($addon->price, 2) }}</span>
                        </td>
                        <td>
                            <div class="status-wrapper">
                                <span class="status-badge-sm {{ $addon->is_active ? 'active' : 'inactive' }}">
                                    {{ $addon->is_active ? 'Active' : 'Inactive' }}
                                </span>
                                <div class="toggle-switch {{ $addon->is_active ? 'active' : '' }} addon-status-toggle"
                                     data-id="{{ $addon->id }}"></div>
                            </div>
                        </td>
                        <td>
                            @php
                                $usageCount = $addon->laundries_count ?? 0;
                            @endphp
                            <span class="usage-badge {{ $usageCount > 0 ? 'has-usage' : '' }}">
                                <i class="bi bi-{{ $usageCount > 0 ? 'check-circle-fill' : 'dash-circle' }}"></i>
                                {{ $usageCount }} {{ Str::plural('use', $usageCount) }}
                            </span>
                        </td>
                        <td class="text-end">
                            <div class="action-group">
                                <button type="button" class="action-btn view view-addon" title="View Add-on Details"
                                        data-bs-toggle="modal"
                                        data-bs-target="#viewAddonModal"
                                        data-addon-id="{{ $addon->id }}"
                                        data-addon-name="{{ $addon->name }}"
                                        data-addon-slug="{{ $addon->slug }}"
                                        data-addon-description="{{ $addon->description }}"
                                        data-addon-price="{{ $addon->price }}"
                                        data-addon-is-active="{{ $addon->is_active }}"
                                        data-addon-created="{{ $addon->created_at->format('F d, Y') }}"
                                        data-addon-updated="{{ $addon->updated_at->format('F d, Y') }}"
                                        data-addon-usage="{{ $usageCount }}">
                                    <i class="bi bi-eye"></i>
                                </button>
                                <button type="button" class="action-btn edit edit-addon" title="Edit Add-on"
                                        data-bs-toggle="modal"
                                        data-bs-target="#editAddonModal"
                                        data-addon-id="{{ $addon->id }}"
                                        data-addon-name="{{ $addon->name }}"
                                        data-addon-slug="{{ $addon->slug }}"
                                        data-addon-description="{{ $addon->description }}"
                                        data-addon-price="{{ $addon->price }}"
                                        data-addon-is-active="{{ $addon->is_active }}">
                                    <i class="bi bi-pencil"></i>
                                </button>
                                <button type="button" class="action-btn delete delete-addon" title="Delete Add-on"
                                        data-id="{{ $addon->id }}"
                                        data-name="{{ $addon->name }}"
                                        data-usage="{{ $usageCount }}">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
            @else
            <div class="empty-state">
                <i class="bi bi-plus-circle"></i>
                <h5>No add-ons yet</h5>
                <p>Start by creating your first add-on service</p>
                <button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#createAddonModal">
                    <i class="bi bi-plus-lg me-2"></i>Create First Add-On
                </button>
            </div>
            @endif
        </div>
    </div>
</div>

{{-- Create Service Type Modal --}}
<div class="modal fade" id="createServiceTypeModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg">
            <form id="createServiceTypeForm">
                @csrf
                <div class="modal-header bg-info text-white">
                    <h5 class="modal-title fw-bold"><i class="bi bi-tags me-2"></i>Create New Service Type</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    {{-- Errors shown inside modal — no data lost on failure --}}
                    <div id="stFormError" class="alert alert-danger d-none mb-3 py-2 small"></div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Name <span class="text-danger">*</span></label>
                        <input type="text" name="name" class="form-control" required placeholder="e.g., Regular Clothes, Comforter - Small">
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Category <span class="text-danger">*</span></label>
                        <select name="category" class="form-select" required>
                            <option value="">Select category</option>
                            <option value="drop_off">🛍 Drop Off</option>
                            <option value="self_service">🧺 Self Service</option>
                            <option value="addon">➕ Add-on</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Icon <small class="text-muted fw-normal">(Bootstrap class, optional)</small></label>
                        <input type="text" name="icon" class="form-control" placeholder="e.g., bi-tag, bi-star">
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Display Order</label>
                        <input type="number" name="display_order" class="form-control" min="0" value="0">
                        <small class="text-muted">Lower numbers appear first</small>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Description</label>
                        <textarea name="description" class="form-control" rows="2" placeholder="Describe this service type..."></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">
                            Default Config <small class="text-muted fw-normal">— optional JSON</small>
                        </label>
                        <textarea name="defaults" id="stDefaults" class="form-control font-monospace" rows="2"
                                  placeholder='{"price":200,"max_weight":8,"turnaround":24,"pricing_type":"per_load"}'></textarea>
                        <small class="text-muted">Leave blank or enter valid JSON. Example: <code>{"price":70,"turnaround":1}</code></small>
                    </div>
                    <div class="mb-2">
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" name="is_active" id="stIsActive" checked>
                            <label class="form-check-label fw-semibold" for="stIsActive">Active</label>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" id="stSubmitBtn" class="btn btn-info px-4 text-white">
                        <i class="bi bi-plus-circle me-1"></i>Create Service Type
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- Create Service Modal --}}
<div class="modal fade" id="createServiceModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg">
            <form id="createServiceForm" method="POST" enctype="multipart/form-data">
                @csrf
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title fw-bold"><i class="bi bi-plus-circle me-2"></i>Create New Service</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="row g-3">
                        <!-- Service Name -->
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Service Name <span class="text-danger">*</span></label>
                            <input type="text" name="name" class="form-control" required placeholder="e.g., Wash & Fold">
                        </div>

                        <!-- Category -->
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Category <span class="text-danger">*</span></label>
                            <select name="category" class="form-select" id="modalCategory" required>
                                <option value="">Select category</option>
                                <option value="drop_off">🛍 Drop Off</option>
                                <option value="self_service">🧺 Self Service</option>
                                <option value="addon">➕ Add-on</option>
                            </select>
                        </div>

                        <!-- Service Type -->
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Service Type <span class="text-danger">*</span></label>
                            <input type="text" name="service_type" class="form-control" id="modalServiceType"
                                required placeholder="e.g., regular_clothes, special_item, self_service">
                            <small class="text-muted">
                                Use: <code>regular_clothes</code>, <code>special_item</code>,
                                <code>self_service</code>, <code>addon</code>
                            </small>
                        </div>

                        <!-- Pricing Type Toggle -->
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Pricing Type <span class="text-danger">*</span></label>
                            <div class="btn-group w-100" role="group" id="pricingTypeToggle">
                                <input type="radio" class="btn-check" name="pricing_type" id="pricingPerLoad" value="per_load" autocomplete="off" checked>
                                <label class="btn btn-outline-primary" for="pricingPerLoad">
                                    <i class="bi bi-basket me-1"></i>Per Load
                                </label>
                                <input type="radio" class="btn-check" name="pricing_type" id="pricingPerPiece" value="per_piece" autocomplete="off">
                                <label class="btn btn-outline-primary" for="pricingPerPiece">
                                    <i class="bi bi-tag me-1"></i>Per Piece
                                </label>
                            </div>
                            <small class="text-muted mt-1 d-block" id="pricingTypeHelp">Customer will be charged per laundry load</small>
                        </div>

                        <!-- Price -->
                        <div class="col-md-6">
                            <label class="form-label fw-semibold" id="modalPriceLabel">Price per Load <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <span class="input-group-text">₱</span>
                                {{--
                                    name is swapped dynamically by JS:
                                    Per Load  → name="price_per_load"
                                    Per Piece → name="price_per_piece"
                                --}}
                                <input type="number" name="price_per_load" id="modalPriceInput" class="form-control" step="0.01" min="0" placeholder="200.00" required>
                            </div>
                            <small class="text-muted" id="modalPriceHelp">Fixed price per load</small>
                        </div>

                        <!-- Turnaround Time -->
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Turnaround Time (hours) <span class="text-danger">*</span></label>
                            <input type="number" name="turnaround_time" id="modalTurnaround" class="form-control" min="0" max="168" value="24" required>
                        </div>

                        <!-- Min Weight -->
                        <div class="col-md-3">
                            <label class="form-label fw-semibold">Min Weight (kg)</label>
                            <input type="number" name="min_weight" class="form-control" step="0.1" min="0" placeholder="0">
                        </div>

                        <!-- Max Weight -->
                        <div class="col-md-3">
                            <label class="form-label fw-semibold">Max Weight (kg)</label>
                            <input type="number" name="max_weight" class="form-control" step="0.1" min="0" placeholder="0">
                        </div>

                        <!-- Icon Upload -->
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Service Icon</label>
                            <input type="file" name="icon" class="form-control" accept="image/*">
                            <small class="text-muted">Optional. Upload an icon for this service.</small>
                        </div>

                        <!-- Description -->
                        <div class="col-12">
                            <label class="form-label fw-semibold">Description</label>
                            <textarea name="description" class="form-control" rows="3" placeholder="Service description..."></textarea>
                        </div>

                        <!-- Slug -->
                        <div class="col-md-8">
                            <label class="form-label fw-semibold">Slug</label>
                            <input type="text" name="slug" class="form-control" placeholder="auto-generated-from-name">
                            <small class="text-muted">URL-friendly version (lowercase, hyphens). Auto-generated if left empty.</small>
                        </div>

                        <!-- Active Status -->
                        <div class="col-md-4">
                            <div class="form-check form-switch mt-4">
                                <input class="form-check-input" type="checkbox" name="is_active" id="serviceActive" checked>
                                <label class="form-check-label fw-semibold" for="serviceActive">Active Service</label>
                            </div>
                            <small class="text-muted">Inactive services won't appear in order creation</small>
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-0">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary px-4">Create Service</button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- Create Add-On Modal --}}
<div class="modal fade" id="createAddonModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg">
            <form id="createAddonForm" method="POST">
                @csrf
                <div class="modal-header bg-success text-white">
                    <h5 class="modal-title fw-bold"><i class="bi bi-plus-circle me-2"></i>Create New Add-On</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Add-On Name <span class="required">*</span></label>
                        <input type="text" name="name" class="form-control" required placeholder="e.g., Fabric Conditioner">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Slug <span class="required">*</span></label>
                        <input type="text" name="slug" class="form-control" required placeholder="e.g., fabric-conditioner">
                        <small class="text-muted">URL-friendly version (lowercase, hyphens)</small>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Description</label>
                        <textarea name="description" class="form-control" rows="2" placeholder="Add-on description..."></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Price (₱) <span class="required">*</span></label>
                        <div class="input-group">
                            <span class="input-group-text">₱</span>
                            <input type="number" name="price" class="form-control" step="0.01" min="0" required placeholder="0.00">
                        </div>
                    </div>
                    <div class="mb-3">
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" name="is_active" id="addonActive" checked>
                            <label class="form-check-label fw-semibold" for="addonActive">Active Add-On</label>
                        </div>
                        <small class="text-muted">Inactive add-ons won't appear in order creation</small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success px-4">Create Add-On</button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- Edit Add-On Modal --}}
<div class="modal fade" id="editAddonModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg">
            <form id="editAddonForm" method="POST">
                @csrf
                @method('PUT')
                <div class="modal-header bg-warning text-white">
                    <h5 class="modal-title fw-bold"><i class="bi bi-pencil me-2"></i>Edit Add-On</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" id="editAddonId" name="id">
                    <div class="mb-3">
                        <label class="form-label">Add-On Name <span class="required">*</span></label>
                        <input type="text" name="name" id="editAddonName" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Slug <span class="required">*</span></label>
                        <input type="text" name="slug" id="editAddonSlug" class="form-control" required>
                        <small class="text-muted">URL-friendly version</small>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Description</label>
                        <textarea name="description" id="editAddonDescription" class="form-control" rows="2"></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Price (₱) <span class="required">*</span></label>
                        <div class="input-group">
                            <span class="input-group-text">₱</span>
                            <input type="number" name="price" id="editAddonPrice" class="form-control" step="0.01" min="0" required>
                        </div>
                    </div>
                    <div class="mb-3">
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" name="is_active" id="editAddonActive">
                            <label class="form-check-label fw-semibold" for="editAddonActive">Active Add-On</label>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-warning px-4">Update Add-On</button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- View Add-On Modal --}}
<div class="modal fade" id="viewAddonModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header bg-info text-white">
                <h5 class="modal-title fw-bold"><i class="bi bi-eye me-2"></i>Add-on Details</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="text-center mb-4">
                    <div class="icon-preview">
                        <i class="bi bi-plus-circle"></i>
                    </div>
                    <span id="viewAddonStatus" class="detail-badge active" style="display: inline-block;">
                        <span id="viewAddonStatusText">ACTIVE</span>
                    </span>
                </div>

                <div class="detail-item">
                    <div class="detail-label">Add-on Name</div>
                    <div id="viewAddonName" class="detail-value">-</div>
                </div>

                <div class="detail-item">
                    <div class="detail-label">Slug</div>
                    <code id="viewAddonSlug" class="detail-value">-</code>
                </div>

                <div class="detail-item">
                    <div class="detail-label">Description</div>
                    <p id="viewAddonDescription" class="detail-value mb-0">-</p>
                </div>

                <div class="row">
                    <div class="col-md-6">
                        <div class="detail-item">
                            <div class="detail-label">Price</div>
                            <div id="viewAddonPrice" class="detail-value text-success">-</div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="detail-item">
                            <div class="detail-label">Status</div>
                            <div id="viewAddonStatusBadge" class="detail-value">-</div>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6">
                        <div class="detail-item">
                            <div class="detail-label">Usage Count</div>
                            <div id="viewAddonUsage" class="detail-value">-</div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="detail-item">
                            <div class="detail-label">Created</div>
                            <div id="viewAddonCreated" class="detail-value">-</div>
                        </div>
                    </div>
                </div>

                <div class="detail-item">
                    <div class="detail-label">Last Updated</div>
                    <div id="viewAddonUpdated" class="detail-value">-</div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Close</button>
                <button type="button" class="btn btn-info text-white" id="viewAddonEditBtn">
                    <i class="bi bi-pencil me-2"></i>Edit Add-on
                </button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Set form action URLs
    const createAddonForm = document.getElementById('createAddonForm');
    if (createAddonForm) {
        @if(Route::has('admin.addons.store'))
            createAddonForm.action = "{{ route('admin.addons.store') }}";
        @else
            createAddonForm.action = "/admin/addons";
        @endif
    }

    // Show/Hide inactive services
    const showInactiveServices = document.getElementById('showInactiveServices');
    if (showInactiveServices) {
        showInactiveServices.addEventListener('change', function() {
            const serviceRows = document.querySelectorAll('.service-row');
            serviceRows.forEach(row => {
                if (!this.checked && row.dataset.active === '0') {
                    row.style.display = 'none';
                } else {
                    row.style.display = '';
                }
            });
        });
        showInactiveServices.dispatchEvent(new Event('change'));
    }

    // Show/Hide inactive addons
    const showInactiveAddons = document.getElementById('showInactiveAddons');
    if (showInactiveAddons) {
        showInactiveAddons.addEventListener('change', function() {
            const addonRows = document.querySelectorAll('.addon-row');
            addonRows.forEach(row => {
                if (!this.checked && row.dataset.active === '0') {
                    row.style.display = 'none';
                } else {
                    row.style.display = '';
                }
            });
        });
        showInactiveAddons.dispatchEvent(new Event('change'));
    }

    // ─── Pricing Type Toggle ─────────────────────────────────────────────────
    const pricingPerLoad  = document.getElementById('pricingPerLoad');
    const pricingPerPiece = document.getElementById('pricingPerPiece');
    const modalPriceLabel = document.getElementById('modalPriceLabel');
    const modalPriceHelp  = document.getElementById('modalPriceHelp');
    const modalPriceInput = document.getElementById('modalPriceInput');
    const pricingTypeHelp = document.getElementById('pricingTypeHelp');

    function updatePricingLabel() {
        const isPerPiece = pricingPerPiece && pricingPerPiece.checked;

        // Swap the input's name so the correct field reaches the controller
        if (modalPriceInput) {
            modalPriceInput.name        = isPerPiece ? 'price_per_piece' : 'price_per_load';
            modalPriceInput.placeholder = isPerPiece ? '150.00' : '200.00';
        }

        if (modalPriceLabel) {
            modalPriceLabel.innerHTML = (isPerPiece ? 'Price per Piece' : 'Price per Load') + ' <span class="text-danger">*</span>';
        }
        if (modalPriceHelp) {
            modalPriceHelp.textContent = isPerPiece
                ? 'Fixed price per piece (e.g. comforter, suit jacket)'
                : 'Fixed price per load';
        }
        if (pricingTypeHelp) {
            pricingTypeHelp.textContent = isPerPiece
                ? 'Customer will be charged per individual piece'
                : 'Customer will be charged per laundry load';
        }
    }

    if (pricingPerLoad)  pricingPerLoad.addEventListener('change',  updatePricingLabel);
    if (pricingPerPiece) pricingPerPiece.addEventListener('change', updatePricingLabel);

    // ─── Service Type defaults ────────────────────────────────────────────────
    const SERVICE_DEFAULTS = {
        regular_clothes: { price: 200, maxWeight: 8,  turnaround: 24, pricingType: 'per_load'  },
        self_service:    { price: 70,  maxWeight: '',  turnaround: 1,  pricingType: 'per_load'  },
        special_item:    { price: 150, maxWeight: '',  turnaround: 24, pricingType: 'per_piece' },
        addon:           { price: 20,  maxWeight: '',  turnaround: 0,  pricingType: 'per_load'  },
    };

    const modalServiceType = document.getElementById('modalServiceType');
    const modalTurnaround  = document.getElementById('modalTurnaround');
    const modalMaxWeight   = document.querySelector('#createServiceModal input[name="max_weight"]');

    if (modalServiceType) {
        modalServiceType.addEventListener('change', function () {
            const d = SERVICE_DEFAULTS[this.value];
            if (!d) return;

            // Apply price default only if field is empty
            if (modalPriceInput && !modalPriceInput.value) modalPriceInput.value = d.price;
            if (modalMaxWeight  && !modalMaxWeight.value)  modalMaxWeight.value  = d.maxWeight;
            if (modalTurnaround && (modalTurnaround.value === '24' || !modalTurnaround.value)) {
                modalTurnaround.value = d.turnaround;
            }

            // Auto-select the appropriate pricing type
            if (d.pricingType === 'per_piece' && pricingPerPiece) {
                pricingPerPiece.checked = true;
            } else if (pricingPerLoad) {
                pricingPerLoad.checked = true;
            }
            updatePricingLabel();
        });
    }

    // ─── Reset modal on open ──────────────────────────────────────────────────
    const createServiceModal = document.getElementById('createServiceModal');
    if (createServiceModal) {
        createServiceModal.addEventListener('show.bs.modal', function () {
            if (modalServiceType) modalServiceType.value = '';
            if (modalPriceInput) {
                modalPriceInput.value       = '';
                modalPriceInput.name        = 'price_per_load'; // always reset to per_load
                modalPriceInput.placeholder = '200.00';
            }
            if (modalTurnaround)  modalTurnaround.value  = '24';
            if (modalMaxWeight)   modalMaxWeight.value   = '';
            if (pricingPerLoad)   pricingPerLoad.checked = true;
            updatePricingLabel();
        });
    }

    // ─── Service Status Toggle ────────────────────────────────────────────────
    document.querySelectorAll('.service-status-toggle').forEach(toggle => {
        toggle.addEventListener('click', function() {
            const serviceId = this.getAttribute('data-id');
            const isActive = this.classList.contains('active');
            const row = this.closest('tr');
            const statusBadge = row.querySelector('.status-badge-sm');

            // Optimistic update
            if (isActive) {
                this.classList.remove('active');
                statusBadge.classList.remove('active');
                statusBadge.classList.add('inactive');
                statusBadge.textContent = 'Inactive';
                row.dataset.active = '0';
            } else {
                this.classList.add('active');
                statusBadge.classList.remove('inactive');
                statusBadge.classList.add('active');
                statusBadge.textContent = 'Active';
                row.dataset.active = '1';
            }

            fetch(`/admin/services/${serviceId}/toggle-status`, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                    'Content-Type': 'application/json',
                    'Accept': 'application/json'
                },
                body: JSON.stringify({ is_active: !isActive })
            })
            .then(response => {
                if (!response.ok) {
                    return response.json().then(err => { throw new Error(err.message || 'Error updating status'); });
                }
                return response.json();
            })
            .then(data => {
                if (!data.success) throw new Error(data.message || 'Error updating status');
                showAlert('success', 'Service status updated successfully!');
                if (showInactiveServices) showInactiveServices.dispatchEvent(new Event('change'));
            })
            .catch(error => {
                console.error('Error:', error);
                // Revert
                if (isActive) {
                    this.classList.add('active');
                    statusBadge.classList.add('active');
                    statusBadge.classList.remove('inactive');
                    statusBadge.textContent = 'Active';
                    row.dataset.active = '1';
                } else {
                    this.classList.remove('active');
                    statusBadge.classList.remove('active');
                    statusBadge.classList.add('inactive');
                    statusBadge.textContent = 'Inactive';
                    row.dataset.active = '0';
                }
                showAlert('danger', error.message || 'Error updating service status');
            });
        });
    });

    // ─── Addon Status Toggle ──────────────────────────────────────────────────
    document.querySelectorAll('.addon-status-toggle').forEach(toggle => {
        toggle.addEventListener('click', function() {
            const addonId = this.getAttribute('data-id');
            const isActive = this.classList.contains('active');
            const row = this.closest('tr');
            const statusBadge = row.querySelector('.status-badge-sm');

            // Optimistic update
            if (isActive) {
                this.classList.remove('active');
                statusBadge.classList.remove('active');
                statusBadge.classList.add('inactive');
                statusBadge.textContent = 'Inactive';
                row.dataset.active = '0';
            } else {
                this.classList.add('active');
                statusBadge.classList.remove('inactive');
                statusBadge.classList.add('active');
                statusBadge.textContent = 'Active';
                row.dataset.active = '1';
            }

            fetch(`/admin/addons/${addonId}/toggle-status`, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                    'Content-Type': 'application/json',
                    'Accept': 'application/json'
                },
                body: JSON.stringify({ is_active: !isActive })
            })
            .then(response => {
                if (!response.ok) {
                    return response.json().then(err => { throw new Error(err.message || 'Error updating status'); });
                }
                return response.json();
            })
            .then(data => {
                if (!data.success) throw new Error(data.message || 'Error updating status');
                showAlert('success', 'Add-on status updated successfully!');
                if (showInactiveAddons) showInactiveAddons.dispatchEvent(new Event('change'));
            })
            .catch(error => {
                console.error('Error:', error);
                // Revert
                if (isActive) {
                    this.classList.add('active');
                    statusBadge.classList.add('active');
                    statusBadge.classList.remove('inactive');
                    statusBadge.textContent = 'Active';
                    row.dataset.active = '1';
                } else {
                    this.classList.remove('active');
                    statusBadge.classList.remove('active');
                    statusBadge.classList.add('inactive');
                    statusBadge.textContent = 'Inactive';
                    row.dataset.active = '0';
                }
                showAlert('danger', error.message || 'Error updating add-on status');
            });
        });
    });

    // ─── Create Service Form ──────────────────────────────────────────────────
    const createServiceForm = document.getElementById('createServiceForm');
    if (createServiceForm) {
        createServiceForm.addEventListener('submit', function(e) {
            e.preventDefault();

            const submitBtn = this.querySelector('button[type="submit"]');
            const formData = new FormData(this);

            submitBtn.disabled = true;
            submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Creating...';

            fetch('/admin/services', {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                    'Accept': 'application/json'
                },
                body: formData
            })
            .then(response => {
                if (response.status === 422) {
                    return response.json().then(err => {
                        const errors = err.errors || {};
                        const messages = Object.entries(errors)
                            .map(([field, msgs]) => `<strong>${field}:</strong> ${msgs[0]}`)
                            .join('<br>');
                        throw new Error(messages || err.message || 'Validation error');
                    });
                }
                if (!response.ok) {
                    return response.json().then(err => { throw new Error(err.message || 'Error creating service'); });
                }
                return response.json();
            })
            .then(data => {
                if (data.success) {
                    showAlert('success', data.message || 'Service created successfully!');
                    const modal = bootstrap.Modal.getInstance(document.getElementById('createServiceModal'));
                    modal.hide();
                    this.reset();
                    setTimeout(() => location.reload(), 1500);
                } else {
                    showAlert('danger', data.message || 'Error creating service');
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = 'Create Service';
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showAlert('danger', error.message || 'Error creating service');
                submitBtn.disabled = false;
                submitBtn.innerHTML = 'Create Service';
            });
        });
    }

    // ─── Delete Service ───────────────────────────────────────────────────────
    document.querySelectorAll('.delete-service').forEach(button => {
        button.addEventListener('click', function() {
            const serviceId   = this.getAttribute('data-id');
            const serviceName = this.getAttribute('data-name');
            const usageCount  = parseInt(this.getAttribute('data-usage') || '0');

            let message = `Are you sure you want to delete "${serviceName}"?`;
            if (usageCount > 0) {
                message = `"${serviceName}" has been used ${usageCount} time${usageCount > 1 ? 's' : ''} in laundry orders. Deleting it may affect historical data. Are you sure you want to proceed?`;
            }

            if (confirm(message + ' This action cannot be undone.')) {
                const originalHtml = this.innerHTML;
                this.disabled = true;
                this.innerHTML = '<span class="spinner-border spinner-border-sm"></span>';

                fetch(`/admin/services/${serviceId}`, {
                    method: 'DELETE',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                        'Accept': 'application/json',
                        'Content-Type': 'application/json'
                    }
                })
                .then(response => {
                    if (!response.ok) {
                        return response.json().then(err => { throw new Error(err.message || 'Error deleting service'); });
                    }
                    return response.json();
                })
                .then(data => {
                    if (data.success) {
                        showAlert('success', data.message || 'Service deleted successfully!');
                        const row = this.closest('tr');
                        row.style.transition = 'all 0.3s ease';
                        row.style.opacity = '0';
                        row.style.transform = 'translateX(20px)';
                        setTimeout(() => {
                            row.remove();
                            const tbody = document.querySelector('.services-table tbody');
                            if (tbody && tbody.children.length === 0) location.reload();
                        }, 300);
                    } else {
                        showAlert('danger', data.message || 'Error deleting service');
                        this.disabled = false;
                        this.innerHTML = originalHtml;
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    showAlert('danger', error.message || 'Error deleting service');
                    this.disabled = false;
                    this.innerHTML = originalHtml;
                });
            }
        });
    });

    // ─── Delete Addon ─────────────────────────────────────────────────────────
    document.querySelectorAll('.delete-addon').forEach(button => {
        button.addEventListener('click', function() {
            const addonId   = this.getAttribute('data-id');
            const addonName = this.getAttribute('data-name');
            const usageCount = parseInt(this.getAttribute('data-usage') || '0');

            let message = `Are you sure you want to delete "${addonName}"?`;
            if (usageCount > 0) {
                message = `"${addonName}" has been used ${usageCount} time${usageCount > 1 ? 's' : ''} in laundry orders. Deleting it may affect historical data. Are you sure you want to proceed?`;
            }

            if (confirm(message + ' This action cannot be undone.')) {
                const originalHtml = this.innerHTML;
                this.disabled = true;
                this.innerHTML = '<span class="spinner-border spinner-border-sm"></span>';

                fetch(`/admin/addons/${addonId}`, {
                    method: 'DELETE',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                        'Accept': 'application/json',
                        'Content-Type': 'application/json'
                    }
                })
                .then(response => {
                    if (!response.ok) {
                        return response.json().then(err => { throw new Error(err.message || 'Error deleting add-on'); });
                    }
                    return response.json();
                })
                .then(data => {
                    if (data.success) {
                        showAlert('success', data.message || 'Add-on deleted successfully!');
                        const row = this.closest('tr');
                        row.style.transition = 'all 0.3s ease';
                        row.style.opacity = '0';
                        row.style.transform = 'translateX(20px)';
                        setTimeout(() => {
                            row.remove();
                            const tbody = document.querySelector('.addons-table tbody');
                            if (tbody && tbody.children.length === 0) location.reload();
                        }, 300);
                    } else {
                        showAlert('danger', data.message || 'Error deleting add-on');
                        this.disabled = false;
                        this.innerHTML = originalHtml;
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    showAlert('danger', error.message || 'Error deleting add-on');
                    this.disabled = false;
                    this.innerHTML = originalHtml;
                });
            }
        });
    });

    // ─── View Addon Modal ─────────────────────────────────────────────────────
    const viewAddonModal = document.getElementById('viewAddonModal');
    if (viewAddonModal) {
        viewAddonModal.addEventListener('show.bs.modal', function(event) {
            const button = event.relatedTarget;
            const name        = button.getAttribute('data-addon-name');
            const slug        = button.getAttribute('data-addon-slug');
            const description = button.getAttribute('data-addon-description') || 'No description provided';
            const price       = button.getAttribute('data-addon-price');
            const isActive    = button.getAttribute('data-addon-is-active') === '1';
            const created     = button.getAttribute('data-addon-created');
            const updated     = button.getAttribute('data-addon-updated');
            const usage       = button.getAttribute('data-addon-usage') || '0';
            const addonId     = button.getAttribute('data-addon-id');

            document.getElementById('viewAddonName').textContent        = name;
            document.getElementById('viewAddonSlug').textContent        = slug;
            document.getElementById('viewAddonDescription').textContent = description;
            document.getElementById('viewAddonPrice').textContent       = `₱${parseFloat(price).toFixed(2)}`;
            document.getElementById('viewAddonUsage').textContent       = `${usage} time${usage != 1 ? 's' : ''}`;
            document.getElementById('viewAddonCreated').textContent     = created;
            document.getElementById('viewAddonUpdated').textContent     = updated;

            const statusSpan  = document.getElementById('viewAddonStatus');
            const statusText  = document.getElementById('viewAddonStatusText');
            const statusBadge = document.getElementById('viewAddonStatusBadge');

            if (isActive) {
                statusSpan.className    = 'detail-badge active';
                statusText.textContent  = 'ACTIVE';
                statusBadge.innerHTML   = '<span class="badge bg-success">Active</span>';
            } else {
                statusSpan.className    = 'detail-badge inactive';
                statusText.textContent  = 'INACTIVE';
                statusBadge.innerHTML   = '<span class="badge bg-secondary">Inactive</span>';
            }

            const editBtn = document.getElementById('viewAddonEditBtn');
            editBtn.onclick = function() {
                const viewModal = bootstrap.Modal.getInstance(viewAddonModal);
                viewModal.hide();
                const editButton = document.querySelector(`.edit-addon[data-addon-id="${addonId}"]`);
                if (editButton) setTimeout(() => editButton.click(), 300);
            };
        });
    }

    // ─── Edit Addon Modal ─────────────────────────────────────────────────────
    const editAddonModal = document.getElementById('editAddonModal');
    if (editAddonModal) {
        editAddonModal.addEventListener('show.bs.modal', function(event) {
            const button  = event.relatedTarget;
            const addonId = button.getAttribute('data-addon-id');
            const form    = document.getElementById('editAddonForm');

            @if(Route::has('admin.addons.update'))
                form.action = "{{ route('admin.addons.update', ['addon' => ':id']) }}".replace(':id', addonId);
            @else
                form.action = `/admin/addons/${addonId}`;
            @endif

            document.getElementById('editAddonId').value          = addonId;
            document.getElementById('editAddonName').value        = button.getAttribute('data-addon-name');
            document.getElementById('editAddonSlug').value        = button.getAttribute('data-addon-slug');
            document.getElementById('editAddonDescription').value = button.getAttribute('data-addon-description') || '';
            document.getElementById('editAddonPrice').value       = button.getAttribute('data-addon-price');
            document.getElementById('editAddonActive').checked    = button.getAttribute('data-addon-is-active') === '1';
        });
    }

    // ─── Slug auto-generator ──────────────────────────────────────────────────
    function slugify(text) {
        return text.toLowerCase()
            .replace(/[^a-z0-9\s-]/g, '')
            .replace(/\s+/g, '-')
            .replace(/-+/g, '-')
            .trim();
    }

    const createAddonModalEl = document.getElementById('createAddonModal');
    if (createAddonModalEl) {
        const createName = createAddonModalEl.querySelector('input[name="name"]');
        const createSlug = createAddonModalEl.querySelector('input[name="slug"]');
        if (createName && createSlug) {
            createName.addEventListener('input', function() {
                if (!createSlug.value || createSlug.dataset.manual !== 'true') {
                    createSlug.value = slugify(this.value);
                }
            });
            createSlug.addEventListener('input', function() { this.dataset.manual = 'true'; });
        }
    }

    const editNameInput = document.getElementById('editAddonName');
    const editSlugInput = document.getElementById('editAddonSlug');
    if (editNameInput && editSlugInput) {
        editNameInput.addEventListener('input', function() {
            if (!editSlugInput.value || editSlugInput.dataset.manual !== 'true') {
                editSlugInput.value = slugify(this.value);
            }
        });
        editSlugInput.addEventListener('input', function() { this.dataset.manual = 'true'; });
    }

    // ─── Create Addon AJAX ────────────────────────────────────────────────────
    if (createAddonForm) {
        createAddonForm.addEventListener('submit', function(e) {
            e.preventDefault();

            const submitBtn = this.querySelector('button[type="submit"]');
            const jsonData = {
                name:        this.querySelector('input[name="name"]').value,
                slug:        this.querySelector('input[name="slug"]').value,
                description: this.querySelector('textarea[name="description"]').value || '',
                price:       parseFloat(this.querySelector('input[name="price"]').value),
                is_active:   this.querySelector('input[name="is_active"]').checked
            };

            if (isNaN(jsonData.price) || jsonData.price < 0) {
                showAlert('danger', 'Please enter a valid price');
                return;
            }

            submitBtn.disabled = true;
            submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Creating...';

            fetch(this.action, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                    'Content-Type': 'application/json',
                    'Accept': 'application/json'
                },
                body: JSON.stringify(jsonData)
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showAlert('success', data.message || 'Add-on created successfully!');
                    const modal = bootstrap.Modal.getInstance(createAddonModalEl);
                    modal.hide();
                    this.reset();
                    setTimeout(() => location.reload(), 1500);
                } else {
                    showAlert('danger', data.message || 'Error creating add-on');
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = 'Create Add-On';
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showAlert('danger', 'Error creating add-on');
                submitBtn.disabled = false;
                submitBtn.innerHTML = 'Create Add-On';
            });
        });
    }

    // ─── Edit Addon AJAX ──────────────────────────────────────────────────────
    const editAddonForm = document.getElementById('editAddonForm');
    if (editAddonForm) {
        editAddonForm.addEventListener('submit', function(e) {
            e.preventDefault();

            const submitBtn = this.querySelector('button[type="submit"]');
            const jsonData = {
                name:        document.getElementById('editAddonName').value,
                slug:        document.getElementById('editAddonSlug').value,
                description: document.getElementById('editAddonDescription').value || '',
                price:       parseFloat(document.getElementById('editAddonPrice').value),
                is_active:   document.getElementById('editAddonActive').checked,
                _method:     'PUT'
            };

            if (isNaN(jsonData.price) || jsonData.price < 0) {
                showAlert('danger', 'Please enter a valid price');
                return;
            }

            submitBtn.disabled = true;
            submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Updating...';

            fetch(this.action, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                    'Content-Type': 'application/json',
                    'Accept': 'application/json'
                },
                body: JSON.stringify(jsonData)
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showAlert('success', data.message || 'Add-on updated successfully!');
                    const modal = bootstrap.Modal.getInstance(editAddonModal);
                    modal.hide();
                    setTimeout(() => location.reload(), 1500);
                } else {
                    showAlert('danger', data.message || 'Error updating add-on');
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = 'Update Add-On';
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showAlert('danger', 'Error updating add-on');
                submitBtn.disabled = false;
                submitBtn.innerHTML = 'Update Add-On';
            });
        });
    }
});

// ─── Create Service Type via AJAX ─────────────────────────────────────────────
const stForm      = document.getElementById('createServiceTypeForm');
const stFormError = document.getElementById('stFormError');
const stSubmitBtn = document.getElementById('stSubmitBtn');

if (stForm) {
    stForm.addEventListener('submit', function(e) {
        e.preventDefault();
        stFormError.classList.add('d-none');
        stFormError.innerHTML = '';

        // Validate defaults JSON client-side
        const defaultsVal = (document.getElementById('stDefaults')?.value || '').trim();
        if (defaultsVal !== '') {
            try { JSON.parse(defaultsVal); }
            catch (err) {
                stFormError.textContent = 'Defaults is not valid JSON. Fix it or leave it blank.';
                stFormError.classList.remove('d-none');
                return;
            }
        }

        stSubmitBtn.disabled = true;
        stSubmitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Saving...';

        const formData = new FormData(stForm);

        fetch('/admin/service-types', {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                'Accept': 'application/json',
            },
            body: formData,
        })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                showAlert('success', data.message || 'Service type created!');
                bootstrap.Modal.getInstance(document.getElementById('createServiceTypeModal'))?.hide();
                stForm.reset();
                setTimeout(() => location.reload(), 1200);
            } else {
                let msg = data.message || 'Failed to save.';
                if (data.errors) {
                    msg += '<ul class="mb-0 mt-1 ps-3">' +
                        Object.values(data.errors).flat().map(e => `<li>${e}</li>`).join('') +
                        '</ul>';
                }
                stFormError.innerHTML = msg;
                stFormError.classList.remove('d-none');
                stSubmitBtn.disabled = false;
                stSubmitBtn.innerHTML = '<i class="bi bi-plus-circle me-1"></i>Create Service Type';
            }
        })
        .catch(() => {
            stFormError.textContent = 'Network error — please try again.';
            stFormError.classList.remove('d-none');
            stSubmitBtn.disabled = false;
            stSubmitBtn.innerHTML = '<i class="bi bi-plus-circle me-1"></i>Create Service Type';
        });
    });
}

// ─── Alert helper ─────────────────────────────────────────────────────────────
function showAlert(type, message) {
    document.querySelectorAll('.alert-position-fixed').forEach(alert => alert.remove());

    const alertDiv = document.createElement('div');
    alertDiv.className = `alert alert-${type} alert-dismissible fade show alert-position-fixed`;
    alertDiv.style.cssText = 'position:fixed;top:20px;right:20px;z-index:9999;max-width:400px;box-shadow:0 0.5rem 1rem rgba(0,0,0,0.15);animation:slideInRight 0.3s ease-out;';
    alertDiv.innerHTML = `${message}<button type="button" class="btn-close" data-bs-dismiss="alert"></button>`;
    document.body.appendChild(alertDiv);

    setTimeout(() => { if (alertDiv.parentNode) alertDiv.remove(); }, 5000);
}
</script>
@endpush
