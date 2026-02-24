@extends('admin.layouts.app')

@section('title', 'Pickup Requests - Customers')

@section('content')
<div class="container py-4">
    <h2 class="fw-bold mb-4">Customers Who Requested Laundry Pickup</h2>
    <table class="table table-bordered table-striped">
        <thead>
            <tr>
                <th>#</th>
                <th>Customer Name</th>
                <th>Email</th>
                <th>Phone</th>
                <th>Pickup Address</th>
                <th>Preferred Date</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
            @foreach($pickupRequests as $pickup)
                <tr>
                    <td>{{ $loop->iteration }}</td>
                    <td>{{ $pickup->customer->name ?? 'N/A' }}</td>
                    <td>{{ $pickup->customer->email ?? 'N/A' }}</td>
                    <td>{{ $pickup->customer->phone ?? 'N/A' }}</td>
                    <td>{{ $pickup->pickup_address }}</td>
                    <td>{{ $pickup->preferred_date }}</td>
                    <td>{{ ucfirst($pickup->status) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>
@endsection
