@extends('admin.layouts.app')

@section('title', 'Services & Add-Ons Management')
@section('page-title', 'Services & Add-Ons Management')

@php
    // Fetch add-ons with usage count
    $addons = $addons ?? \App\Models\AddOn::withCount('laundries')->latest()->get();

    // Load services with laundries count
    $services = $services ?? \App\Models\Service::withCount('laundries')->latest()->get();

    // Group services by category for better display
    $fullServiceItems = $services->where('category', 'drop_off');
    $selfServiceItems = $services->where('category', 'self_service');
    $addonServiceItems = $services->where('category', 'addon');

    // Get service types for the modal
    $serviceTypes = \App\Models\ServiceType::orderBy('category')->orderBy('display_order')->get()->groupBy('category');
@endphp

@push('styles')
    <link rel="stylesheet" href="{{ asset('assets/css/services.css') }}">
    <style>
        /* Modern Card Design */
        .service-card {
            transition: all 0.3s ease;
            border-radius: 16px;
            overflow: hidden;
            border: 1px solid rgba(0,0,0,0.05);
        }
        .service-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(0,0,0,0.1) !important;
        }
        .service-header {
            padding: 20px 20px 10px;
            border-bottom: 1px solid rgba(0,0,0,0.05);
        }
        .service-body {
            padding: 15px 20px;
        }
        .service-footer {
            padding: 15px 20px;
            border-top: 1px solid rgba(0,0,0,0.05);
            background: rgba(0,0,0,0.02);
        }
        .price-tag {
            font-size: 24px;
            font-weight: 700;
        }
        .price-unit {
            font-size: 14px;
            font-weight: 400;
            color: #6c757d;
        }
        .inclusion-item {
            font-size: 13px;
            padding: 5px 0;
            border-bottom: 1px dashed rgba(0,0,0,0.05);
        }
        .inclusion-item:last-child {
            border-bottom: none;
        }
        .inclusion-icon {
            color: #10B981;
            margin-right: 8px;
            font-size: 12px;
        }
        .badge-service {
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 600;
            letter-spacing: 0.3px;
        }
        .section-title {
            font-size: 18px;
            font-weight: 700;
            padding: 10px 20px;
            border-radius: 10px;
            color: white;
            display: inline-block;
        }
        .service-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
            gap: 20px;
        }
        .addon-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }
        .addon-card {
            background: white;
            border-radius: 16px;
            padding: 20px;
            transition: all 0.3s ease;
            border: 1px solid rgba(0,0,0,0.05);
            position: relative;
            overflow: hidden;
        }
        .addon-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(245,158,11,0.15);
            border-color: #F59E0B;
        }
        .addon-card.inactive {
            opacity: 0.6;
        }
        .addon-card.inactive:hover {
            opacity: 0.8;
        }
        .addon-icon {
            width: 50px;
            height: 50px;
            background: rgba(245,158,11,0.1);
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 15px;
        }
        .addon-icon i {
            font-size: 24px;
            color: #F59E0B;
        }
        .addon-name {
            font-size: 18px;
            font-weight: 700;
            margin-bottom: 5px;
            color: #333;
        }
        .addon-price {
            font-size: 24px;
            font-weight: 800;
            color: #F59E0B;
            margin-bottom: 10px;
        }
        .addon-price small {
            font-size: 14px;
            font-weight: 400;
            color: #6c757d;
        }
        .addon-description {
            font-size: 13px;
            color: #6c757d;
            margin-bottom: 15px;
            line-height: 1.5;
        }
        .addon-meta {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 15px;
            padding-top: 15px;
            border-top: 1px solid rgba(0,0,0,0.05);
        }
        .addon-usage {
            font-size: 12px;
            background: #f8f9fa;
            padding: 4px 10px;
            border-radius: 20px;
            color: #6c757d;
        }
        .addon-usage i {
            margin-right: 5px;
            color: #F59E0B;
        }
        .addon-actions {
            display: flex;
            gap: 5px;
        }
        .addon-actions .btn {
            padding: 4px 8px;
            font-size: 12px;
            border-radius: 8px;
        }
        .status-badge {
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 600;
        }
        .status-badge.active {
            background: rgba(16,185,129,0.1);
            color: #10B981;
        }
        .status-badge.inactive {
            background: rgba(108,117,125,0.1);
            color: #6c757d;
        }
        .toggle-switch {
            width: 40px;
            height: 20px;
            background: #e9ecef;
            border-radius: 20px;
            position: relative;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        .toggle-switch.active {
            background: #10B981;
        }
        .toggle-switch::after {
            content: '';
            width: 16px;
            height: 16px;
            background: white;
            border-radius: 50%;
            position: absolute;
            top: 2px;
            left: 2px;
            transition: all 0.3s ease;
        }
        .toggle-switch.active::after {
            left: 22px;
        }
        @media (max-width: 768px) {
            .service-grid, .addon-grid {
                grid-template-columns: 1fr;
            }
        }
        .service-type-badge {
            display: inline-block;
            padding: 3px 10px;
            border-radius: 20px;
            font-size: 10px;
            font-weight: 600;
            background: #e9ecef;
            color: #495057;
        }
        .service-type-badge.drop_off {
            background: rgba(61,59,107,0.1);
            color: #3D3B6B;
        }
        .service-type-badge.self_service {
            background: rgba(16,185,129,0.1);
            color: #10B981;
        }
        .service-type-badge.addon {
            background: rgba(245,158,11,0.1);
            color: #F59E0B;
        }
        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 24px;
        }
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 20px;
            margin-bottom: 30px;
        }
        .stat-card {
            background: white;
            border-radius: 16px;
            padding: 24px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.05);
            border: 1px solid rgba(0,0,0,0.05);
            transition: all 0.3s ease;
        }
        .stat-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 15px rgba(0,0,0,0.1);
        }
        .stat-icon-wrapper {
            width: 48px;
            height: 48px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
        }
        .stat-icon-wrapper.primary {
            background: rgba(61,59,107,0.1);
            color: #3D3B6B;
        }
        .stat-icon-wrapper.success {
            background: rgba(16,185,129,0.1);
            color: #10B981;
        }
        .stat-icon-wrapper.info {
            background: rgba(13,110,253,0.1);
            color: #0d6efd;
        }
        .stat-icon-wrapper.warning {
            background: rgba(245,158,11,0.1);
            color: #F59E0B;
        }
        .stat-label {
            font-size: 13px;
            color: #6c757d;
            margin-bottom: 4px;
        }
        .stat-value {
            font-size: 24px;
            font-weight: 700;
            color: #212529;
        }
        .table-card {
            background: white;
            border-radius: 20px;
            padding: 24px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.05);
            margin-bottom: 30px;
            border: 1px solid rgba(0,0,0,0.05);
        }
        .table-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }
        .table-header h6 {
            margin: 0;
            font-size: 16px;
            font-weight: 600;
            color: #212529;
        }
        .services-table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
        }
        .services-table th {
            text-align: left;
            padding: 12px 16px;
            background: #f8f9fa;
            font-size: 13px;
            font-weight: 600;
            color: #495057;
            border-bottom: 2px solid #dee2e6;
        }
        .services-table td {
            padding: 16px;
            border-bottom: 1px solid #e9ecef;
            vertical-align: middle;
        }
        .services-table tbody tr:hover {
            background: #f8f9fa;
        }
        .service-name {
            font-weight: 600;
            color: #212529;
        }
        .service-description {
            font-size: 12px;
            color: #6c757d;
            margin-top: 4px;
        }
        .type-badge {
            display: inline-block;
            padding: 4px 8px;
            border-radius: 6px;
            font-size: 11px;
            font-weight: 600;
            background: #e9ecef;
            color: #495057;
        }
        .pricing-badge {
            display: inline-block;
            padding: 4px 8px;
            border-radius: 6px;
            font-size: 11px;
            font-weight: 600;
            background: #e9ecef;
            color: #495057;
        }
        .price-display {
            font-weight: 600;
            color: #3D3B6B;
        }
        .price-display.primary {
            color: #3D3B6B;
        }
        .status-wrapper {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .status-badge-sm {
            display: inline-block;
            padding: 4px 8px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 600;
        }
        .status-badge-sm.active {
            background: rgba(16,185,129,0.1);
            color: #10B981;
        }
        .status-badge-sm.inactive {
            background: rgba(108,117,125,0.1);
            color: #6c757d;
        }
        .usage-badge {
            display: inline-block;
            padding: 4px 8px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 600;
            background: #f8f9fa;
            color: #6c757d;
        }
        .usage-badge.has-usage {
            background: rgba(16,185,129,0.1);
            color: #10B981;
        }
        .action-group {
            display: flex;
            gap: 8px;
            justify-content: flex-end;
        }
        .action-btn {
            width: 32px;
            height: 32px;
            border-radius: 8px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            background: transparent;
            border: 1px solid transparent;
            color: #6c757d;
            transition: all 0.2s ease;
        }
        .action-btn:hover {
            background: #f8f9fa;
            color: #212529;
        }
        .action-btn.view:hover {
            background: rgba(13,110,253,0.1);
            color: #0d6efd;
        }
        .action-btn.edit:hover {
            background: rgba(255,193,7,0.1);
            color: #ffc107;
        }
        .action-btn.delete:hover {
            background: rgba(220,53,69,0.1);
            color: #dc3545;
        }
        .empty-state {
            text-align: center;
            padding: 60px 20px;
        }
        .empty-state i {
            font-size: 48px;
            color: #dee2e6;
            margin-bottom: 16px;
        }
        .empty-state h5 {
            font-size: 18px;
            font-weight: 600;
            color: #212529;
            margin-bottom: 8px;
        }
        .empty-state p {
            color: #6c757d;
            margin-bottom: 20px;
        }
        .modal-content {
            border: none;
            border-radius: 20px;
        }
        .modal-header {
            border-radius: 20px 20px 0 0;
            padding: 20px 24px;
        }
        .modal-body {
            padding: 24px;
        }
        .modal-footer {
            padding: 20px 24px;
            border-top: 1px solid #e9ecef;
        }
        .form-label {
            font-size: 14px;
            margin-bottom: 8px;
        }
        .required {
            color: #dc3545;
        }
        .btn-group .btn {
            padding: 10px 16px;
        }
        .service-types-summary {
            background: #f8f9fa;
            border-radius: 12px;
            padding: 16px;
            margin-bottom: 24px;
        }
        .service-type-tag {
            display: inline-flex;
            align-items: center;
            padding: 6px 12px;
            background: white;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 500;
            margin-right: 8px;
            margin-bottom: 8px;
            border: 1px solid #e9ecef;
        }
        .service-type-tag i {
            margin-right: 4px;
        }
    </style>
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

    {{-- Service Types Summary --}}
    @if($serviceTypes && $serviceTypes->count() > 0)
    <div class="service-types-summary">
        <div class="d-flex align-items-center mb-2">
            <i class="bi bi-tags me-2" style="color: #3D3B6B;"></i>
            <span class="fw-semibold">Service Types:</span>
        </div>
        <div>
            @foreach($serviceTypes as $category => $types)
                @foreach($types as $type)
                    <span class="service-type-tag">
                        <i class="bi {{ $type->icon ?? 'bi-tag' }}" style="color:
                            @if($category == 'drop_off') #3D3B6B
                            @elseif($category == 'self_service') #10B981
                            @else #F59E0B
                            @endif
                        "></i>
                        {{ $type->name }}
                    </span>
                @endforeach
            @endforeach
        </div>
    </div>
    @endif

    {{-- FULL SERVICE PACKAGES --}}
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h5 class="fw-bold" style="color: #3D3B6B;">
            <i class="bi bi-bag-check me-2"></i>FULL SERVICE PACKAGES
        </h5>
        <div class="d-flex align-items-center gap-3">
            <div class="form-check form-switch">
                <input class="form-check-input" type="checkbox" id="showInactiveServices" checked>
                <label class="form-check-label small fw-semibold" for="showInactiveServices">Show Inactive</label>
            </div>
            <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#createServiceModal"
                    onclick="setTimeout(() => {
                        document.getElementById('modalCategory').value = 'drop_off';
                        document.getElementById('modalCategory').dispatchEvent(new Event('change'));
                        document.getElementById('modalServiceType').value = '';
                        console.log('Category set to: drop_off');
                    }, 300)">
                <i class="bi bi-plus-circle me-1"></i>New Service
            </button>
        </div>
    </div>

    @if($fullServiceItems->count() > 0)
    <div class="service-grid mb-5" id="fullServiceCards">
        @foreach($fullServiceItems as $service)
        @php
            $pt = $service->pricing_type ?? 'per_load';
            $priceVal = $pt === 'per_piece' ? ($service->price_per_piece ?? 0) : ($service->price_per_load ?? 0);
            $priceUnit = $pt === 'per_piece' ? 'piece' : 'load';
            $usageCount = $service->laundries_count ?? 0;
            $serviceType = $service->service_type ?? '';
            $serviceTypeName = $service->serviceType?->name ?? $serviceType;

            // Determine card styling based on service type
            $cardColor = '#3D3B6B';
            $cardIcon = 'bi-bag-check';
            $cardTitle = 'Regular';

            if (str_contains(strtolower($service->name), 'premium') || str_contains(strtolower($serviceType), 'premium')) {
                $cardColor = '#6366F1';
                $cardIcon = 'bi-award';
                $cardTitle = 'Premium';
            } elseif (str_contains(strtolower($service->name), 'comforter') || str_contains(strtolower($serviceType), 'special')) {
                $cardColor = '#8B5CF6';
                $cardIcon = 'bi-stars';
                $cardTitle = 'Comforter';
            } elseif ($pt === 'per_piece') {
                $cardColor = '#8B5CF6';
                $cardIcon = 'bi-stars';
                $cardTitle = 'Special Item';
            }

            // Parse description for inclusions
            $description = $service->description ?? '';
        @endphp
        <div class="service-row" data-active="{{ $service->is_active ? '1' : '0' }}" data-id="{{ $service->id }}">
            <div class="card service-card border-0 shadow-sm h-100 {{ !$service->is_active ? 'opacity-50' : '' }}">
                <div class="service-header" style="border-left: 5px solid {{ $cardColor }};">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <span class="badge-service" style="background: {{ $cardColor }}20; color: {{ $cardColor }};">
                                <i class="bi {{ $cardIcon }} me-1"></i>{{ $cardTitle }}
                            </span>
                            <h5 class="fw-bold mt-2 mb-1">{{ $service->name }}</h5>
                            @if($serviceTypeName)
                                <small class="text-muted">{{ ucfirst(str_replace('_', ' ', $serviceTypeName)) }}</small>
                            @endif
                            @if($service->serviceType)
                                <span class="service-type-badge {{ $service->category }}" style="display: inline-block; margin-top: 5px;">
                                    <i class="bi {{ $service->serviceType->icon ?? 'bi-tag' }} me-1"></i>
                                    {{ $service->serviceType->name }}
                                </span>
                            @endif
                        </div>
                        <div class="text-end">
                            <div class="price-tag" style="color: {{ $cardColor }};">₱{{ number_format($priceVal, 0) }}</div>
                            <div class="price-unit">per {{ $priceUnit }}</div>
                        </div>
                    </div>
                </div>
                <div class="service-body">
                    @if($service->max_weight)
                        <div class="inclusion-item">
                            <i class="bi bi-check-circle-fill inclusion-icon"></i>
                            <span>up to {{ $service->max_weight }}kg per load for regular clothes</span>
                        </div>
                    @endif

                    @if(str_contains(strtolower($service->name), 'premium') || str_contains(strtolower($serviceType), 'premium'))
                        <div class="inclusion-item">
                            <i class="bi bi-check-circle-fill inclusion-icon"></i>
                            <span>2 sachets of Ariel or Breeze laundry detergent</span>
                        </div>
                        <div class="inclusion-item">
                            <i class="bi bi-check-circle-fill inclusion-icon"></i>
                            <span>2 sachets branded Del fabric conditioner</span>
                        </div>
                        <div class="inclusion-item">
                            <i class="bi bi-check-circle-fill inclusion-icon"></i>
                            <span>60ml of Zonrox bleach colorsafe</span>
                        </div>
                    @elseif(str_contains(strtolower($service->name), 'regular'))
                        <div class="inclusion-item">
                            <i class="bi bi-check-circle-fill inclusion-icon"></i>
                            <span>wash, dry & fold</span>
                        </div>
                        <div class="inclusion-item">
                            <i class="bi bi-check-circle-fill inclusion-icon"></i>
                            <span>free laundry detergent</span>
                        </div>
                        <div class="inclusion-item">
                            <i class="bi bi-check-circle-fill inclusion-icon"></i>
                            <span>free branded Del fabric conditioner</span>
                        </div>
                    @elseif(str_contains(strtolower($service->name), 'comforter') || str_contains(strtolower($serviceType), 'special'))
                        <div class="inclusion-item">
                            <i class="bi bi-check-circle-fill inclusion-icon"></i>
                            <span>All in including detergent & fabcon</span>
                        </div>
                        @if(str_contains(strtolower($service->name), 'small'))
                            <div class="inclusion-item">
                                <i class="bi bi-check-circle-fill inclusion-icon"></i>
                                <span>Small - ₱150 per piece</span>
                            </div>
                        @elseif(str_contains(strtolower($service->name), 'medium'))
                            <div class="inclusion-item">
                                <i class="bi bi-check-circle-fill inclusion-icon"></i>
                                <span>Medium - ₱180 per piece</span>
                            </div>
                        @elseif(str_contains(strtolower($service->name), 'large'))
                            <div class="inclusion-item">
                                <i class="bi bi-check-circle-fill inclusion-icon"></i>
                                <span>Large - ₱200 per piece</span>
                            </div>
                        @endif
                    @endif

                    @if($description)
                        <div class="inclusion-item">
                            <i class="bi bi-info-circle-fill inclusion-icon" style="color: #0d6efd;"></i>
                            <span>{{ $description }}</span>
                        </div>
                    @endif
                </div>
                <div class="service-footer">
                    <div class="d-flex justify-content-between align-items-center">
                        <div class="d-flex align-items-center gap-2">
                            <span class="badge {{ $service->is_active ? 'bg-success' : 'bg-secondary' }}" style="font-size:11px;">
                                {{ $service->is_active ? 'Active' : 'Inactive' }}
                            </span>
                            <div class="toggle-switch {{ $service->is_active ? 'active' : '' }} service-status-toggle"
                                 data-id="{{ $service->id }}" style="transform:scale(.7);"></div>
                            <span class="badge bg-light text-muted" style="font-size:11px;">
                                <i class="bi bi-receipt me-1"></i>{{ $usageCount }} uses
                            </span>
                        </div>
                        <div class="d-flex gap-1">
                            <a href="{{ route('admin.services.show', $service) }}" class="btn btn-sm btn-outline-primary py-1 px-2" title="View">
                                <i class="bi bi-eye"></i>
                            </a>
                            <a href="{{ route('admin.services.edit', $service) }}" class="btn btn-sm btn-outline-secondary py-1 px-2" title="Edit">
                                <i class="bi bi-pencil"></i>
                            </a>
                            <button type="button" class="btn btn-sm btn-outline-danger py-1 px-2 delete-service" title="Delete"
                                    data-id="{{ $service->id }}" data-name="{{ $service->name }}" data-usage="{{ $usageCount }}">
                                <i class="bi bi-trash"></i>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        @endforeach
    </div>
    @else
    <div class="empty-state mb-4">
        <i class="bi bi-bag-check" style="font-size: 3rem; color: #3D3B6B;"></i>
        <h5>No full service packages yet</h5>
        <p class="text-muted">Create your first drop-off service</p>
        <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#createServiceModal"
                onclick="setTimeout(() => {
                    document.getElementById('modalCategory').value = 'drop_off';
                    document.getElementById('modalCategory').dispatchEvent(new Event('change'));
                    document.getElementById('modalServiceType').value = '';
                }, 300)">
            <i class="bi bi-plus-circle me-2"></i>Add Service
        </button>
    </div>
    @endif

    {{-- SELF SERVICE --}}
    <div class="d-flex justify-content-between align-items-center mb-3 mt-4">
        <h5 class="fw-bold" style="color: #10B981;">
            <i class="bi bi-person-workspace me-2"></i>SELF SERVICE
        </h5>
        <button type="button" class="btn btn-success btn-sm px-3" data-bs-toggle="modal" data-bs-target="#createServiceModal"
                onclick="setTimeout(() => {
                    document.getElementById('modalCategory').value = 'self_service';
                    document.getElementById('modalCategory').dispatchEvent(new Event('change'));
                    document.getElementById('modalServiceType').value = '';
                    console.log('Category set to: self_service');
                }, 300)">
            <i class="bi bi-plus-circle me-1"></i>New Self Service
        </button>
    </div>

    @if($selfServiceItems->count() > 0)
    <div class="service-grid mb-5" id="selfServiceCards">
        @foreach($selfServiceItems as $service)
        @php
            $pt = $service->pricing_type ?? 'per_load';
            $priceVal = $pt === 'per_piece' ? ($service->price_per_piece ?? 0) : ($service->price_per_load ?? 0);
            $priceUnit = $pt === 'per_piece' ? 'piece' : 'load';
            $usageCount = $service->laundries_count ?? 0;
            $serviceType = $service->service_type ?? '';
            $serviceTypeName = $service->serviceType?->name ?? $serviceType;

            // Determine service label
            $serviceLabel = 'Self Service';
            if (str_contains(strtolower($serviceTypeName), 'wash')) {
                $serviceLabel = 'Wash';
            } elseif (str_contains(strtolower($serviceTypeName), 'dry')) {
                $serviceLabel = 'Dry';
            } elseif (str_contains(strtolower($serviceTypeName), 'fold')) {
                $serviceLabel = 'Fold';
            } elseif (str_contains(strtolower($serviceTypeName), 'extra')) {
                $serviceLabel = 'Add 10 Minutes Dry';
            }
        @endphp
        <div class="service-row" data-active="{{ $service->is_active ? '1' : '0' }}" data-id="{{ $service->id }}">
            <div class="card service-card border-0 shadow-sm h-100 {{ !$service->is_active ? 'opacity-50' : '' }}">
                <div class="service-header" style="border-left: 5px solid #10B981;">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <span class="badge-service" style="background: rgba(16,185,129,0.1); color: #10B981;">
                                <i class="bi bi-person-workspace me-1"></i>{{ $serviceLabel }}
                            </span>
                            <h5 class="fw-bold mt-2 mb-1">{{ $service->name }}</h5>
                            @if($service->serviceType)
                                <span class="service-type-badge {{ $service->category }}" style="display: inline-block; margin-top: 5px;">
                                    <i class="bi {{ $service->serviceType->icon ?? 'bi-tag' }} me-1"></i>
                                    {{ $service->serviceType->name }}
                                </span>
                            @endif
                        </div>
                        <div class="text-end">
                            <div class="price-tag" style="color: #10B981;">₱{{ number_format($priceVal, 0) }}</div>
                            <div class="price-unit">per {{ $priceUnit }}</div>
                        </div>
                    </div>
                </div>
                <div class="service-body">
                    <div class="inclusion-item">
                        <i class="bi bi-clock-history inclusion-icon" style="color: #10B981;"></i>
                        <span>{{ $service->turnaround_time }} hour turnaround</span>
                    </div>
                    @if($service->description)
                        <div class="inclusion-item">
                            <i class="bi bi-info-circle-fill inclusion-icon" style="color: #10B981;"></i>
                            <span>{{ $service->description }}</span>
                        </div>
                    @endif
                </div>
                <div class="service-footer">
                    <div class="d-flex justify-content-between align-items-center">
                        <div class="d-flex align-items-center gap-2">
                            <span class="badge {{ $service->is_active ? 'bg-success' : 'bg-secondary' }}" style="font-size:11px;">
                                {{ $service->is_active ? 'Active' : 'Inactive' }}
                            </span>
                            <div class="toggle-switch {{ $service->is_active ? 'active' : '' }} service-status-toggle"
                                 data-id="{{ $service->id }}" style="transform:scale(.7);"></div>
                            <span class="badge bg-light text-muted" style="font-size:11px;">
                                <i class="bi bi-receipt me-1"></i>{{ $usageCount }} uses
                            </span>
                        </div>
                        <div class="d-flex gap-1">
                            <a href="{{ route('admin.services.show', $service) }}" class="btn btn-sm btn-outline-primary py-1 px-2" title="View">
                                <i class="bi bi-eye"></i>
                            </a>
                            <a href="{{ route('admin.services.edit', $service) }}" class="btn btn-sm btn-outline-secondary py-1 px-2" title="Edit">
                                <i class="bi bi-pencil"></i>
                            </a>
                            <button type="button" class="btn btn-sm btn-outline-danger py-1 px-2 delete-service" title="Delete"
                                    data-id="{{ $service->id }}" data-name="{{ $service->name }}" data-usage="{{ $usageCount }}">
                                <i class="bi bi-trash"></i>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        @endforeach
    </div>
    @else
    <div class="empty-state mb-4">
        <i class="bi bi-person-workspace" style="font-size: 3rem; color: #10B981;"></i>
        <h5>No self service items yet</h5>
        <p class="text-muted">Create your first self service option</p>
        <button type="button" class="btn btn-success btn-sm" data-bs-toggle="modal" data-bs-target="#createServiceModal"
                onclick="setTimeout(() => {
                    document.getElementById('modalCategory').value = 'self_service';
                    document.getElementById('modalCategory').dispatchEvent(new Event('change'));
                    document.getElementById('modalServiceType').value = '';
                }, 300)">
            <i class="bi bi-plus-circle me-2"></i>Add Self Service
        </button>
    </div>
    @endif

    {{-- ADD-ONS SECTION --}}
    <div class="d-flex justify-content-between align-items-center mb-3 mt-4">
        <h5 class="fw-bold" style="color: #F59E0B;">
            <i class="bi bi-plus-circle me-2"></i>ADD-ON SERVICES
        </h5>
        <div class="d-flex align-items-center gap-3">
            <div class="form-check form-switch">
                <input class="form-check-input" type="checkbox" id="showInactiveAddons" checked>
                <label class="form-check-label small fw-semibold" for="showInactiveAddons">Show Inactive</label>
            </div>
            <button type="button" class="btn btn-success btn-sm" data-bs-toggle="modal" data-bs-target="#createAddonModal">
                <i class="bi bi-plus-circle me-1"></i>New Add-On
            </button>
        </div>
    </div>

    @if($addons->count() > 0)
    <div class="addon-grid mb-5" id="addonCards">
        @foreach($addons as $addon)
        @php
            $usageCount = $addon->laundries_count ?? 0;

            // Determine icon based on addon name
            $icon = 'bi-plus-circle';
            if (str_contains(strtolower($addon->name), 'ariel') || str_contains(strtolower($addon->name), 'breeze')) {
                $icon = 'bi-droplet-half';
            } elseif (str_contains(strtolower($addon->name), 'fabcon') || str_contains(strtolower($addon->name), 'fabric')) {
                $icon = 'bi-moisture';
            } elseif (str_contains(strtolower($addon->name), 'zonrox') || str_contains(strtolower($addon->name), 'bleach')) {
                $icon = 'bi-droplet';
            } elseif (str_contains(strtolower($addon->name), 'wash')) {
                $icon = 'bi-arrow-repeat';
            }
        @endphp
        <div class="addon-row" data-active="{{ $addon->is_active ? '1' : '0' }}" data-id="{{ $addon->id }}">
            <div class="addon-card {{ !$addon->is_active ? 'inactive' : '' }}">
                <div class="addon-icon">
                    <i class="bi {{ $icon }}"></i>
                </div>
                <div class="addon-name">{{ $addon->name }}</div>
                <div class="addon-price">
                    ₱{{ number_format($addon->price, 2) }}
                    <small>each</small>
                </div>
                @if($addon->description)
                    <div class="addon-description">{{ $addon->description }}</div>
                @else
                    <div class="addon-description text-muted fst-italic">No description</div>
                @endif

                <div class="addon-meta">
                    <div class="d-flex align-items-center gap-2">
                        <span class="status-badge {{ $addon->is_active ? 'active' : 'inactive' }}">
                            {{ $addon->is_active ? 'Active' : 'Inactive' }}
                        </span>
                        <div class="toggle-switch {{ $addon->is_active ? 'active' : '' }} addon-status-toggle"
                             data-id="{{ $addon->id }}" style="transform:scale(.8);"></div>
                    </div>
                    <span class="addon-usage">
                        <i class="bi bi-receipt"></i> {{ $usageCount }} {{ Str::plural('use', $usageCount) }}
                    </span>
                </div>

                <div class="addon-actions justify-content-end mt-3">
                    <button type="button" class="btn btn-outline-info btn-sm view-addon" title="View Details"
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
                    <button type="button" class="btn btn-outline-warning btn-sm edit-addon" title="Edit"
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
                    <button type="button" class="btn btn-outline-danger btn-sm delete-addon" title="Delete"
                            data-id="{{ $addon->id }}"
                            data-name="{{ $addon->name }}"
                            data-usage="{{ $usageCount }}">
                        <i class="bi bi-trash"></i>
                    </button>
                </div>
            </div>
        </div>
        @endforeach
    </div>
    @else
    <div class="empty-state mb-4">
        <i class="bi bi-plus-circle" style="font-size: 3rem; color: #F59E0B;"></i>
        <h5>No add-ons yet</h5>
        <p class="text-muted">Create your first add-on service</p>
        <button type="button" class="btn btn-success btn-sm" data-bs-toggle="modal" data-bs-target="#createAddonModal">
            <i class="bi bi-plus-lg me-2"></i>Create First Add-On
        </button>
    </div>
    @endif
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
                            <input type="text" name="name" class="form-control" required placeholder="e.g., Wash & Fold" id="modalServiceName">
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

    // Debug: Log category changes
    const modalCategory = document.getElementById('modalCategory');
    if (modalCategory) {
        modalCategory.addEventListener('change', function() {
            console.log('Category changed to:', this.value);
        });
    }

    // Debug: Log when modal opens
    const createServiceModal = document.getElementById('createServiceModal');
    if (createServiceModal) {
        createServiceModal.addEventListener('show.bs.modal', function() {
            console.log('Modal opening with category:', document.getElementById('modalCategory').value);
        });
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
            const row = this.closest('.service-row');
            const statusBadge = row.querySelector('.badge.bg-success, .badge.bg-secondary');

            // Optimistic update
            if (isActive) {
                this.classList.remove('active');
                statusBadge.classList.remove('bg-success');
                statusBadge.classList.add('bg-secondary');
                statusBadge.textContent = 'Inactive';
                row.dataset.active = '0';
            } else {
                this.classList.add('active');
                statusBadge.classList.remove('bg-secondary');
                statusBadge.classList.add('bg-success');
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
                    statusBadge.classList.add('bg-success');
                    statusBadge.classList.remove('bg-secondary');
                    statusBadge.textContent = 'Active';
                    row.dataset.active = '1';
                } else {
                    this.classList.remove('active');
                    statusBadge.classList.remove('bg-success');
                    statusBadge.classList.add('bg-secondary');
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
            const card = this.closest('.addon-row');
            const statusBadge = card.querySelector('.status-badge');

            // Optimistic update
            if (isActive) {
                this.classList.remove('active');
                statusBadge.classList.remove('active');
                statusBadge.classList.add('inactive');
                statusBadge.textContent = 'Inactive';
                card.dataset.active = '0';
            } else {
                this.classList.add('active');
                statusBadge.classList.remove('inactive');
                statusBadge.classList.add('active');
                statusBadge.textContent = 'Active';
                card.dataset.active = '1';
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
                    card.dataset.active = '1';
                } else {
                    this.classList.remove('active');
                    statusBadge.classList.remove('active');
                    statusBadge.classList.add('inactive');
                    statusBadge.textContent = 'Inactive';
                    card.dataset.active = '0';
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
                        const card = this.closest('.service-row');
                        card.style.transition = 'all 0.3s ease';
                        card.style.opacity = '0';
                        card.style.transform = 'translateX(20px)';
                        setTimeout(() => {
                            card.remove();
                            if (showInactiveServices) showInactiveServices.dispatchEvent(new Event('change'));
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
                        const card = this.closest('.addon-row');
                        card.style.transition = 'all 0.3s ease';
                        card.style.opacity = '0';
                        card.style.transform = 'translateX(20px)';
                        setTimeout(() => {
                            card.remove();
                            if (showInactiveAddons) showInactiveAddons.dispatchEvent(new Event('change'));
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
