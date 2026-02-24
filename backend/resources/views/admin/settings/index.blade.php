@use('App\Models\SystemSetting')
@use('Illuminate\Support\Facades\File')
@use('Illuminate\Support\Facades\Storage')
@extends('admin.layouts.app')

@section('page-title', 'System Settings')

@section('content')
<div class="container-fluid px-4 py-4">
    {{-- Header Section --}}
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <p class="text-muted small">Configure global rules, FCM notifications, and system maintenance.</p>
        </div>
        <div id="save-indicator" class="text-muted small d-none">
            <i class="bi bi-clock-history me-1"></i> Last saved: Just now
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success border-0 shadow-sm rounded-4 mb-4 d-flex align-items-center">
            <i class="bi bi-check-circle-fill fs-4 me-3"></i>
            <div>{{ session('success') }}</div>
            <button type="button" class="btn-close ms-auto" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <div class="row g-4">
        {{-- Navigation Sidebar --}}
        <div class="col-xl-3 col-lg-4">
            <div class="card border-0 shadow-sm rounded-4 sticky-top" style="top: 20px;">
                <div class="card-body p-3">
                    <div class="nav flex-column nav-pills" id="settingsTabs" role="tablist">
                        <button class="nav-link active d-flex align-items-center py-3 mb-1 text-start" data-bs-toggle="pill" data-bs-target="#general" type="button">
                            <i class="bi bi-shop fs-5 me-3"></i>
                            <div>
                                <div class="fw-bold">General</div>
                                <div class="x-small opacity-75">Branding & Contact</div>
                            </div>
                        </button>
                        <button class="nav-link d-flex align-items-center py-3 mb-1 text-start" data-bs-toggle="pill" data-bs-target="#pricing" type="button">
                            <i class="bi bi-currency-exchange fs-5 me-3"></i>
                            <div>
                                <div class="fw-bold">Pricing</div>
                                <div class="x-small opacity-75">Fees & Rates</div>
                            </div>
                        </button>
                        <button class="nav-link d-flex align-items-center py-3 mb-1 text-start" data-bs-toggle="pill" data-bs-target="#hours" type="button">
                            <i class="bi bi-clock fs-5 me-3"></i>
                            <div>
                                <div class="fw-bold">Business Hours</div>
                                <div class="x-small opacity-75">Operating Schedule</div>
                            </div>
                        </button>
                        <button class="nav-link d-flex align-items-center py-3 mb-1 text-start" data-bs-toggle="pill" data-bs-target="#pickup" type="button">
                            <i class="bi bi-truck fs-5 me-3"></i>
                            <div>
                                <div class="fw-bold">Pickup & Delivery</div>
                                <div class="x-small opacity-75">Service Settings</div>
                            </div>
                        </button>
                        <button class="nav-link d-flex align-items-center py-3 mb-1 text-start" data-bs-toggle="pill" data-bs-target="#receipt" type="button">
                            <i class="bi bi-receipt fs-5 me-3"></i>
                            <div>
                                <div class="fw-bold">Receipt & Invoice</div>
                                <div class="x-small opacity-75">Print Settings</div>
                            </div>
                        </button>
                        <button class="nav-link d-flex align-items-center py-3 mb-1 text-start" data-bs-toggle="pill" data-bs-target="#unclaimed" type="button">
                            <i class="bi bi-clock-history fs-5 me-3"></i>
                            <div>
                                <div class="fw-bold">Unclaimed Rules</div>
                                <div class="x-small opacity-75">Thresholds & Policy</div>
                            </div>
                        </button>
                        <button class="nav-link d-flex align-items-center py-3 mb-1 text-start" data-bs-toggle="pill" data-bs-target="#notifications" type="button">
                            <i class="bi bi-megaphone fs-5 me-3"></i>
                            <div>
                                <div class="fw-bold">Notifications</div>
                                <div class="x-small opacity-75">FCM & Push Alerts</div>
                            </div>
                        </button>
                        <button class="nav-link d-flex align-items-center py-3 mb-1 text-start" data-bs-toggle="pill" data-bs-target="#status" type="button">
                            <i class="bi bi-heart-pulse fs-5 me-3"></i>
                            <div>
                                <div class="fw-bold">System Health</div>
                                <div class="x-small opacity-75">Server & DB Status</div>
                            </div>
                        </button>
                        <button class="nav-link d-flex align-items-center py-3 text-start" data-bs-toggle="pill" data-bs-target="#backup" type="button">
                            <i class="bi bi-database-up fs-5 me-3"></i>
                            <div>
                                <div class="fw-bold">Backup & Data</div>
                                <div class="x-small opacity-75">Export & Security</div>
                            </div>
                        </button>
                    </div>
                </div>
            </div>
        </div>

        {{-- Settings Content Area --}}
        <div class="col-xl-9 col-lg-8">
            {{-- FIXED: Removed @method('PUT') since route only accepts POST --}}
            <form action="{{ route('admin.settings.update') }}" method="POST" enctype="multipart/form-data">
                @csrf

                <div class="tab-content">
                    {{-- GENERAL --}}
                    <div class="tab-pane fade show active" id="general">
                        <div class="card border-0 shadow-sm rounded-4 mb-4">
                            <div class="card-header bg-white py-3 border-bottom">
                                <h5 class="fw-bold mb-0">Identity & Branding</h5>
                            </div>
                            <div class="card-body p-4">
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label class="form-label fw-bold">Shop Display Name</label>
                                        <input type="text" name="shop_name" class="form-control rounded-3" value="{{ SystemSetting::get('shop_name', 'WashBox') }}">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label fw-bold">Official Contact No.</label>
                                        <input type="text" name="contact_number" class="form-control rounded-3" value="{{ SystemSetting::get('contact_number') }}">
                                    </div>
                                    <div class="col-12 mt-4">
                                        <label class="form-label fw-bold">System Logo</label>
                                        <div class="d-flex align-items-center bg-light p-4 rounded-4 border border-dashed">
                                            @if(SystemSetting::get('app_logo'))
                                                <img src="{{ Storage::url(SystemSetting::get('app_logo')) }}" class="rounded me-4 shadow-sm" style="height: 60px; width: 60px; object-fit: contain; background: #fff;">
                                            @endif
                                            <div>
                                                <input type="file" name="app_logo" class="form-control form-control-sm mb-1" accept="image/*">
                                                <span class="x-small text-muted">Recommended: PNG 512x512px with transparent background.</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- PRICING --}}
                    <div class="tab-pane fade" id="pricing">
                        {{-- Default Service Rates --}}
                        <div class="card border-0 shadow-sm rounded-4 mb-4">
                            <div class="card-header bg-white py-3 border-bottom">
                                <h5 class="fw-bold mb-0"><i class="bi bi-tag me-2 text-success"></i>Default Service Rates</h5>
                                <p class="text-muted x-small mb-0 mt-1">Fallback rates used when a service has no specific price configured.</p>
                            </div>
                            <div class="card-body p-4">
                                <div class="row g-3">
                                    <div class="col-md-4">
                                        <label class="form-label fw-bold">Price Per Piece</label>
                                        <div class="input-group">
                                            <span class="input-group-text">₱</span>
                                            <input type="number" name="default_price_per_piece" class="form-control" value="{{ SystemSetting::get('default_price_per_piece', 60) }}" min="0" step="0.01">
                                            <span class="input-group-text">/ piece</span>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label fw-bold">Price Per Load</label>
                                        <div class="input-group">
                                            <span class="input-group-text">₱</span>
                                            <input type="number" name="default_price_per_load" class="form-control" value="{{ SystemSetting::get('default_price_per_load', 120) }}" min="0" step="0.01">
                                            <span class="input-group-text">/ load</span>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label fw-bold">Minimum Order Amount</label>
                                        <div class="input-group">
                                            <span class="input-group-text">₱</span>
                                            <input type="number" name="minimum_order_amount" class="form-control" value="{{ SystemSetting::get('minimum_order_amount', 100) }}" min="0" step="0.01">
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        {{-- Storage & Penalty Fees --}}
                        <div class="card border-0 shadow-sm rounded-4 mb-4">
                            <div class="card-header bg-white py-3 border-bottom">
                                <h5 class="fw-bold mb-0"><i class="bi bi-box-seam me-2 text-warning"></i>Storage & Penalty Fees</h5>
                                <p class="text-muted x-small mb-0 mt-1">Applied to unclaimed laundry after the grace period ends.</p>
                            </div>
                            <div class="card-body p-4">
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label class="form-label fw-bold">Storage Fee Per Day</label>
                                        <div class="input-group">
                                            <span class="input-group-text">₱</span>
                                            <input type="number" name="storage_fee_per_day" class="form-control" value="{{ SystemSetting::get('storage_fee_per_day', 5) }}" min="0" step="0.01">
                                            <span class="input-group-text">/ day</span>
                                        </div>
                                        <div class="form-text">Charged per day after the unclaimed threshold is exceeded.</div>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label fw-bold">Free Storage Grace Period</label>
                                        <div class="input-group">
                                            <input type="number" name="storage_grace_period_days" class="form-control text-center" value="{{ SystemSetting::get('storage_grace_period_days', 3) }}" min="0">
                                            <span class="input-group-text">Days</span>
                                        </div>
                                        <div class="form-text">Number of free days before storage fees start.</div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        {{-- Tax Settings --}}
                        <div class="card border-0 shadow-sm rounded-4 mb-4">
                            <div class="card-header bg-white py-3 border-bottom">
                                <h5 class="fw-bold mb-0"><i class="bi bi-percent me-2 text-info"></i>Tax Settings</h5>
                            </div>
                            <div class="card-body p-4">
                                <div class="row g-3 align-items-end">
                                    <div class="col-md-4">
                                        <label class="form-label fw-bold">VAT Rate</label>
                                        <div class="input-group">
                                            <input type="number" name="vat_rate" class="form-control text-center" value="{{ SystemSetting::get('vat_rate', 0) }}" min="0" max="100" step="0.01">
                                            <span class="input-group-text">%</span>
                                        </div>
                                        <div class="form-text">Set to 0 if VAT-exempt.</div>
                                    </div>
                                    <div class="col-md-8">
                                        <label class="form-label fw-bold">VAT Display Mode</label>
                                        <select name="vat_inclusive" class="form-select">
                                            <option value="1" {{ SystemSetting::get('vat_inclusive', 1) ? 'selected' : '' }}>Inclusive — Prices already include VAT</option>
                                            <option value="0" {{ !SystemSetting::get('vat_inclusive', 1) ? 'selected' : '' }}>Exclusive — Add VAT on top of prices</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- BUSINESS HOURS --}}
                    <div class="tab-pane fade" id="hours">
                        <div class="card border-0 shadow-sm rounded-4 mb-4">
                            <div class="card-header bg-white py-3 border-bottom">
                                <h5 class="fw-bold mb-0"><i class="bi bi-clock me-2 text-primary"></i>Default Operating Hours</h5>
                                <p class="text-muted x-small mb-0 mt-1">Global defaults. Individual branches can override these in the Branches section.</p>
                            </div>
                            <div class="card-body p-4">
                                @php
                                    $days = ['monday','tuesday','wednesday','thursday','friday','saturday','sunday'];
                                    $defaultOpen  = ['monday'=>'07:00','tuesday'=>'07:00','wednesday'=>'07:00','thursday'=>'07:00','friday'=>'07:00','saturday'=>'08:00','sunday'=>'08:00'];
                                    $defaultClose = ['monday'=>'20:00','tuesday'=>'20:00','wednesday'=>'20:00','thursday'=>'20:00','friday'=>'20:00','saturday'=>'18:00','sunday'=>'14:00'];
                                    $defaultClosed = ['sunday'];
                                @endphp
                                <div class="table-responsive">
                                    <table class="table align-middle mb-0">
                                        <thead class="x-small text-uppercase text-muted bg-light">
                                            <tr>
                                                <th class="ps-3" style="width:130px">Day</th>
                                                <th>Opens At</th>
                                                <th>Closes At</th>
                                                <th class="text-center" style="width:100px">Open</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($days as $day)
                                            @php $isOpen = SystemSetting::get("hours_{$day}_open", !in_array($day, $defaultClosed)); @endphp
                                            <tr class="{{ !$isOpen ? 'opacity-50' : '' }}">
                                                <td class="ps-3 fw-bold text-capitalize">{{ $day }}</td>
                                                <td>
                                                    <input type="time" name="hours_{{ $day }}_start" class="form-control form-control-sm" style="width:130px"
                                                        value="{{ SystemSetting::get("hours_{$day}_start", $defaultOpen[$day]) }}"
                                                        {{ !$isOpen ? 'disabled' : '' }}>
                                                </td>
                                                <td>
                                                    <input type="time" name="hours_{{ $day }}_end" class="form-control form-control-sm" style="width:130px"
                                                        value="{{ SystemSetting::get("hours_{$day}_end", $defaultClose[$day]) }}"
                                                        {{ !$isOpen ? 'disabled' : '' }}>
                                                </td>
                                                <td class="text-center">
                                                    <div class="form-check form-switch d-flex justify-content-center mb-0">
                                                        <input class="form-check-input hours-toggle" type="checkbox"
                                                            name="hours_{{ $day }}_open"
                                                            data-day="{{ $day }}"
                                                            style="width:2.5em;height:1.3em;"
                                                            {{ $isOpen ? 'checked' : '' }}>
                                                    </div>
                                                </td>
                                            </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- PICKUP & DELIVERY --}}
                    <div class="tab-pane fade" id="pickup">
                        {{-- Service Availability --}}
                        <div class="card border-0 shadow-sm rounded-4 mb-4">
                            <div class="card-header bg-white py-3 border-bottom">
                                <h5 class="fw-bold mb-0"><i class="bi bi-toggles me-2 text-primary"></i>Service Availability</h5>
                            </div>
                            <div class="card-body p-4">
                                <div class="d-flex align-items-center justify-content-between p-3 bg-light rounded-3 mb-3">
                                    <div>
                                        <div class="fw-bold">Enable Pickup Service</div>
                                        <div class="text-muted x-small">Allow customers to request laundry pickup via the mobile app.</div>
                                    </div>
                                    <div class="form-check form-switch mb-0">
                                        <input class="form-check-input" type="checkbox" name="enable_pickup" style="width:3em;height:1.5em;"
                                            {{ SystemSetting::get('enable_pickup', true) ? 'checked' : '' }}>
                                    </div>
                                </div>
                                <div class="d-flex align-items-center justify-content-between p-3 bg-light rounded-3">
                                    <div>
                                        <div class="fw-bold">Enable Delivery Service</div>
                                        <div class="text-muted x-small">Allow staff to deliver completed laundry to customer addresses.</div>
                                    </div>
                                    <div class="form-check form-switch mb-0">
                                        <input class="form-check-input" type="checkbox" name="enable_delivery" style="width:3em;height:1.5em;"
                                            {{ SystemSetting::get('enable_delivery', true) ? 'checked' : '' }}>
                                    </div>
                                </div>
                            </div>
                        </div>

                        {{-- Default Fees --}}
                        <div class="card border-0 shadow-sm rounded-4 mb-4">
                            <div class="card-header bg-white py-3 border-bottom">
                                <h5 class="fw-bold mb-0"><i class="bi bi-cash-stack me-2 text-success"></i>Default Fees</h5>
                                <p class="text-muted x-small mb-0 mt-1">Used as defaults when creating a new pickup or delivery order.</p>
                            </div>
                            <div class="card-body p-4">
                                <div class="row g-3">
                                    <div class="col-md-4">
                                        <label class="form-label fw-bold">Default Pickup Fee</label>
                                        <div class="input-group">
                                            <span class="input-group-text">₱</span>
                                            <input type="number" name="default_pickup_fee" class="form-control" value="{{ SystemSetting::get('default_pickup_fee', 50) }}" min="0" step="0.01">
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label fw-bold">Default Delivery Fee</label>
                                        <div class="input-group">
                                            <span class="input-group-text">₱</span>
                                            <input type="number" name="default_delivery_fee" class="form-control" value="{{ SystemSetting::get('default_delivery_fee', 50) }}" min="0" step="0.01">
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label fw-bold">Max Service Radius</label>
                                        <div class="input-group">
                                            <input type="number" name="max_service_radius_km" class="form-control text-center" value="{{ SystemSetting::get('max_service_radius_km', 10) }}" min="1">
                                            <span class="input-group-text">km</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        {{-- Schedule Rules --}}
                        <div class="card border-0 shadow-sm rounded-4 mb-4">
                            <div class="card-header bg-white py-3 border-bottom">
                                <h5 class="fw-bold mb-0"><i class="bi bi-calendar-check me-2 text-info"></i>Booking Schedule Rules</h5>
                            </div>
                            <div class="card-body p-4">
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label class="form-label fw-bold">Minimum Advance Booking</label>
                                        <div class="input-group">
                                            <input type="number" name="pickup_advance_days_min" class="form-control text-center" value="{{ SystemSetting::get('pickup_advance_days_min', 1) }}" min="0">
                                            <span class="input-group-text">Day(s) ahead</span>
                                        </div>
                                        <div class="form-text">Minimum days in advance a customer must book a pickup.</div>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label fw-bold">Maximum Advance Booking</label>
                                        <div class="input-group">
                                            <input type="number" name="pickup_advance_days_max" class="form-control text-center" value="{{ SystemSetting::get('pickup_advance_days_max', 7) }}" min="1">
                                            <span class="input-group-text">Day(s) ahead</span>
                                        </div>
                                        <div class="form-text">How far in advance a customer can schedule a pickup.</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- RECEIPT & INVOICE --}}
                    <div class="tab-pane fade" id="receipt">
                        <div class="card border-0 shadow-sm rounded-4 mb-4">
                            <div class="card-header bg-white py-3 border-bottom">
                                <h5 class="fw-bold mb-0"><i class="bi bi-receipt me-2 text-secondary"></i>Receipt Content</h5>
                                <p class="text-muted x-small mb-0 mt-1">These values appear on every printed receipt.</p>
                            </div>
                            <div class="card-body p-4">
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label class="form-label fw-bold">Tracking Number Prefix</label>
                                        <input type="text" name="tracking_prefix" class="form-control font-monospace" value="{{ SystemSetting::get('tracking_prefix', 'WB') }}" maxlength="5">
                                        <div class="form-text">e.g. <code>WB</code> → tracking numbers like <strong>WB-20240101-ABCD</strong></div>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label fw-bold">Business Email (for receipts)</label>
                                        <input type="email" name="business_email" class="form-control" value="{{ SystemSetting::get('business_email') }}" placeholder="washbox@email.com">
                                    </div>
                                    <div class="col-12">
                                        <label class="form-label fw-bold">Receipt Header Message</label>
                                        <textarea name="receipt_header" class="form-control" rows="2" placeholder="Thank you for choosing WashBox Laundry Services!">{{ SystemSetting::get('receipt_header', 'Thank you for choosing WashBox Laundry Services!') }}</textarea>
                                        <div class="form-text">Shown at the top of every receipt.</div>
                                    </div>
                                    <div class="col-12">
                                        <label class="form-label fw-bold">Receipt Footer Message</label>
                                        <textarea name="receipt_footer" class="form-control" rows="2" placeholder="Please keep this receipt. Claims without receipt may not be honored.">{{ SystemSetting::get('receipt_footer', 'Please keep this receipt. Claims without receipt may not be honored.') }}</textarea>
                                        <div class="form-text">Shown at the bottom — good for your claim policy reminder.</div>
                                    </div>
                                    <div class="col-12">
                                        <label class="form-label fw-bold">Claim Reminder Text</label>
                                        <input type="text" name="receipt_claim_reminder" class="form-control" value="{{ SystemSetting::get('receipt_claim_reminder', 'Please claim your laundry within 30 days.') }}">
                                        <div class="form-text">Highlighted reminder line printed on each receipt.</div>
                                    </div>
                                </div>

                                <hr class="my-4">
                                <h6 class="fw-bold mb-3">Print Options</h6>

                                <div class="d-flex align-items-center justify-content-between p-3 bg-light rounded-3 mb-3">
                                    <div>
                                        <div class="fw-bold">Show Branch Info on Receipt</div>
                                        <div class="text-muted x-small">Prints branch address and contact number.</div>
                                    </div>
                                    <div class="form-check form-switch mb-0">
                                        <input class="form-check-input" type="checkbox" name="receipt_show_branch" style="width:3em;height:1.5em;"
                                            {{ SystemSetting::get('receipt_show_branch', true) ? 'checked' : '' }}>
                                    </div>
                                </div>
                                <div class="d-flex align-items-center justify-content-between p-3 bg-light rounded-3">
                                    <div>
                                        <div class="fw-bold">Show Staff Name on Receipt</div>
                                        <div class="text-muted x-small">Prints the name of the staff who accepted the order.</div>
                                    </div>
                                    <div class="form-check form-switch mb-0">
                                        <input class="form-check-input" type="checkbox" name="receipt_show_staff" style="width:3em;height:1.5em;"
                                            {{ SystemSetting::get('receipt_show_staff', false) ? 'checked' : '' }}>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- UNCLAIMED RULES --}}
                    <div class="tab-pane fade" id="unclaimed">
                        <div class="card border-0 shadow-sm rounded-4 mb-4">
                            <div class="card-header bg-white py-3 border-bottom">
                                <h5 class="fw-bold mb-0 text-danger"><i class="bi bi-shield-exclamation me-2"></i>Inventory Retention</h5>
                            </div>
                            <div class="card-body p-4">
                                <div class="row align-items-center mb-4">
                                    <div class="col-md-8">
                                        <h6 class="fw-bold mb-1">Auto-Disposal Threshold</h6>
                                        <p class="text-muted small mb-0">How many days should an order remain unclaimed before marking for disposal?</p>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="input-group">
                                            <input type="number" name="disposal_threshold_days" class="form-control text-center fw-bold" value="{{ SystemSetting::get('disposal_threshold_days', 30) }}">
                                            <span class="input-group-text">Days</span>
                                        </div>
                                    </div>
                                </div>
                                <div class="form-check form-switch p-3 bg-light rounded-3">
                                    <input class="form-check-input ms-0 me-3" type="checkbox" name="enable_unclaimed_notifications" style="width: 3em; height: 1.5em;" {{ SystemSetting::get('enable_unclaimed_notifications') ? 'checked' : '' }}>
                                    <label class="form-check-label fw-bold">Automated Retention Alerts</label>
                                    <div class="text-muted x-small ms-5">Send daily reminders to customers with overdue laundry.</div>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- NOTIFICATIONS --}}
                    <div class="tab-pane fade" id="notifications">
                        <div class="card border-0 shadow-sm rounded-4 mb-4">
                            <div class="card-header bg-white py-3 border-bottom">
                                <h5 class="fw-bold mb-0 text-primary">Cloud Messaging (FCM)</h5>
                            </div>
                            <div class="card-body p-4 text-center py-5">
                                <i class="bi bi-cloud-check text-primary display-4 mb-3"></i>
                                <div class="row g-3 text-start mt-2">
                                    <div class="col-12">
                                        <label class="form-label fw-bold small">Server Key</label>
                                        <input type="password" name="fcm_server_key" class="form-control font-monospace" value="{{ SystemSetting::get('fcm_server_key') }}">
                                    </div>
                                    <div class="col-12">
                                        <label class="form-label fw-bold small">Sender ID</label>
                                        <input type="text" name="fcm_sender_id" class="form-control font-monospace" value="{{ SystemSetting::get('fcm_sender_id') }}">
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- SYSTEM STATUS --}}
                    {{-- $health is passed directly from SettingsController@index() --}}
                    <div class="tab-pane fade" id="status">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <div class="card border-0 shadow-sm rounded-4 p-4 text-center">
                                    <div class="display-6 mb-2 {{ $health['database'] ? 'text-success' : 'text-danger' }}">
                                        <i class="bi bi-database-fill-check"></i>
                                    </div>
                                    <h6 class="fw-bold mb-1">Database</h6>
                                    <span class="badge {{ $health['database'] ? 'bg-success' : 'bg-danger' }} rounded-pill">
                                        {{ $health['database'] ? 'Healthy & Connected' : 'Connection Error' }}
                                    </span>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="card border-0 shadow-sm rounded-4 p-4 text-center">
                                    <div class="display-6 mb-2 {{ $health['fcm'] ? 'text-success' : 'text-warning' }}">
                                        <i class="bi bi-broadcast-pin"></i>
                                    </div>
                                    <h6 class="fw-bold mb-1">FCM Status</h6>
                                    <span class="badge {{ $health['fcm'] ? 'bg-success' : 'bg-warning' }} rounded-pill px-3">
                                        {{ $health['fcm'] ? 'Push Engine Ready' : 'Service Account Missing' }}
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- BACKUP --}}
                    <div class="tab-pane fade" id="backup">
                        <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
                            <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center">
                                <h5 class="fw-bold mb-0">Available Snapshots</h5>
                                <button type="button" class="btn btn-primary btn-sm px-3" onclick="generateBackup()">
                                    <i class="bi bi-plus-lg me-1"></i> New Backup
                                </button>
                            </div>
                            <div class="table-responsive">
                                <table class="table table-hover align-middle mb-0">
                                    <thead class="bg-light x-small text-uppercase">
                                        <tr><th class="ps-4">File ID</th><th>Size</th><th>Created</th><th class="text-end pe-4">Action</th></tr>
                                    </thead>
                                    <tbody>
                                        @php
                                            $backupPath = storage_path('app/backups');
                                            $backupFiles = File::exists($backupPath) ? collect(File::files($backupPath))->sortByDesc(fn($f) => $f->getMTime()) : collect();
                                        @endphp
                                        @forelse($backupFiles as $file)
                                        <tr>
                                            <td class="ps-4 fw-bold font-monospace x-small">{{ $file->getFilename() }}</td>
                                            <td><span class="badge bg-light text-dark">{{ number_format($file->getSize() / 1024, 1) }} KB</span></td>
                                            <td class="text-muted small">{{ date('M j, Y H:i', $file->getMTime()) }}</td>
                                            <td class="text-end pe-4">
                                                <a href="{{ route('admin.settings.download-backup', $file->getFilename()) }}" class="btn btn-light btn-sm"><i class="bi bi-download"></i></a>
                                            </td>
                                        </tr>
                                        @empty
                                        <tr><td colspan="4" class="text-center py-5 text-muted small">No backup history available.</td></tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="sticky-bottom bg-white py-3 mt-4 border-top">
                    <div class="d-flex justify-content-end gap-2">
                        <button type="reset" class="btn btn-light px-4 rounded-3">Discard Changes</button>
                        <button type="submit" class="btn btn-primary px-5 rounded-3 shadow" style="background: #3D3B6B;">
                            Save Settings
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<style>
    .x-small { font-size: 0.75rem; }
    .border-dashed { border-style: dashed !important; }
    .nav-pills .nav-link { border-radius: 12px; color: #6c757d; transition: all 0.2s; }
    .nav-pills .nav-link.active { background-color: #f0f1ff; color: #3D3B6B; }
    .nav-pills .nav-link:hover:not(.active) { background-color: #f8f9fa; color: #333; }
    .tab-pane { animation: fadeIn 0.3s ease; }
    @keyframes fadeIn { from { opacity: 0; transform: translateY(5px); } to { opacity: 1; transform: translateY(0); } }
</style>

<script>
function generateBackup() {
    fetch('{{ route("admin.settings.backup") }}', {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': '{{ csrf_token() }}',
            'Content-Type': 'application/json',
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert(data.message);
            location.reload();
        } else {
            alert('Error: ' + data.message);
        }
    })
    .catch(error => {
        alert('Error creating backup');
        console.error(error);
    });
}

// Business hours toggle — enable/disable time inputs when day is toggled on/off
document.querySelectorAll('.hours-toggle').forEach(toggle => {
    toggle.addEventListener('change', function () {
        const day = this.dataset.day;
        const row = this.closest('tr');
        const inputs = row.querySelectorAll('input[type="time"]');
        inputs.forEach(input => input.disabled = !this.checked);
        row.classList.toggle('opacity-50', !this.checked);
    });
});
</script>
@endsection
