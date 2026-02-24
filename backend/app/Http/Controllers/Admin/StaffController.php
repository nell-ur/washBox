<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Branch;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rules\Password;

class StaffController extends Controller
{
    /**
     * Display a listing of staff members
     */
    public function index(Request $request)
    {
        $query = User::where('role', 'staff')
            ->with(['branch'])
            ->withCount(['laundries']);

        // Filter by branch
        if ($request->filled('branch_id')) {
            $query->where('branch_id', $request->branch_id);
        }

        // Filter by status
        if ($request->filled('status')) {
            if ($request->status === 'active') {
                $query->where('is_active', true);
            } elseif ($request->status === 'inactive') {
                $query->where('is_active', false);
            }
        }

        // Search by name or email
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        // Sort
        $sortBy = $request->get('sort_by', 'created_at');
        $sortLaundry = $request->get('sort_laundries', 'desc');
        $query->orderBy('laundries_count', $sortLaundry);

        $staff = $query->paginate(12);
        $branches = Branch::active()->get();

        // Statistics
        $stats = [
            'total' => User::where('role', 'staff')->count(),
            'active' => User::where('role', 'staff')->where('is_active', true)->count(),
            'inactive' => User::where('role', 'staff')->where('is_active', false)->count(),
            'total_laundries' => User::where('role', 'staff')->withCount('laundries')->get()->sum('laundries_count'),
        ];

        return view('admin.staff.index', compact('staff', 'branches', 'stats'));
    }

    /**
     * Show the form for creating a new staff member
     */
    public function create()
    {
        $branches = Branch::active()->get();
        return view('admin.staff.create', compact('branches'));
    }

    /**
     * Store a newly created staff member in storage
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'phone' => 'nullable|string|max:50',
            'password' => ['required', 'confirmed', Password::min(8)],
            'branch_id' => 'required|exists:branches,id',
            'employee_id' => 'nullable|string|max:50|unique:users,employee_id',
            'position' => 'nullable|string|max:255',
            'hire_date' => 'nullable|date',
            'photo' => 'nullable|image|mimes:jpeg,jpg,png|max:2048',
            'address' => 'nullable|string|max:500',
            'emergency_contact' => 'nullable|string|max:255',
            'emergency_phone' => 'nullable|string|max:50',
            'is_active' => 'nullable|boolean',
        ]);

        // Handle photo upload
        if ($request->hasFile('photo')) {
            $validated['profile_photo_path'] = $request->file('photo')->store('staff', 'public');
        }

        // Set role and hash password
        $validated['role'] = 'staff';
        $validated['password'] = Hash::make($validated['password']);
        $validated['is_active'] = $request->has('is_active') ? true : false;

        User::create($validated);

        return redirect()->route('admin.staff.index')
            ->with('success', 'Staff member added successfully!');
    }

    /**
     * Display the specified staff member
     */
    public function show(User $staff)
    {
        // Load relationships
        $staff->load(['branch', 'laundries' => function($query) {
            $query->with(['customer', 'service'])->latest()->take(10);
        }]);

        // Calculate statistics
        $stats = [
            'total_laundries' => $staff->laundries()->count(),
            'completed_laundries' => $staff->laundries()->where('status', 'completed')->count(),
            'pending_laundries' => $staff->laundries()->whereIn('status', ['pending', 'processing'])->count(),
            'total_revenue' => $staff->laundries()->where('status', 'completed')->sum('total_amount'),
            'avg_laundry_value' => $staff->laundries()->where('status', 'completed')->avg('total_amount') ?? 0,
        ];

        // Recent laundries
        $recent_laundries = $staff->laundries()
            ->with(['customer', 'service'])
            ->latest()
            ->take(10)
            ->get();

        return view('admin.staff.show', compact('staff', 'stats', 'recent_laundries'));
    }

    /**
     * Show the form for editing the specified staff member
     */
    public function edit(User $staff)
    {
        $branches = Branch::active()->get();
        return view('admin.staff.edit', compact('staff', 'branches'));
    }

    /**
     * Update the specified staff member in storage
     */
    public function update(Request $request, User $staff)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email,' . $staff->id,
            'phone' => 'nullable|string|max:50',
            'branch_id' => 'required|exists:branches,id',
            'employee_id' => 'nullable|string|max:50|unique:users,employee_id,' . $staff->id,
            'position' => 'nullable|string|max:255',
            'hire_date' => 'nullable|date',
            'photo' => 'nullable|image|mimes:jpeg,jpg,png|max:2048',
            'address' => 'nullable|string|max:500',
            'emergency_contact' => 'nullable|string|max:255',
            'emergency_phone' => 'nullable|string|max:50',
            'is_active' => 'nullable|boolean',
        ]);

        // Handle photo upload
        if ($request->hasFile('photo')) {
            // Delete old photo
            if ($staff->profile_photo_path) {
                Storage::disk('public')->delete($staff->profile_photo_path);
            }
            $validated['profile_photo_path'] = $request->file('photo')->store('staff', 'public');
        }

        $validated['is_active'] = $request->has('is_active') ? true : false;

        $staff->update($validated);

        return redirect()->route('admin.staff.index')
            ->with('success', 'Staff member updated successfully!');
    }

    /**
     * Remove the specified staff member from storage
     */
    public function destroy(User $staff)
    {
        // Check if staff has any laundries
        if ($staff->laundries()->count() > 0) {
            return redirect()->route('admin.staff.index')
                ->with('error', 'Cannot delete staff member with existing laundries. Please deactivate instead.');
        }

        // Delete photo if exists
        if ($staff->profile_photo_path) {
            Storage::disk('public')->delete($staff->profile_photo_path);
        }

        $staff->delete();

        return redirect()->route('admin.staff.index')
            ->with('success', 'Staff member deleted successfully!');
    }

    /**
     * Toggle staff member active/inactive status
     */
    public function toggleStatus(User $staff)
    {
        $staff->update([
            'is_active' => !$staff->is_active
        ]);

        $status = $staff->is_active ? 'activated' : 'deactivated';

        return redirect()->route('admin.staff.index')
            ->with('success', "Staff member {$status} successfully!");
    }

    /**
     * Reset staff member password
     */
    public function resetPassword(Request $request, User $staff)
    {
        $validated = $request->validate([
            'password' => ['required', 'confirmed', Password::min(8)],
        ]);

        $staff->update([
            'password' => Hash::make($validated['password'])
        ]);

        return redirect()->route('admin.staff.show', $staff)
            ->with('success', 'Password reset successfully!');
    }
}
