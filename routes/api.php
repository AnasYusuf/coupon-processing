<?php

// routes/api.php
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\CouponController;
use Illuminate\Support\Facades\Redis;
use App\Http\Controllers\CheckoutController;
use App\Services\CouponService;

Route::get('/test', function () {
    return response()->json(['message' => 'API routes working!']);
});

Route::post('/apply-coupon', [CouponController::class, 'apply']);
Route::post('/consume-coupon', [CouponController::class, 'consume']);
Route::post('/release-coupon', [CouponController::class, 'release']);

//Route::post('/checkout-success', [CheckoutController::class, 'success']);
Route::post('/checkout-success', 
function (Request $request, CouponService $service) {
    $userId = $request->input('user_id');
    $couponCode = $request->input('coupon_code');
    $cart = $request->input('cart');

    // Attempt to consume the coupon
    $success = $service->consume($userId, $couponCode, $cart);

    if ($success) {
        return response()->json(['message' => 'Coupon consumed']);
    } else {
        return response()->json(['message' => 'Cannot consume coupon: reservation expired or invalid'], 400);
    }
});

Route::post('/checkout-fail', function (Request $request) {
    $userId = $request->input('user_id');
    $couponCode = $request->input('coupon_code');
    $cart = $request->input('cart');

    // Remove reservation
    \Illuminate\Support\Facades\Redis::del("coupon:{$couponCode}:user:{$userId}");

    // Record released event
    \App\Models\CouponEvent::create([
        'user_id' => $userId,
        'coupon_code' => $couponCode,
        'event_type' => 'released',
        'reason' => 'checkout failed',
        'rule_version' => 'v1',
        'cart_context' => $cart
    ]);

    return response()->json(['message'=>'Coupon released']);
});

Route::get('/redis-test', function () {
    Redis::set('coupon_test', 'working');
    return Redis::get('coupon_test');
});

