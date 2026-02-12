<?php

namespace App\Jobs;

use App\Services\CouponService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ApplyCouponJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $timeout = 10;
    public int $backoff = 5;

    public function __construct(
        public int $userId,
        public array $cart,
        public string $couponCode
    ) {}

    public function handle(CouponService $service): void
    {
        $service->reserve($this->userId, $this->couponCode, $this->cart);
    }
}
