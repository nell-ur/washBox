<?php

namespace App\Http\Controllers\Admin;

use App\Models\Service;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Storage;

class ServiceController extends Controller
{
    // List all services
    public function index()
    {
        $services = Service::withCount('laundries')->latest()->get();
        return view('admin.services.index', compact('services'));
    }

    // Show form to create a new service
    public function create()
    {
        $services = Service::all();
        return view('admin.services.create', compact('services'));
    }

    // Store a new service - FIXED to return JSON for AJAX
    public function store(Request $request)
    {
        try {
            // Custom validation logic
            $rules = [
                'name'            => 'required|string|max:255',
                'description'     => 'nullable|string',
                'min_weight'      => 'nullable|numeric|min:0',
                'max_weight'      => 'nullable|numeric|min:0',
                // service_type is free text — nullable so hidden field can be empty
                'service_type'    => 'nullable|string|max:255',
                'service_type_id' => 'nullable|exists:service_types,id',
                'category'        => 'required|string|in:drop_off,self_service,addon',
                'pricing_type'    => 'required|in:per_piece,per_load',
                'turnaround_time' => 'required|integer|min:0|max:168',
                'slug'            => 'nullable|string|unique:services,slug',
                'is_active'       => 'boolean',
                'icon'            => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
            ];

            // Add conditional validation based on pricing_type
            if ($request->pricing_type === 'per_piece') {
                $rules['price_per_piece'] = 'required|numeric|min:0';
                $rules['price_per_load'] = 'nullable|numeric|min:0';
            } else {
                $rules['price_per_load'] = 'required|numeric|min:0';
                $rules['price_per_piece'] = 'nullable|numeric|min:0';
            }

            $request->validate($rules);

            // Handle icon upload
            $iconPath = null;
            if ($request->hasFile('icon')) {
                $iconPath = $request->file('icon')->store('service-icons', 'public');
            }

            // Store prices based on pricing type
            $pricePerPiece = null;
            $pricePerLoad = null;

            if ($request->pricing_type === 'per_piece') {
                $pricePerPiece = $request->price_per_piece;
            } else {
                $pricePerLoad = $request->price_per_load;
            }

            // service_type: use explicit text field, or fall back to the
            // service_type_id's name so the column is never blank
            $serviceTypeText = $request->service_type
                ?? optional(\App\Models\ServiceType::find($request->service_type_id))->name
                ?? $request->name;

            $service = Service::create([
                'name'            => $request->name,
                'description'     => $request->description,
                'price_per_piece' => $pricePerPiece,
                'price_per_load'  => $pricePerLoad,
                'min_weight'      => $request->min_weight,
                'max_weight'      => $request->max_weight,
                'service_type'    => $serviceTypeText,
                'service_type_id' => $request->service_type_id,
                'pricing_type'    => $request->pricing_type,
                'turnaround_time' => $request->turnaround_time,
                'slug'            => $request->slug ?? Str::slug($request->name),
                'icon_path'       => $iconPath,
                'is_active'       => $request->is_active ?? true,
                'category'        => $request->category,   // always from form, never overwritten
            ]);

            // Return JSON response for AJAX requests
            if ($request->wantsJson() || $request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Service created successfully!',
                    'service' => $service
                ]);
            }

            return redirect()->route('admin.services.index')->with('success', 'Service created successfully!');

        } catch (\Illuminate\Validation\ValidationException $e) {
            if ($request->wantsJson() || $request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation error',
                    'errors' => $e->errors()
                ], 422);
            }
            throw $e;
        } catch (\Exception $e) {
            if ($request->wantsJson() || $request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Error creating service: ' . $e->getMessage()
                ], 500);
            }
            throw $e;
        }
    }

    // Show a single service
    public function show(Service $service)
    {
        return view('admin.services.show', compact('service'));
    }

    // Show form to edit a service
    public function edit(Service $service)
    {
        $services = Service::all();
        return view('admin.services.edit', compact('service', 'services'));
    }

    // Update a service
    public function update(Request $request, Service $service)
    {
        // Custom validation logic
        $rules = [
            'name'            => 'required|string|max:255',
            'description'     => 'nullable|string',
            'min_weight'      => 'nullable|numeric|min:0',
            'max_weight'      => 'nullable|numeric|min:0',
            'service_type'    => 'nullable|string|max:255',
            'service_type_id' => 'nullable|exists:service_types,id',
            'category'        => 'required|string|in:drop_off,self_service,addon',
            'pricing_type'    => 'required|in:per_piece,per_load',
            'turnaround_time' => 'required|integer|min:0|max:168',
            'slug'            => 'nullable|string|unique:services,slug,' . $service->id,
            'is_active'       => 'boolean',
            'icon'            => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
        ];

        // Add conditional validation based on pricing_type
        if ($request->pricing_type === 'per_piece') {
            $rules['price_per_piece'] = 'required|numeric|min:0';
            $rules['price_per_load'] = 'nullable|numeric|min:0';
        } else {
            $rules['price_per_load'] = 'required|numeric|min:0';
            $rules['price_per_piece'] = 'nullable|numeric|min:0';
        }

        $request->validate($rules);

        // Handle icon upload
        if ($request->hasFile('icon')) {
            // Delete old icon if exists
            if ($service->icon_path) {
                Storage::disk('public')->delete($service->icon_path);
            }
            $iconPath = $request->file('icon')->store('service-icons', 'public');
            $service->icon_path = $iconPath;
        }

        // Store prices based on pricing type
        $pricePerPiece = null;
        $pricePerLoad = null;

        if ($request->pricing_type === 'per_piece') {
            $pricePerPiece = $request->price_per_piece;
        } else {
            $pricePerLoad = $request->price_per_load;
        }

        $serviceTypeText = $request->service_type
            ?? optional(\App\Models\ServiceType::find($request->service_type_id))->name
            ?? $service->service_type
            ?? $request->name;

        $service->update([
            'name'            => $request->name,
            'description'     => $request->description,
            'price_per_piece' => $pricePerPiece,
            'price_per_load'  => $pricePerLoad,
            'min_weight'      => $request->min_weight,
            'max_weight'      => $request->max_weight,
            'service_type'    => $serviceTypeText,
            'service_type_id' => $request->service_type_id ?? $service->service_type_id,
            'pricing_type'    => $request->pricing_type,
            'turnaround_time' => $request->turnaround_time,
            'slug'            => $request->slug ?? Str::slug($request->name),
            'is_active'       => $request->is_active ?? true,
            'category'        => $request->category,   // always from form, never overwritten
        ]);

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Service updated successfully!',
                'service' => $service
            ]);
        }

        return redirect()->route('admin.services.index')->with('success', 'Service updated successfully!');
    }

    /**
     * Toggle service active status
     */
    public function toggleStatus(Request $request, $id)
    {
        try {
            $service = Service::findOrFail($id);

            // Validate the request
            $request->validate([
                'is_active' => 'required|boolean'
            ]);

            $service->is_active = $request->is_active;
            $service->save();

            return response()->json([
                'success' => true,
                'message' => 'Service status updated successfully!',
                'is_active' => $service->is_active
            ]);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Service not found'
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error updating service status: ' . $e->getMessage()
            ], 500);
        }
    }

    // Delete a service - FIXED to return JSON
    public function destroy(Service $service)
    {
        try {
            // Check if service has any laundries
            $laundryCount = $service->laundries()->count();

            if ($laundryCount > 0) {
                return response()->json([
                    'success' => false,
                    'message' => "Cannot delete service because it has been used in {$laundryCount} laundry order(s). You can deactivate it instead."
                ], 422);
            }

            // Delete icon if exists
            if ($service->icon_path) {
                Storage::disk('public')->delete($service->icon_path);
            }

            $service->delete();

            return response()->json([
                'success' => true,
                'message' => 'Service deleted successfully!'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error deleting service: ' . $e->getMessage()
            ], 500);
        }
    }
}
