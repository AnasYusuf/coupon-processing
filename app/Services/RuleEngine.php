<?php

namespace App\Services;

use App\Models\Coupon;

class RuleEngine
{
    /**
     * Validate a coupon for a given user and cart.
     */
    public function validate(Coupon $coupon, int $userId, array $cart): bool
{
    $total = $cart['total'] ?? array_sum(array_column($cart, 'price'));
    $userOrderCount = $cart['user_order_count'] ?? 0;

    // Usage limits
    if ($coupon->usage_limit > 0 && $coupon->used_count >= $coupon->usage_limit) return false;
    if ($coupon->per_user_limit > 0 && $coupon->userUses($userId) >= $coupon->per_user_limit) return false;

    // Minimum cart value
    if ($total < $coupon->min_cart_value) return false;

    // Dynamic rules
    if (!empty($coupon->rules) && is_array($coupon->rules)) {
        if (($coupon->rules['first_time_user'] ?? false) && $userOrderCount > 0) return false;
    }

    return true;
}


}
