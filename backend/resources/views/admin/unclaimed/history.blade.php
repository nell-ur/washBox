@extends('admin.layouts.app')

@section('title', 'Disposal History')

@section('content')
<div class="container-fluid px-4 py-4">
    {{-- Header --}}
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <a href="{{ route('admin.unclaimed.index') }}" class="text-decoration-none text-muted small">
                <i class="bi bi-arrow-left me-1"></i> Back to Unclaimed Laundry
            </a>
            <h3 class="fw-bold mt-2 mb-1">
                <i class="bi bi-clock-history me-2"></i>Disposal History
            </h3>
            <p class="text-muted small mb-0">Record of disposed unclaimed laundry items</p>
        </div>
    </div>

    {{-- Stats --}}
    <div class="row g-3 mb-4">
        <div class="col-md-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body text-center py-4">
                    <div class="rounded-circle bg-secondary bg-opacity-10 d-inline-flex align-items-center justify-content-center mb-2" style="width: 48px; height: 48px;">
                        <i class="bi bi-archive fs-4 text-secondary"></i>
                    </div>
                    <h3 class="fw-bold mb-0">{{ $totalDisposed }}</h3>
                    <small class="text-muted">Total Disposed</small>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-0 shadow-sm h-100 border-top border-4 border-danger">
                <div class="card-body text-center py-4">
                    <div class="rounded-circle bg-danger bg-opacity-10 d-inline-flex align-items-center justify-content-center mb-2" style="width: 48px; height: 48px;">
                        <i class="bi bi-currency-dollar fs-4 text-danger"></i>
                    </div>
                    <h3 class="fw-bold text-danger mb-0">₱{{ number_format($totalLoss, 0) }}</h3>
                    <small class="text-muted">Total Revenue Lost</small>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body text-center py-4">
                    <div class="rounded-circle bg-warning bg-opacity-10 d-inline-flex align-items-center justify-content-center mb-2" style="width: 48px; height: 48px;">
                        <i class="bi bi-calendar-x fs-4 text-warning"></i>
                    </div>
                    <h3 class="fw-bold text-warning mb-0">₱{{ number_format($thisMonthLoss, 0) }}</h3>
                    <small class="text-muted">This Month's Loss</small>
                </div>
            </div>
        </div>
    </div>

    {{-- Filters --}}
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body py-3">
            <form method="GET" class="row g-2 align-items-end">
                <div class="col-md-3">
                    <label class="form-label small">Branch</label>
                    <select name="branch_id" class="form-select">
                        <option value="">All Branches</option>
                        @foreach($branches as $branch)
                            <option value="{{ $branch->id }}" {{ request('branch_id') == $branch->id ? 'selected' : '' }}>
                                {{ $branch->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label small">From Date</label>
                    <input type="date" name="from_date" class="form-control" value="{{ request('from_date') }}">
                </div>
                <div class="col-md-3">
                    <label class="form-label small">To Date</label>
                    <input type="date" name="to_date" class="form-control" value="{{ request('to_date') }}">
                </div>
                <div class="col-md-3 d-flex gap-2">
                    <button type="submit" class="btn btn-primary flex-grow-1">
                        <i class="bi bi-search me-1"></i> Filter
                    </button>
                    <a href="{{ route('admin.unclaimed.history') }}" class="btn btn-outline-secondary">
                        <i class="bi bi-x-lg"></i>
                    </a>
                </div>
            </form>
        </div>
    </div>

    {{-- Table --}}
    <div class="card border-0 shadow-sm">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="bg-light">
                        <tr>
                            <th class="ps-4">Order #</th>
                            <th>Customer</th>
                            <th>Branch</th>
                            <th class="text-end">Amount Lost</th>
                            <th class="text-center">Days Unclaimed</th>
                            <th>Disposed Date</th>
                            <th>Disposed By</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($history as $item)
                            <tr>
                                <td class="ps-4">
                                    <span class="fw-semibold">{{ $item->order->tracking_number ?? 'N/A' }}</span>
                                </td>
                                <td>
                                    <div class="fw-semibold">{{ $item->order->customer->name ?? $item->customer->name ?? 'N/A' }}</div>
                                    <div class="small text-muted">{{ $item->order->customer->phone ?? $item->customer->phone ?? '' }}</div>
                                </td>
                                <td>
                                    <span class="badge bg-secondary bg-opacity-10 text-secondary">
                                        {{ $item->branch->name ?? 'N/A' }}
                                    </span>
                                </td>
                                <td class="text-end fw-bold text-danger">
                                    ₱{{ number_format($item->order->total_amount ?? 0, 2) }}
                                </td>
                                <td class="text-center">
                                    <span class="badge bg-danger">{{ $item->days_unclaimed }} days</span>
                                </td>
                                <td>
                                    <div>{{ $item->disposed_at?->format('M d, Y') ?? 'N/A' }}</div>
                                    <div class="small text-muted">{{ $item->disposed_at?->format('h:i A') ?? '' }}</div>
                                </td>
                                <td>
                                    {{ $item->disposedBy->name ?? 'System' }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center py-5">
                                    <i class="bi bi-archive fs-1 text-muted d-block mb-2"></i>
                                    <p class="text-muted mb-0">No disposed items found</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        @if($history->hasPages())
            <div class="card-footer bg-white">
                {{ $history->links() }}
            </div>
        @endif
    </div>

    {{-- Loss by Branch --}}
    @if($lossByBranch->isNotEmpty())
        <div class="card border-0 shadow-sm mt-4">
            <div class="card-header bg-white py-3">
                <h6 class="mb-0"><i class="bi bi-building me-2"></i>Loss by Branch</h6>
            </div>
            <div class="card-body">
                <div class="row">
                    @foreach($branches as $branch)
                        @php
                            $branchData = $lossByBranch->get($branch->id, ['count' => 0, 'value' => 0]);
                        @endphp
                        <div class="col-md-4 mb-3">
                            <div class="border rounded p-3">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <div class="fw-bold">{{ $branch->name }}</div>
                                        <div class="small text-muted">{{ $branchData['count'] ?? 0 }} items</div>
                                    </div>
                                    <div class="text-end">
                                        <div class="fw-bold text-danger">₱{{ number_format($branchData['value'] ?? 0, 0) }}</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    @endif
</div>
@endsection
