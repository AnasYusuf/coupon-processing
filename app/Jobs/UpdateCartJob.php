<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class UpdateCartJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $userId;
    public $cart;
    public $couponCode;

    public function __construct(int $userId, array $cart, string $couponCode)
    {
        $this->userId = $userId;
        $this->cart = $cart;
        $this->couponCode = $couponCode;

        // Assign to default queue
        $this->queue = 'default';
    }

    public function handle()
    {
        // Example logic: apply discount in cart
        // Replace with your real cart update logic
        // For demonstration, just log it
        \Log::info("Cart updated for user {$this->userId} with coupon {$this->couponCode}", [
            'cart' => $this->cart
        ]);

        // You can also emit events or update DB/cart table here
    }
}
