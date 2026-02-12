<?php

namespace App\Services;

use App\Models\Coupon;
use App\Models\CouponEvent;
use Illuminate\Support\Facades\Redis;
use App\Jobs\UpdateCartJob;
use App\Jobs\LogEventJob;

class CouponService
{
    protected int $reservationTTL = 300; // 5 minutes

    public function reserve(int $userId, string $couponCode, array $cart): bool
    {
        $coupon = Coupon::where('code', $couponCode)->first();

        if (!$coupon || !$coupon->is_active) {
            $this->log($userId, $couponCode, 'failed', 'Coupon inactive or not found', $coupon, $cart);
            return false;
        }

        // ----------------------------
        // Global & per-user limits
        // ----------------------------
        $userConsumedCount = CouponEvent::where('coupon_code', $couponCode)
            ->where('user_id', $userId)
            ->where('event_type', 'consumed')
            ->count();

        if ($coupon->per_user_limit !== null && $userConsumedCount >= $coupon->per_user_limit) {
            $this->log($userId, $couponCode, 'failed', 'Per-user limit reached', $coupon, $cart);
            return false;
        }

        $activeReservations = Redis::keys("coupon:{$couponCode}:user:*");
        $totalReserved = count($activeReservations);

        if ($coupon->usage_limit !== null && ($coupon->used_count + $totalReserved) >= $coupon->usage_limit) {
            $this->log($userId, $couponCode, 'failed', 'Global limit reached', $coupon, $cart);
            return false;
        }

        // ----------------------------
        // Ensure total & user_order_count exist
        // ----------------------------
        $cartWithTotal = $cart;
        if (!isset($cartWithTotal['total'])) {
            $cartWithTotal['total'] = array_sum(array_column($cartWithTotal, 'price'));
        }
        if (!isset($cartWithTotal['user_order_count'])) {
            $cartWithTotal['user_order_count'] = 0;
        }

        // ----------------------------
        // Validate coupon rules
        // ----------------------------
        $ruleEngine = new RuleEngine();
        if (!$ruleEngine->validate($coupon, $userId, $cartWithTotal)) {
            $this->log($userId, $couponCode, 'failed', 'Rule validation failed', $coupon, $cartWithTotal);
            return false;
        }

        // ----------------------------
        // Atomic reservation in Redis
        // ----------------------------
        $reservationKey = "coupon:{$couponCode}:user:{$userId}";
        $reserved = Redis::set(
            $reservationKey,
            json_encode([
                'user_id' => $userId,
                'coupon_code' => $couponCode,
                'reserved_at' => now()->timestamp
            ]),
            'NX',
            'EX',
            $this->reservationTTL
        );

        if (!$reserved) {
            $this->log($userId, $couponCode, 'already_reserved', null, $coupon, $cartWithTotal);
            return true; // idempotent
        }

        // ----------------------------
        // Log reservation
        // ----------------------------
        $this->log($userId, $couponCode, 'reserved', null, $coupon, $cartWithTotal);

        // ----------------------------
        // Dispatch cart update job to default queue
        // ----------------------------
        UpdateCartJob::dispatch($userId, $cartWithTotal, $couponCode)
            ->onQueue('default');

        return true;
    }

     /**
     * Consume coupon after successful checkout
     */
    public function consume(int $userId, string $couponCode): bool
    {
        $coupon = Coupon::where('code', $couponCode)->first();
        if (!$coupon) return false;

        $reservationKey = "coupon:{$couponCode}:user:{$userId}";
        if (!Redis::exists($reservationKey)) {
            $this->log($userId, $couponCode, 'failed', 'Cannot consume without active reservation', $coupon, null);
            return false;
        }

        Redis::del($reservationKey);
        $coupon->increment('used_count');

        $this->log($userId, $couponCode, 'consumed', null, $coupon, null);
        return true;
    }

    /**
     * Release coupon after failed checkout or timeout
     */
    public function release(int $userId, string $couponCode): bool
    {
        $coupon = Coupon::where('code', $couponCode)->first();
        if (!$coupon) return false;

        $reservationKey = "coupon:{$couponCode}:user:{$userId}";
        if (!Redis::exists($reservationKey)) return false;

        Redis::del($reservationKey);
        $this->log($userId, $couponCode, 'released', 'checkout failed', $coupon, null);

        return true;
    }

    /**
     * Centralized logging via LogEventJob
     */
    protected function log(int $userId, string $couponCode, string $eventType, ?string $reason, ?Coupon $coupon, ?array $cart): void
    {
        LogEventJob::dispatch(
            $userId,
            $couponCode,
            $eventType,
            $reason,
            $coupon?->rule_version,
            $cart
        )->onQueue('low');
    }
}
