<?php
// app/Http/Controllers/Admin/ServiceTypeController.php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ServiceType;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

class ServiceTypeController extends Controller
{
    public function index()
    {
        return redirect()->route('admin.services.index')
            ->with('info', 'Service types are managed from the Services page.');
    }

    public function create()
    {
        return redirect()->route('admin.services.index');
    }

    public function store(Request $request)
    {
        // Fix 1: Empty string fails 'json' rule — null it out first
        if (trim($request->input('defaults', '')) === '') {
            $request->merge(['defaults' => null]);
        }

        // Fix 2: 'display_order' needs 'nullable', 'is_active' is a checkbox (sends 'on', not bool)
        try {
            $request->validate([
                'name'          => 'required|string|max:255',
                'category'      => 'required|in:drop_off,self_service,addon',
                'description'   => 'nullable|string',
                'defaults'      => 'nullable|json',
                'icon'          => 'nullable|string|max:50',
                'display_order' => 'nullable|integer|min:0',
                // is_active handled manually — checkbox sends 'on', not boolean
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            // Fix 3: Return JSON so AJAX can show error inside the modal
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors'  => $e->errors(),
            ], 422);
        }

        // Build unique slug
        $slug  = Str::slug($request->name);
        $base  = $slug;
        $count = 1;
        while (ServiceType::where('slug', $slug)->exists()) {
            $slug = $base . '-' . $count++;
        }

        // Parse defaults JSON safely
        $defaults    = null;
        $rawDefaults = trim($request->input('defaults', ''));
        if ($rawDefaults !== '') {
            $defaults = json_decode($rawDefaults, true);
        }

        try {
            $serviceType = ServiceType::create([
                'name'          => $request->name,
                'slug'          => $slug,
                'category'      => $request->category,
                'description'   => $request->description,
                'defaults'      => $defaults,
                'icon'          => $request->icon,
                'display_order' => (int) ($request->display_order ?? 0),
                'is_active'     => $request->has('is_active'), // checkbox: present=true, absent=false
            ]);

            return response()->json([
                'success'      => true,
                'message'      => 'Service type "' . $serviceType->name . '" created successfully!',
                'service_type' => $serviceType,
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to create service type: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to create service type: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function show(ServiceType $serviceType)
    {
        return redirect()->route('admin.services.index');
    }

    public function edit(ServiceType $serviceType)
    {
        return redirect()->route('admin.services.index')
            ->with('info', 'Edit functionality coming soon.');
    }

    public function update(Request $request, ServiceType $serviceType)
    {
        if (trim($request->input('defaults', '')) === '') {
            $request->merge(['defaults' => null]);
        }

        try {
            $request->validate([
                'name'          => 'required|string|max:255',
                'category'      => 'required|in:drop_off,self_service,addon',
                'description'   => 'nullable|string',
                'defaults'      => 'nullable|json',
                'icon'          => 'nullable|string|max:50',
                'display_order' => 'nullable|integer|min:0',
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors'  => $e->errors(),
            ], 422);
        }

        if ($serviceType->name !== $request->name) {
            $slug  = Str::slug($request->name);
            $base  = $slug;
            $count = 1;
            while (ServiceType::where('slug', $slug)->where('id', '!=', $serviceType->id)->exists()) {
                $slug = $base . '-' . $count++;
            }
            $serviceType->slug = $slug;
        }

        $defaults    = null;
        $rawDefaults = trim($request->input('defaults', ''));
        if ($rawDefaults !== '') {
            $defaults = json_decode($rawDefaults, true);
        }

        try {
            $serviceType->update([
                'name'          => $request->name,
                'category'      => $request->category,
                'description'   => $request->description,
                'defaults'      => $defaults,
                'icon'          => $request->icon,
                'display_order' => (int) ($request->display_order ?? 0),
                'is_active'     => $request->has('is_active'),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Service type updated successfully.',
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to update service type: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to update service type.',
            ], 500);
        }
    }

    public function destroy(ServiceType $serviceType)
    {
        if ($serviceType->services()->count() > 0) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot delete service type that is being used by ' . $serviceType->services()->count() . ' service(s).'
            ], 422);
        }

        try {
            $serviceType->delete();
            return response()->json(['success' => true, 'message' => 'Service type deleted successfully.']);
        } catch (\Exception $e) {
            Log::error('Failed to delete service type: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Failed to delete service type.'], 500);
        }
    }

    public function toggleStatus(ServiceType $serviceType)
    {
        try {
            $serviceType->update(['is_active' => !$serviceType->is_active]);
            return response()->json([
                'success'   => true,
                'message'   => 'Service type status updated successfully.',
                'is_active' => $serviceType->is_active,
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to toggle service type status: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Failed to update service type status.'], 500);
        }
    }

    public function getByCategory($category)
    {
        try {
            $types = ServiceType::where('category', $category)
                ->where('is_active', true)
                ->orderBy('display_order')
                ->orderBy('name')
                ->get(['id', 'name', 'defaults', 'description', 'icon']);

            return response()->json($types);
        } catch (\Exception $e) {
            Log::error('Failed to get service types by category: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to fetch service types'], 500);
        }
    }
}
