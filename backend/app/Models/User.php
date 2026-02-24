<?php

namespace App\Models;

use Laravel\Sanctum\HasApiTokens;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;

class User extends Authenticatable
{
    use Notifiable;
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'phone',
        'address',
        'employee_id',
        'position',
        'branch_id',
        'hire_date',
        'profile_photo_path',
        'emergency_contact',
        'emergency_phone',
        'is_active',
        'role',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
        'hire_date' => 'date',
        'is_active' => 'boolean',
    ];

    /**
     * Get the branch that the staff member belongs to.
     */
    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    /**
     * Get all laundries assigned to this staff member.
     */
    public function laundries()
    {
        return $this->hasMany(Laundry::class, 'staff_id');
    }

    /**
     * Get all pickup requests assigned to this staff member.
     */
    public function pickupRequests()
    {
        return $this->hasMany(PickupRequest::class, 'assigned_staff_id');
    }

    /**
     * Scope a query to only include staff members.
     */
    public function scopeStaff($query)
    {
        return $query->where('role', 'staff');
    }

    /**
     * Scope a query to only include admin users.
     */
    public function scopeAdmin($query)
    {
        return $query->where('role', 'admin');
    }

    /**
     * Scope a query to only include active users.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope a query to only include inactive users.
     */
    public function scopeInactive($query)
    {
        return $query->where('is_active', false);
    }

    /**
     * Scope a query to filter by branch.
     */
    public function scopeByBranch($query, $branchId)
    {
        return $query->where('branch_id', $branchId);
    }

    /**
     * Get the profile photo URL.
     */
    public function getProfilePhotoUrlAttribute()
    {
        if (!$this->profile_photo_path) {
            return null;
        }

        // If it's already a full URL, return it
        if (filter_var($this->profile_photo_path, FILTER_VALIDATE_URL)) {
            return $this->profile_photo_path;
        }

        // Otherwise, return storage URL
        return asset('storage/' . $this->profile_photo_path);
    }

    /**
     * Get the user's full name with employee ID.
     */
    public function getFullNameAttribute()
    {
        if ($this->employee_id) {
            return "{$this->name} ({$this->employee_id})";
        }
        return $this->name;
    }

    /**
     * Get the user's status label.
     */
    public function getStatusLabelAttribute()
    {
        return $this->is_active ? 'Active' : 'Inactive';
    }

    /**
     * Get the user's status color.
     */
    public function getStatusColorAttribute()
    {
        return $this->is_active ? 'success' : 'secondary';
    }

    /**
     * Get tenure in months.
     */
    public function getTenureMonthsAttribute()
    {
        if (!$this->hire_date) {
            return 0;
        }
        return $this->hire_date->diffInMonths(now());
    }

    /**
     * Get tenure in human readable format.
     */
    public function getTenureHumanAttribute()
    {
        if (!$this->hire_date) {
            return 'N/A';
        }
        return $this->hire_date->diffForHumans(null, true);
    }

    /**
     * Check if user is an admin.
     */
    public function isAdmin()
    {
        return $this->role === 'admin';
    }

    /**
     * Check if user is a staff member.
     */
    public function isStaff()
    {
        return $this->role === 'staff';
    }

    /**
     * Check if user is a customer.
     */
    public function isCustomer()
    {
        return $this->role === 'customer';
    }

    /**
     * Check if user has any laundries.
     */
    public function hasLaundries()
    {
        return $this->laundries()->exists();
    }

    /**
     * Get completed laundries count.
     */
    public function getCompletedLaundriesCountAttribute()
    {
        return $this->laundries()->where('status', 'completed')->count();
    }

    /**
     * Get pending laundries count.
     */
    public function getPendingLaundriesCountAttribute()
    {
        return $this->laundries()->whereIn('status', ['pending', 'processing'])->count();
    }

    /**
     * Get total revenue from completed laundries.
     */
    public function getTotalRevenueAttribute()
    {
        return $this->laundries()->where('status', 'completed')->sum('total_amount');
    }

    /**
     * Get average laundry value.
     */
    public function getAvgLaundryValueAttribute()
    {
        return $this->laundries()->where('status', 'completed')->avg('total_amount') ?? 0;
    }

    public function notifications()
    {
        return $this->hasMany(Notification::class, 'user_id');
    }

     /**
     * Get all notifications for this user
     */
    public function userNotifications()
    {
        return $this->hasMany(UserNotification::class)->orderBy('created_at', 'desc');
    }

    /**
     * Get unread notifications
     */
    public function unreadNotifications()
    {
        return $this->hasMany(UserNotification::class)
            ->where('is_read', false)
            ->orderBy('created_at', 'desc');
    }

    /**
     * Get unread notification count
     */
    public function getUnreadNotificationCountAttribute()
    {
        return $this->unreadNotifications()->count();
    }

    /**
     * Mark all notifications as read
     */
    public function markAllNotificationsAsRead()
    {
        $this->unreadNotifications()->update([
            'is_read' => true,
            'read_at' => now()
        ]);
    }

}
