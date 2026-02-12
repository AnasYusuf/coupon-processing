<?php

namespace App\Jobs;

use App\Models\CouponEvent;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class LogEventJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public int $userId,
        public string $couponCode,
        public string $eventType,
        public ?string $reason = null,
        public ?string $ruleVersion = null,
        public ?array $cartContext = null
    ) {}

    public function handle()
    {
        CouponEvent::create([
            'user_id' => $this->userId,
            'coupon_code' => $this->couponCode,
            'event_type' => $this->eventType,
            'reason' => $this->reason,
            'rule_version' => $this->ruleVersion,
            'cart_context' => $this->cartContext,
        ]);
    }
}
