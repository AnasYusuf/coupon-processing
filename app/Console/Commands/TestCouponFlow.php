<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Coupon;
use App\Services\CouponService;

class TestCouponFlow extends Command
{
    protected $signature = 'coupon:test';
    protected $description = 'Test full coupon lifecycle: reserve, consume, release';

    public function handle()
    {
        $userId = 1;

        // Fetch a test coupon
        $coupon = Coupon::where('code', 'TEST10')->first();
        if (!$coupon) {
            $this->error('Test coupon not found');
            return 1;
        }

        // Get CouponService instance from the container
        $service = app(CouponService::class);

        $cart = [
            ['item_id' => 101, 'price' => 50],
            ['item_id' => 102, 'price' => 30],
        ];

        $this->info("=== Testing reservation ===");
        $reserved = $service->reserve($userId, $coupon->code, $cart);
        $this->info($reserved ? 'Coupon reserved successfully' : 'Reservation failed');

        $this->info("=== Testing consumption ===");
        $service->consume($userId, $coupon->code);
        $this->info('Coupon consumed');

        $this->info("=== Testing release ===");
        $service->release($userId, $coupon->code);
        $this->info('Coupon released');

        $this->info("=== Test completed ===");
        return 0;
    }
}
