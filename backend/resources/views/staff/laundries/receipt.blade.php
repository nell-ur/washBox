<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Receipt - {{ $laundry->tracking_number }}</title>
    <style>
        /* Reset & Base */
        @page { size: 80mm 200mm; margin: 0; }
        body { font-family: 'Courier New'; width: 70mm; margin: 0 auto; padding: 5mm; font-size: 12px; }

        /* Layout */
        .t { text-align: center; }
        .d { border-top: 1px dashed #000; margin: 8px 0; }
        .sd { border-top: 2px solid #000; margin: 10px 0; }
        .fb { display: flex; justify-content: space-between; margin: 2px 0; }

        /* Elements */
        .bc { margin: 8px 0; font-size: 14px; font-weight: bold; letter-spacing: 2px; }
        .total { font-size: 16px; font-weight: bold; margin: 12px 0; }
        .status { display: inline-block; padding: 4px 12px; background: #000; color: #fff; font-weight: bold; }
        .note { font-size: 10px; background: #f9f9f9; padding: 5px; margin: 5px 0; }
        .small { font-size: 10px; }
        .bold { font-weight: bold; }

        /* Print */
        @media print { .no-print { display: none !important; } body { padding: 0; margin: 0; } }
        .print-btn { background: #3D3B6B; color: white; padding: 10px; border: none; border-radius: 6px; cursor: pointer; }
    </style>
</head>
<body onload="window.print()">

    <div class="no-print" style="background:#fef3c7;padding:15px;text-align:center;margin-bottom:20px;">
        <button onclick="window.print()" class="print-btn">🖨️ Print Receipt</button>
        <p class="small" style="color:#92400e;margin:10px 0 0;"><strong>Note:</strong> 80mm thermal paper</p>
    </div>

    <div>
        <div class="t">
            <h2 style="margin:3px 0;font-size:18px;">WASHBOX</h2>
            <h3 style="margin:3px 0;font-size:13px;">Laundry Services</h3>
            <p class="small"><strong>{{ $laundry->branch->name }}</strong><br>{{ Str::limit($laundry->branch->address,35) }}</p>
        </div>

        <div class="d"></div>
        <div class="t bc">* {{ $laundry->tracking_number }} *</div>
        <div class="d"></div>

        <div class="fb"><span>Date:</span><span>{{ $laundry->created_at->format('M d, h:i A') }}</span></div>
        <div class="fb"><span>Customer:</span><span class="bold">{{ $laundry->customer->name }}</span></div>
        @if($laundry->customer->phone)<div class="fb"><span>Phone:</span><span>{{ $laundry->customer->phone }}</span></div>@endif

        <div class="d"></div>
        <div class="fb bold"><span>DESCRIPTION</span><span>AMOUNT</span></div>
        <div class="fb"><span>{{ $laundry->service->name }}</span><span>₱{{ number_format($laundry->subtotal,2) }}</span></div>
        @if($laundry->service)
        @php
            $isSpecial = $laundry->service->service_type === 'special_item';
            $rLoads    = $laundry->number_of_loads ?? 1;
            $rUnit     = $isSpecial ? 'piece' : 'load';
            $rUnits    = $isSpecial ? 'pieces' : 'loads';
        @endphp
        <p class="small t">
            {{ $rLoads }} {{ $rLoads > 1 ? $rUnits : $rUnit }}
            × ₱{{ number_format($laundry->service->price_per_load ?? 0, 2) }}/{{ $rUnit }}
            @if($laundry->weight && !$isSpecial)
                · {{ number_format($laundry->weight, 2) }} kg
            @endif
        </p>
        @endif

        @if($laundry->pickup_fee > 0 || $laundry->delivery_fee > 0)
            <div class="d"></div>
            @if($laundry->pickup_fee > 0)<div class="fb small"><span>Pickup Fee:</span><span>₱{{ number_format($laundry->pickup_fee,2) }}</span></div>@endif
            @if($laundry->delivery_fee > 0)<div class="fb small"><span>Delivery Fee:</span><span>₱{{ number_format($laundry->delivery_fee,2) }}</span></div>@endif
        @endif

        @if($laundry->discount_amount > 0)<div class="fb"><span>Discount:</span><span>-₱{{ number_format($laundry->discount_amount,2) }}</span></div>@endif

        <div class="sd"></div>
        <div class="fb total"><span>TOTAL:</span><span>₱{{ number_format($laundry->total_amount,2) }}</span></div>

        <div class="t"><div class="status">{{ strtoupper($laundry->status) }}</div></div>

        @if($laundry->pickupRequest)
            <div class="d"></div>
            <div class="note">
                <div class="bold">Pickup Service</div>
                <div>Type: {{ $laundry->pickupRequest->service_type_label }}</div>
                @if($laundry->pickupRequest->pickup_address)<div>Address: {{ Str::limit($laundry->pickupRequest->pickup_address,35) }}</div>@endif
            </div>
        @endif

        @if($laundry->pickup_date || $laundry->delivery_date)
            <div class="d"></div>
            <div class="small">
                @if($laundry->pickup_date)<div class="fb"><span>Pickup:</span><span>{{ \Carbon\Carbon::parse($laundry->pickup_date)->format('M d') }}</span></div>@endif
                @if($laundry->delivery_date)<div class="fb"><span>Delivery:</span><span>{{ \Carbon\Carbon::parse($laundry->delivery_date)->format('M d') }}</span></div>@endif
            </div>
        @endif

        @if($laundry->notes)
            <div class="d"></div>
            <div class="note"><strong>Notes:</strong><br>{{ $laundry->notes }}</div>
        @endif

        <div class="sd"></div>
        <div class="t">
            <p class="bold">PRESENT THIS TICKET</p>
            @if($laundry->staff)<p class="small">Served by: {{ $laundry->staff->name }}</p>@endif
            <p class="small">Receipt #: {{ $laundry->id }}</p>
            <p class="small">Printed: {{ now()->format('M d, h:i A') }}</p>
            <div class="d"></div>
            <p class="bold">THANK YOU!</p>
            <p class="small">{{ $laundry->branch->phone ?? 'Contact branch' }}</p>
        </div>
    </div>

</body>
</html>
