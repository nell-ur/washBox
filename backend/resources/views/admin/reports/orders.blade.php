@extends('admin.layouts.app')

@section('title', 'Laundries Report')

@section('content')
<div class="container-fluid px-4 py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="mb-0 fw-bold">Laundries Report</h2>
            <p class="text-muted mb-0">Detailed laundry history</p>
        </div>
        <a href="{{ route('admin.reports.index') }}" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left me-2"></i>Back to Reports
        </a>
    </div>

    {{-- Summary Cards --}}
    <div class="row g-3 mb-4">
        <div class="col-md-3">
            <div class="card">
                <div class="card-body">
                    <small class="text-muted">Total Laundries</small>
                    <h4 class="mb-0">{{ number_format($summary['total_laundries']) }}</h4>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card">
                <div class="card-body">
                    <small class="text-muted">Completed</small>
                    <h4 class="mb-0 text-success">{{ number_format($summary['completed_laundries']) }}</h4>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card">
                <div class="card-body">
                    <small class="text-muted">Pending</small>
                    <h4 class="mb-0 text-warning">{{ number_format($summary['pending_laundries']) }}</h4>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card">
                <div class="card-body">
                    <small class="text-muted">Total Revenue</small>
                    <h4 class="mb-0 text-primary">₱{{ number_format($summary['total_revenue'], 2) }}</h4>
                </div>
            </div>
        </div>
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

    {{-- Laundries Table --}}
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0">Laundries List</h5>
            <form method="POST" action="{{ route('admin.reports.export') }}">
                @csrf
                <input type="hidden" name="type" value="laundries">
                <input type="hidden" name="start_date" value="{{ $startDate->format('Y-m-d') }}">
                <input type="hidden" name="end_date" value="{{ $endDate->format('Y-m-d') }}">
                <button type="submit" class="btn btn-sm btn-success">
                    <i class="bi bi-download me-2"></i>Export CSV
                </button>
            </form>
        </div>
        <div class="card-body">
            @if($laundries->count() > 0)
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Laundry #</th>
                                <th>Customer</th>
                                <th>Branch</th>
                                <th>Status</th>
                                <th>Payment</th>
                                <th class="text-end">Amount</th>
                                <th>Date</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($laundries as $laundry)
                                <tr>
                                    <td>
                                        <a href="{{ route('admin.laundries.show', $laundry->id) }}">
                                            {{ $laundry->tracking_number }}
                                        </a>
                                    </td>
                                    <td>{{ $order->customer->name ?? 'N/A' }}</td>
                                    <td>{{ $order->branch->name ?? 'N/A' }}</td>
                                    <td>
                                        <span class="badge bg-{{ $order->status === 'completed' ? 'success' : 'warning' }}">
                                            {{ ucfirst($order->status) }}
                                        </span>
                                    </td>
                                    <td>
                                        <span class="badge bg-{{ $order->payment_status === 'paid' ? 'success' : 'danger' }}">
                                            {{ ucfirst($order->payment_status) }}
                                        </span>
                                    </td>
                                    <td class="text-end">₱{{ number_format($order->total_amount, 2) }}</td>
                                    <td>{{ $order->created_at->format('M d, Y') }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="mt-3">
                    {{ $laundries->links() }}
                </div>
            @else
                <div class="text-center py-5">
                    <i class="bi bi-basket text-muted" style="font-size: 3rem;"></i>
                    <p class="text-muted mt-3">No laundries found for this period</p>
                </div>
            @endif
        </div>
    </div>
</div>
@endsection
