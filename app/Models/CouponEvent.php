<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CouponEvent extends Model
{
    protected $fillable = [
        'user_id', 'coupon_code', 'event_type', 'reason', 'rule_version', 'cart_context'
    ];

    protected $casts = [
        'cart_context' => 'array'
    ];
}
