@extends('admin.layouts.app')
@section('page-title', 'Promotions Management')

@section('content')
<div class="container-fluid px-4 py-4">
    <h2 class="fw-bold mb-4">Marketing Analytics by Branch</h2>

    <div class="row">
        <div class="col-xl-8 mb-4">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white py-3">
                    <h5 class="fw-bold mb-0">Revenue vs. Discounts</h5>
                </div>
                <div class="card-body">
                    <canvas id="branchChart" height="300"></canvas>
                </div>
            </div>
        </div>

        <div class="col-xl-4">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white py-3">
                    <h5 class="fw-bold mb-0">Performance Stats</h5>
                </div>
                <div class="card-body p-0">
                    <ul class="list-group list-group-flush">
                        @foreach($branchStats as $stat)
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            <div>
                                <div class="fw-bold">{{ $stat->branch_name }}</div>
                                <small class="text-muted">{{ $stat->usage_count }} promos used</small>
                            </div>
                            <div class="text-end">
                                <div class="text-success fw-bold">₱{{ number_format($stat->total_revenue, 2) }}</div>
                                <div class="text-danger small">-₱{{ number_format($stat->total_discounts, 2) }}</div>
                            </div>
                        </li>
                        @endforeach
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    const ctx = document.getElementById('branchChart').getContext('2d');
    const branchStats = @json($branchStats);

    new Chart(ctx, {
        type: 'bar',
        data: {
            labels: branchStats.map(s => s.branch_name),
            datasets: [{
                label: 'Revenue (Final Amount)',
                data: branchStats.map(s => s.total_revenue),
                backgroundColor: '#198754', // Success Green
                borderRadius: 5
            }, {
                label: 'Discounts Given',
                data: branchStats.map(s => s.total_discounts),
                backgroundColor: '#dc3545', // Danger Red
                borderRadius: 5
            }]
        },
        options: {
            responsive: true,
            scales: {
                y: { beginAtZero: true, ticks: { callback: value => '₱' + value } }
            }
        }
    });
</script>
@endsection
