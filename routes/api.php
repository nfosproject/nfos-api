<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\Admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\Api\Admin\NotificationController as AdminNotificationController;
use App\Http\Controllers\Api\Admin\OrderController as AdminOrderController;
use App\Http\Controllers\Api\Admin\ProductController as AdminProductController;
use App\Http\Controllers\Api\Admin\UserController as AdminUserController;
use App\Http\Controllers\Api\Admin\CouponController as AdminCouponController;
use App\Http\Controllers\Api\AddressController;
use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\CouponController;
use App\Http\Controllers\Api\NotificationController as PublicNotificationController;
use App\Http\Controllers\Api\OrderController;
use App\Http\Controllers\Api\PointsController;
use App\Http\Controllers\Api\ProductController;
use App\Http\Controllers\Api\Seller\OrderController as SellerOrderController;
use App\Http\Controllers\Api\Payment\EsewaPaymentController;
use App\Http\Controllers\Api\PayoutController;
use App\Http\Controllers\Api\Admin\PayoutController as AdminPayoutController;
use Illuminate\Support\Facades\Route;

Route::get('/health', fn () => response()->json(['ok' => true]));

Route::prefix('auth')->group(function () {
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/login', [AuthController::class, 'login']);

    Route::middleware('auth:sanctum')->group(function () {
        Route::get('/me', [AuthController::class, 'me']);
        Route::post('/logout', [AuthController::class, 'logout']);
    });
});

Route::get('/categories', [CategoryController::class, 'index']);
Route::get('/categories/{category}', [CategoryController::class, 'show']);

Route::get('/products', [ProductController::class, 'index']);
Route::get('/products/{product}', [ProductController::class, 'show']);

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/coupons', [CouponController::class, 'index']);
    Route::get('/coupons/validate', [CouponController::class, 'validateCode']);
    
    // Points routes
    Route::get('/points', [PointsController::class, 'index']);
    Route::post('/points/redeem', [PointsController::class, 'redeem']);
    Route::post('/points/earn', [PointsController::class, 'earn']);
    Route::post('/points/referral', [PointsController::class, 'earnReferralPoints']);
    Route::post('/points/review', [PointsController::class, 'earnReviewPoints']);
    Route::post('/points/adjust', [PointsController::class, 'adjust']);
    Route::post('/points/expiry-check', [PointsController::class, 'runExpiryCheck']);
});

Route::get('/notifications', [PublicNotificationController::class, 'index']);
Route::patch('/notifications/{notification}', [PublicNotificationController::class, 'markRead']);
Route::post('/notifications/mark-all-read', [PublicNotificationController::class, 'markAllRead']);

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/orders', [OrderController::class, 'index']);
    Route::get('/orders/{order}', [OrderController::class, 'show']);
    Route::post('/orders', [OrderController::class, 'store']);
    Route::patch('/orders/{order}', [OrderController::class, 'update']);
    Route::delete('/notifications/{notification}', [PublicNotificationController::class, 'destroy']);
    Route::post('/payments/esewa/initiate', [EsewaPaymentController::class, 'initiate']);

    // Address Book Routes
    Route::get('/addresses', [AddressController::class, 'index']);
    Route::get('/addresses/{address}', [AddressController::class, 'show']);
    Route::post('/addresses', [AddressController::class, 'store']);
    Route::patch('/addresses/{address}', [AddressController::class, 'update']);
    Route::delete('/addresses/{address}', [AddressController::class, 'destroy']);
    Route::post('/addresses/{address}/set-default', [AddressController::class, 'setDefault']);

    // Payout routes (seller)
    Route::get('/payouts/balance', [PayoutController::class, 'balance']);
    Route::get('/payouts/history', [PayoutController::class, 'history']);
    Route::get('/payouts/earnings', [PayoutController::class, 'earnings']);
    Route::post('/payouts/update-info', [PayoutController::class, 'updatePayoutInfo']);
});

Route::post('/payments/esewa/callback', [EsewaPaymentController::class, 'callback']);

Route::middleware('auth:sanctum')->prefix('seller')->group(function () {
    Route::get('/orders/overview', [SellerOrderController::class, 'overview']);
    Route::post('/orders/print-labels', [SellerOrderController::class, 'printLabels']);
    Route::patch('/orders/{order}', [SellerOrderController::class, 'update']);
});

Route::prefix('admin')->group(function () {
    Route::get('/overview', [AdminDashboardController::class, 'overview']);

    Route::get('/products', [AdminProductController::class, 'index']);
    Route::post('/products', [AdminProductController::class, 'store']);
    Route::match(['put', 'patch'], '/products/{product}', [AdminProductController::class, 'update']);
    Route::delete('/products/{product}', [AdminProductController::class, 'destroy']);

    Route::get('/users', [AdminUserController::class, 'index']);
    Route::post('/users', [AdminUserController::class, 'store']);
    Route::match(['put', 'patch'], '/users/{user}', [AdminUserController::class, 'update']);
    Route::delete('/users/{user}', [AdminUserController::class, 'destroy']);

    Route::get('/orders', [AdminOrderController::class, 'index']);
    Route::post('/orders', [AdminOrderController::class, 'store']);
    Route::match(['put', 'patch'], '/orders/{order}', [AdminOrderController::class, 'update']);
    Route::delete('/orders/{order}', [AdminOrderController::class, 'destroy']);

    Route::get('/coupons', [AdminCouponController::class, 'index']);
    Route::post('/coupons', [AdminCouponController::class, 'store']);
    Route::match(['put', 'patch'], '/coupons/{coupon}', [AdminCouponController::class, 'update']);
    Route::delete('/coupons/{coupon}', [AdminCouponController::class, 'destroy']);

    Route::get('/notifications', [AdminNotificationController::class, 'index']);
    Route::post('/notifications', [AdminNotificationController::class, 'store']);
    Route::match(['put', 'patch'], '/notifications/{notification}', [AdminNotificationController::class, 'update']);
    Route::delete('/notifications/{notification}', [AdminNotificationController::class, 'destroy']);
    Route::post('/notifications/mark-all-read', [AdminNotificationController::class, 'markAllRead']);

    // Admin payout routes
    Route::get('/payouts/sellers', [AdminPayoutController::class, 'sellers']);
    Route::get('/payouts/batches', [AdminPayoutController::class, 'batches']);
    Route::get('/payouts/batches/{batch}', [AdminPayoutController::class, 'batchDetails']);
    Route::get('/payouts/batches/{batch}/export', [AdminPayoutController::class, 'exportBatch']);
    Route::post('/payouts/trigger/{sellerId}', [AdminPayoutController::class, 'triggerManualPayout']);
    Route::get('/payouts/statistics', [AdminPayoutController::class, 'statistics']);
});
