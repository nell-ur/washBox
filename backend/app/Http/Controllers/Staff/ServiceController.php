<?php

namespace App\Http\Controllers\Staff;

use App\Models\Service;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Log;

class ServiceController extends Controller
{
    /**
     * Display a listing of services for staff.
     */
    public function index()
    {
        try {
            $services = Service::withCount('laundries as times_used')
                ->latest()
                ->get();

            return view('staff.services.index', compact('services'));

        } catch (\Exception $e) {
            Log::error('Staff Service Index Error: ' . $e->getMessage());

            return redirect()->back()
                ->with('error', 'Error loading services: ' . $e->getMessage());
        }
    }

    /**
     * Display the specified service.
     */
    public function show(Service $service)
    {
        try {
            // Get usage statistics
            $times_used = $service->laundries()->count();
            $total_revenue = $service->laundries()
                ->where('status', 'completed')
                ->sum('total_amount');

            // Get recent orders that used this service
            $recent_orders = $service->laundries()
                ->with(['customer', 'branch'])
                ->latest()
                ->limit(5)
                ->get();

            return view('staff.services.show', compact(
                'service',
                'times_used',
                'total_revenue',
                'recent_laundries'
            ));

        } catch (\Exception $e) {
            Log::error('Staff Service Show Error: ' . $e->getMessage());

            return redirect()->route('staff.services.index')
                ->with('error', 'Error loading service details: ' . $e->getMessage());
        }
    }
}
