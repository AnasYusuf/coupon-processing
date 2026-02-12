<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Jobs\ApplyCouponJob;
use App\Services\CouponService;

class CouponController extends Controller
{
    public function apply(Request $request)
    {
        $request->validate([
            'user_id' => 'required|integer',
            'coupon_code' => 'required|string',
            'cart' => 'required|array',
        ]);

        ApplyCouponJob::dispatch(
            $request->user_id,
            $request->cart,
            $request->coupon_code
        )->onQueue('high');

        return response()->json(['message' => 'Coupon verification in progress'], 200);
    }

    public function consume(Request $request, CouponService $service)
    {
        $request->validate([
            'user_id' => 'required|integer',
            'coupon_code' => 'required|string',
        ]);

        return $service->consume($request->user_id, $request->coupon_code)
            ? response()->json(['message' => 'Coupon consumed'], 200)
            : response()->json(['message' => 'Cannot consume coupon: reservation expired or invalid'], 400);
    }

    public function release(Request $request, CouponService $service)
    {
        $request->validate([
            'user_id' => 'required|integer',
            'coupon_code' => 'required|string',
        ]);

        return $service->release($request->user_id, $request->coupon_code)
            ? response()->json(['message' => 'Coupon released'], 200)
            : response()->json(['message' => 'Nothing to release'], 200);
    }
}
