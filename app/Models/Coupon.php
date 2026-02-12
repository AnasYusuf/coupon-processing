<?php 

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Coupon extends Model
{
    protected $fillable = [
        'code', 'usage_limit', 'per_user_limit', 'min_cart_value', 'rules', 'used_count', 'rule_version'
    ];

    protected $casts = [
        'rules' => 'array'
    ];

    public function userUses(int $userId): int
    {
        return \DB::table('coupon_events')
            ->where('coupon_code', $this->code)
            ->where('user_id', $userId)
            ->where('event_type', 'consumed')
            ->count();
    }
}
