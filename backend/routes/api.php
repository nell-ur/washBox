<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\LaundryController;
use App\Http\Controllers\Api\BranchController;
use App\Http\Controllers\Api\PickupController;
use App\Http\Controllers\Api\ServiceController;
use App\Http\Controllers\Api\CustomerController;
use App\Http\Controllers\Api\PromotionController;
use App\Http\Controllers\Api\NotificationController;
use App\Http\Controllers\Api\CustomerRatingController;
use App\Http\Controllers\Admin\BranchRatingController as AdminBranchRatingController;
use App\Http\Controllers\Admin\NotificationController as AdminNotificationController;
use App\Http\Controllers\Staff\NotificationController as StaffNotificationController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

Route::prefix('v1')->group(function () {

    // ========================================
    // PUBLIC ROUTES (No Authentication Required)
    // ========================================

    // Authentication
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/login', [AuthController::class, 'login']);
    Route::post('/forgot-password', [AuthController::class, 'forgotPassword']);
    Route::post('/reset-password', [AuthController::class, 'resetPassword']);

    // Branches (Public)
    Route::prefix('branches')->group(function () {
        Route::get('/', [BranchController::class, 'index']);
        Route::get('/nearest', [BranchController::class, 'nearest']);
        Route::get('/{id}', [BranchController::class, 'show']);
        Route::get('/{branch}/operating-hours', [BranchController::class, 'operatingHours']);
    });

    // Services (Public)
    Route::get('/services', [ServiceController::class, 'index']);

    // Promotions (Public)
    Route::prefix('promotions')->group(function () {
        Route::get('/', [PromotionController::class, 'index']);
        Route::get('/featured', [PromotionController::class, 'featured']);
        Route::get('/validate-code', [PromotionController::class, 'validateCode']);
        Route::get('/applicable', [PromotionController::class, 'applicable']);
        Route::get('/{id}', [PromotionController::class, 'show']);
    });

    Route::get('/health', function () {
        return response()->json([
            'success' => true,
            'message' => 'WashBox API is running',
            'version' => '1.0.0',
            'timestamp' => now()->toIso8601String(),
        ]);
    });

    // ========================================
    // PROTECTED ROUTES (Require Authentication)
    // ========================================

    Route::middleware('auth:sanctum')->group(function () {

        // Authentication & Profile
        Route::post('/logout', [AuthController::class, 'logout']);
        Route::get('/user', [AuthController::class, 'user']);
        Route::get('/profile', [CustomerController::class, 'getProfile']);
        Route::put('/profile', [CustomerController::class, 'updateProfile']);
        Route::put('/password', [AuthController::class, 'changePassword']);

        // Customer Statistics & Info
        Route::prefix('customer')->group(function () {
            Route::get('/stats', [CustomerController::class, 'getStats']);
            Route::get('/active-laundries', [CustomerController::class, 'getActiveLaundries']);
            Route::get('/latest-pickup', [CustomerController::class, 'getLatestPickup']);

            // Push Notification Token & Preferences
            Route::post('/fcm-token', [CustomerController::class, 'updateFcmToken']);
            Route::put('/notification-preferences', [CustomerController::class, 'updateNotificationPreferences']);

            // Customer Ratings (Laundry)
            Route::get('/ratings', [CustomerRatingController::class, 'index']);
            Route::post('/ratings', [CustomerRatingController::class, 'store']);
            Route::get('/ratings/check/{laundryId}', [CustomerRatingController::class, 'check']);
            Route::put('/ratings/{id}', [CustomerRatingController::class, 'update']);
            Route::delete('/ratings/{id}', [CustomerRatingController::class, 'destroy']);
            Route::get('/unrated-laundries', [CustomerRatingController::class, 'unratedLaundries']);

            // Branch Ratings
            Route::get('/branches', [AdminBranchRatingController::class, 'index']);
            Route::get('/branch-ratings', [AdminBranchRatingController::class, 'index']);
            Route::post('/branch-ratings', [AdminBranchRatingController::class, 'store']);
            Route::get('/branch-ratings/stats', [AdminBranchRatingController::class, 'index']);
        });

        // Laundries
        Route::prefix('laundries')->group(function () {
            Route::get('/', [LaundryController::class, 'index']);
            Route::post('/', [LaundryController::class, 'store']);
            Route::get('/{id}', [LaundryController::class, 'show']);
            Route::put('/{id}/cancel', [LaundryController::class, 'cancel']);
        });

        // Pickup Requests (Customer)
        Route::prefix('pickups')->group(function () {
            Route::get('/', [PickupController::class, 'index']);
            Route::post('/', [PickupController::class, 'store']);
            Route::get('/{id}', [PickupController::class, 'show']);
            Route::put('/{id}/cancel', [PickupController::class, 'cancel']);
        });

        // ========================================
        // NOTIFICATIONS
        // ========================================
        Route::prefix('notifications')->group(function () {
            Route::get('/', [NotificationController::class, 'index']);
            Route::get('/unread-count', [NotificationController::class, 'unreadCount']);
            Route::post('/mark-all-read', [NotificationController::class, 'markAllAsRead']);
            Route::post('/{id}/read', [NotificationController::class, 'markAsRead']);
            Route::delete('/{id}', [NotificationController::class, 'destroy']);
            Route::delete('/clear-read', [NotificationController::class, 'clearRead']);
        });
    });

    // ========================================
    // STAFF ROUTES
    // ========================================

    Route::middleware(['auth:sanctum'])->prefix('staff')->group(function () {
        Route::prefix('pickups')->group(function () {
            Route::put('/{id}/accept', [PickupController::class, 'accept']);
            Route::put('/{id}/en-route', [PickupController::class, 'markEnRoute']);
            Route::put('/{id}/picked-up', [PickupController::class, 'markPickedUp']);
            Route::put('/{id}/link-order', [PickupController::class, 'linkOrder']);
        });
    });
});

Route::middleware('auth:staff')->group(function () {
    Route::get('/staff/notifications', [StaffNotificationController::class, 'index']);
    Route::get('/staff/notifications/unread-count', [StaffNotificationController::class, 'unreadCount']);
    Route::post('/staff/notifications/{id}/read', [StaffNotificationController::class, 'markAsRead']);
    Route::post('/staff/notifications/read-all', [StaffNotificationController::class, 'markAllAsRead']);
    Route::delete('/staff/notifications/{id}', [StaffNotificationController::class, 'delete']);
    Route::delete('/staff/notifications/clear-read', [StaffNotificationController::class, 'clearRead']);
});

Route::middleware('auth:admin')->group(function () {
    Route::get('/admin/notifications', [AdminNotificationController::class, 'index']);
    Route::get('/admin/notifications/unread-count', [AdminNotificationController::class, 'unreadCount']);
    Route::post('/admin/notifications/{id}/read', [AdminNotificationController::class, 'markAsRead']);
    Route::post('/admin/notifications/read-all', [AdminNotificationController::class, 'markAllAsRead']);
    Route::delete('/admin/notifications/{id}', [AdminNotificationController::class, 'delete']);
    Route::delete('/admin/notifications/clear-read', [AdminNotificationController::class, 'clearRead']);
});
