<?php

namespace Tests\Feature;

use App\Jobs\UpdateCartJob;
use App\Models\Coupon;
use App\Services\CouponService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Str;
use Tests\TestCase;

class CouponReservationTest extends TestCase
{
    use RefreshDatabase;

    private CouponService $service;
    private string $code;
    private array $cart;

    protected function setUp(): void
    {
        parent::setUp();

        // Do not run real jobs; we only check that they are dispatched.
        Bus::fake();

        $this->service = new CouponService();
        $this->code = 'TEST-' . Str::upper(Str::random(8));
        $this->cart = [
            'total' => 100,
            'items' => [
                ['id' => 101, 'name' => 'T-shirt', 'price' => 100, 'quantity' => 1, 'category' => 'clothing'],
            ],
        ];
    }

    protected function tearDown(): void
    {
        // Remove only the keys this test could have created.
        foreach ([1, 2, 3] as $userId) {
            Redis::del($this->key($userId));
        }

        parent::tearDown();
    }

    private function key(int $userId): string
    {
        return "coupon:{$this->code}:user:{$userId}";
    }

    private function makeCoupon(int $usageLimit): Coupon
    {
        return Coupon::forceCreate([
            'code' => $this->code,
            'usage_limit' => $usageLimit,
            'per_user_limit' => 1,
            'min_cart_value' => 0,
            'used_count' => 0,
            'rule_version' => 'v1',
            'is_active' => true,
        ]);
    }

    public function test_same_user_requesting_twice_reserves_only_once(): void
    {
        $this->makeCoupon(10);

        $this->assertTrue($this->service->reserve(1, $this->code, $this->cart));
        $this->assertTrue($this->service->reserve(1, $this->code, $this->cart)); // idempotent

        $this->assertEquals(1, Redis::exists($this->key(1)));
        Bus::assertDispatchedTimes(UpdateCartJob::class, 1);
    }

    public function test_global_limit_blocks_second_user_when_one_use_remains(): void
    {
        $this->makeCoupon(1);

        $this->assertTrue($this->service->reserve(1, $this->code, $this->cart));
        $this->assertFalse($this->service->reserve(2, $this->code, $this->cart));

        $this->assertEquals(0, Redis::exists($this->key(2)));
    }

    public function test_consume_without_reservation_is_rejected(): void
    {
        $coupon = $this->makeCoupon(10);

        $this->assertFalse($this->service->consume(1, $this->code));
        $this->assertEquals(0, $coupon->fresh()->used_count);
    }

    public function test_consume_removes_reservation_and_increments_used_count(): void
    {
        $coupon = $this->makeCoupon(10);

        $this->service->reserve(1, $this->code, $this->cart);
        $this->assertTrue($this->service->consume(1, $this->code));

        $this->assertEquals(0, Redis::exists($this->key(1)));
        $this->assertEquals(1, $coupon->fresh()->used_count);
    }

    public function test_release_frees_the_slot_for_another_user(): void
    {
        $this->makeCoupon(1);

        $this->assertTrue($this->service->reserve(1, $this->code, $this->cart));
        $this->assertTrue($this->service->release(1, $this->code));
        $this->assertTrue($this->service->reserve(2, $this->code, $this->cart));
    }
}
