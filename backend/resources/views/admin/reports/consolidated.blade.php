@extends('admin.layouts.app')

@section('content')
<div class="container-fluid px-4 py-4">
    <h2 class="fw-bold mb-4">Branch Revenue Comparison</h2>

    <div class="row mb-4">
        <div class="col-lg-12">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <h6 class="text-muted text-uppercase small fw-bold mb-4">Revenue across Negros Oriental Corridor</h6>
                    <div style="height: 400px;">
                        <canvas id="branchComparisonChart"></canvas>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-body p-0">
            <table class="table mb-0">
                <thead class="bg-light">
                    <tr>
                        <th class="ps-4">Branch</th>
                        <th>Total Revenue</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($branches as $branch)
                    <tr>
                        <td class="ps-4">{{ $branch->name }}</td>
                        <td class="fw-bold">₱{{ number_format($branch->laundries_sum_total_amount ?? 0, 2) }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    const ctx = document.getElementById('branchComparisonChart').getContext('2d');
    new Chart(ctx, {
        type: 'bar',
        data: {
            labels: {!! json_encode($labels) !!},
            datasets: [{
                label: 'Total Revenue (₱)',
                data: {!! json_encode($revenueData) !!},
                backgroundColor: [
                    'rgba(61, 59, 107, 0.8)', // WashBox Primary
                    'rgba(54, 162, 235, 0.8)',
                    'rgba(255, 99, 132, 0.8)'
                ],
                borderColor: ['#3D3B6B', '#36A2EB', '#FF6384'],
                borderWidth: 1,
                borderRadius: 5
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        callback: function(value) { return '₱' + value.toLocaleString(); }
                    }
                }
            },
            plugins: {
                legend: { display: false }
            }
        }
    });
</script>
@endpush
@endsection
