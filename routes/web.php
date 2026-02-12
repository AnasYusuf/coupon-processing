<?php

use Illuminate\Support\Facades\Route;
//use App\Http\Controllers\CouponController;

Route::get('/', function () {
    return view('welcome');
});

//Route::post('/apply-coupon', [CouponController::class, 'apply']);