<?php

namespace Tests\Unit\Services;

use App\Models\TradeIn;
use App\Models\TradeInBasePrice;
use App\Services\TradeInPricingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TradeInPricingServiceTest extends TestCase
{
    use RefreshDatabase;

    protected TradeInPricingService $pricingService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->pricingService = new TradeInPricingService();
    }

    /**
     * Test getting base price for device.
     */
    public function test_get_base_price_with_exact_match(): void
    {
        TradeInBasePrice::create([
            'brand' => 'Apple',
            'model' => 'iPhone 14',
            'storage' => '128GB',
            'base_price' => 30000,
            'active' => true,
        ]);

        $basePrice = $this->pricingService->getBasePrice('Apple', 'iPhone 14', '128GB');

        $this->assertEquals(30000, $basePrice);
    }

    /**
     * Test getting base price without storage.
     */
    public function test_get_base_price_without_storage(): void
    {
        TradeInBasePrice::create([
            'brand' => 'Apple',
            'model' => 'iPhone 14',
            'storage' => '',
            'base_price' => 28000,
            'active' => true,
        ]);

        $basePrice = $this->pricingService->getBasePrice('Apple', 'iPhone 14', null);

        $this->assertEquals(28000, $basePrice);
    }

    /**
     * Test getting base price returns null when not found.
     */
    public function test_get_base_price_returns_null_when_not_found(): void
    {
        $basePrice = $this->pricingService->getBasePrice('Unknown', 'Unknown Model', '128GB');

        $this->assertNull($basePrice);
    }

    /**
     * Test calculating price with condition A.
     */
    public function test_calculate_price_condition_a(): void
    {
        $result = $this->pricingService->calculatePrice(30000, 'A');

        $this->assertEquals(30000, $result['calculated']);
        $this->assertGreaterThan(0, $result['min']);
        $this->assertGreaterThan($result['min'], $result['max']);
    }

    /**
     * Test calculating price with condition B.
     */
    public function test_calculate_price_condition_b(): void
    {
        $result = $this->pricingService->calculatePrice(30000, 'B');

        // B = 0.85 multiplier
        $expected = (int) round(30000 * 0.85);
        $this->assertEquals($expected, $result['calculated']);
    }

    /**
     * Test calculating price with condition C.
     */
    public function test_calculate_price_condition_c(): void
    {
        $result = $this->pricingService->calculatePrice(30000, 'C');

        // C = 0.70 multiplier
        $expected = (int) round(30000 * 0.70);
        $this->assertEquals($expected, $result['calculated']);
    }

    /**
     * Test calculating price with battery health.
     */
    public function test_calculate_price_with_battery_health(): void
    {
        $resultWithoutBattery = $this->pricingService->calculatePrice(30000, 'B');
        $resultWithBattery = $this->pricingService->calculatePrice(30000, 'B', 80);

        // Price with battery health should be lower
        $this->assertLessThan($resultWithoutBattery['calculated'], $resultWithBattery['calculated']);
    }

    /**
     * Test price range with margin.
     */
    public function test_calculate_price_has_margin(): void
    {
        $result = $this->pricingService->calculatePrice(30000, 'B');

        // Min should be 10% less, max should be 10% more
        $margin = 0.10;
        $calculated = $result['calculated'];
        $expectedMin = (int) round($calculated * (1 - $margin));
        $expectedMax = (int) round($calculated * (1 + $margin));

        $this->assertEquals($expectedMin, $result['min']);
        $this->assertEquals($expectedMax, $result['max']);
    }

    /**
     * Test applying manual override.
     */
    public function test_apply_manual_override(): void
    {
        $tradeIn = TradeIn::factory()->create([
            'final_price' => null,
        ]);

        $updated = $this->pricingService->applyManualOverride($tradeIn->id, 25000);

        $this->assertEquals(25000, $updated->final_price);
    }

    /**
     * Test full trade-in price calculation.
     */
    public function test_calculate_tradein_price_full_flow(): void
    {
        TradeInBasePrice::create([
            'brand' => 'Apple',
            'model' => 'iPhone 14',
            'storage' => '128GB',
            'base_price' => 30000,
            'active' => true,
        ]);

        $result = $this->pricingService->calculateTradeInPrice(
            'Apple',
            'iPhone 14',
            '128GB',
            'B',
            85
        );

        $this->assertNotNull($result['base_price']);
        $this->assertEquals(30000, $result['base_price']);
        $this->assertGreaterThan(0, $result['min']);
        $this->assertGreaterThan(0, $result['max']);
        $this->assertGreaterThan(0, $result['calculated']);
    }

    /**
     * Test calculate trade-in price returns zeros when base price not found.
     */
    public function test_calculate_tradein_price_returns_zeros_when_not_found(): void
    {
        $result = $this->pricingService->calculateTradeInPrice(
            'Unknown',
            'Unknown Model',
            '128GB',
            'B',
            85
        );

        $this->assertNull($result['base_price']);
        $this->assertEquals(0, $result['min']);
        $this->assertEquals(0, $result['max']);
        $this->assertEquals(0, $result['calculated']);
    }
}

