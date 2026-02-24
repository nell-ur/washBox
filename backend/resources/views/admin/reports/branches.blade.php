@extends('admin.layouts.app')

@section('title', 'Branches Report')

@section('content')
<div class="container-fluid px-4 py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="mb-0 fw-bold">Branches Report</h2>
            <p class="text-muted mb-0">Branch performance comparison</p>
        </div>
        <a href="{{ route('admin.reports.index') }}" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left me-2"></i>Back to Reports
        </a>
    </div>

    {{-- Date Filter --}}
    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" class="row g-3">
                <div class="col-md-5">
                    <label class="form-label">Start Date</label>
                    <input type="date" name="start_date" class="form-control" value="{{ $startDate->format('Y-m-d') }}">
                </div>
                <div class="col-md-5">
                    <label class="form-label">End Date</label>
                    <input type="date" name="end_date" class="form-control" value="{{ $endDate->format('Y-m-d') }}">
                </div>
                <div class="col-md-2">
                    <label class="form-label">&nbsp;</label>
                    <button type="submit" class="btn btn-primary w-100">Filter</button>
                </div>
            </form>
        </div>
    </div>

    {{-- Branches Performance --}}
    <div class="row g-4">
        @foreach($branches as $branch)
            <div class="col-md-6 col-lg-4">
                <div class="card h-100">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-start mb-3">
                            <div>
                                <h5 class="mb-1">{{ $branch['name'] }}</h5>
                                <span class="badge bg-primary">{{ $branch['code'] }}</span>
                            </div>
                            <i class="bi bi-shop text-muted" style="font-size: 2rem;"></i>
                        </div>

                        <div class="mb-3">
                            <small class="text-muted">Total Laundries</small>
                            <h4 class="mb-0">{{ number_format($branch['laundries_count']) }}</h4>
                        </div>

                        <div class="mb-3">
                            <small class="text-muted">Total Revenue</small>
                            <h4 class="mb-0 text-success">₱{{ number_format($branch['revenue'], 2) }}</h4>
                        </div>

                        <div>
                            <small class="text-muted">Average Order Value</small>
                            <h5 class="mb-0">₱{{ number_format($branch['avg_laundry_value'], 2) }}</h5>
                        </div>
                    </div>
                </div>
            </div>
        @endforeach
    </div>
</div>
@endsection
