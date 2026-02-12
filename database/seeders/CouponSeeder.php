<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Coupon;

class CouponSeeder extends Seeder
{
    public function run(): void
    {
        Coupon::create([
            'code' => 'TEST10',           // Coupon code
            'is_active' => 1,
            'usage_limit' => 100,          // Total times it can be used
            'per_user_limit' => 1,         // Max usage per user
            'min_cart_value' => 50,        // Minimum cart value
            'rules' => json_encode([       // Optional dynamic rules
                'first_time_user' => false,
            ]),
            'used_count' => 0,             // Initial used count
            'rule_version' => 'v1',        // Version for auditing
        ]);

        Coupon::create([
            'code' => 'WELCOME10',           // Coupon code
            'is_active' => 1,
            'usage_limit' => 100,          // Total times it can be used
            'per_user_limit' => 1,         // Max usage per user
            'min_cart_value' => 50,        // Minimum cart value
            'rules' => json_encode([       // Optional dynamic rules
                'first_time_user' => false,
            ]),
            'used_count' => 0,             // Initial used count
            'rule_version' => 'v1',        // Version for auditing
        ]);
    }
}
