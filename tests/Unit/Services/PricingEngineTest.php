<?php

namespace Tests\Unit\Services;

use App\Models\PriceMatrix;
use App\Services\PricingEngine;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PricingEngineTest extends TestCase
{
    use RefreshDatabase;

    protected PricingEngine $pricingEngine;

    protected function setUp(): void
    {
        parent::setUp();
        $this->pricingEngine = new PricingEngine();
    }

    /**
     * Test getting pricing when exact match exists.
     */
    public function test_get_pricing_returns_price_when_exact_match_exists(): void
    {
        // Create a price matrix entry
        $priceMatrix = PriceMatrix::create([
            'brand' => 'Apple',
            'model' => 'iPhone 14',
            'problem_type' => 'Screen Repair',
            'price_min' => 1500,
            'price_max' => 3000,
        ]);

        $result = $this->pricingEngine->getPricing('Apple', 'iPhone 14', 'Screen Repair');

        $this->assertFalse($result['needs_manual_quote']);
        $this->assertEquals(1500, $result['price_min']);
        $this->assertEquals(3000, $result['price_max']);
    }

    /**
     * Test getting pricing when no match exists.
     */
    public function test_get_pricing_returns_needs_manual_quote_when_no_match(): void
    {
        // Create a different price matrix entry
        PriceMatrix::create([
            'brand' => 'Samsung',
            'model' => 'Galaxy S23',
            'problem_type' => 'Battery Replacement',
            'price_min' => 800,
            'price_max' => 1500,
        ]);

        $result = $this->pricingEngine->getPricing('Apple', 'iPhone 14', 'Screen Repair');

        $this->assertTrue($result['needs_manual_quote']);
        $this->assertNull($result['price_min']);
        $this->assertNull($result['price_max']);
    }

    /**
     * Test case-insensitive matching.
     */
    public function test_get_pricing_is_case_insensitive(): void
    {
        PriceMatrix::create([
            'brand' => 'Apple',
            'model' => 'iPhone 14',
            'problem_type' => 'Screen Repair',
            'price_min' => 1500,
            'price_max' => 3000,
        ]);

        // Test with different cases
        $result1 = $this->pricingEngine->getPricing('APPLE', 'iphone 14', 'screen repair');
        $result2 = $this->pricingEngine->getPricing('apple', 'iPhone 14', 'SCREEN REPAIR');
        $result3 = $this->pricingEngine->getPricing('Apple', 'iPhone 14', 'Screen Repair');

        $this->assertFalse($result1['needs_manual_quote']);
        $this->assertEquals(1500, $result1['price_min']);
        $this->assertEquals(3000, $result1['price_max']);

        $this->assertFalse($result2['needs_manual_quote']);
        $this->assertEquals(1500, $result2['price_min']);
        $this->assertEquals(3000, $result2['price_max']);

        $this->assertFalse($result3['needs_manual_quote']);
        $this->assertEquals(1500, $result3['price_min']);
        $this->assertEquals(3000, $result3['price_max']);
    }

    /**
     * Test whitespace handling.
     */
    public function test_get_pricing_handles_whitespace(): void
    {
        PriceMatrix::create([
            'brand' => 'Apple',
            'model' => 'iPhone 14',
            'problem_type' => 'Screen Repair',
            'price_min' => 1500,
            'price_max' => 3000,
        ]);

        // Test with extra whitespace
        $result = $this->pricingEngine->getPricing('  Apple  ', '  iPhone 14  ', '  Screen Repair  ');

        $this->assertFalse($result['needs_manual_quote']);
        $this->assertEquals(1500, $result['price_min']);
        $this->assertEquals(3000, $result['price_max']);
    }

    /**
     * Test partial match does not return pricing.
     */
    public function test_get_pricing_requires_exact_match(): void
    {
        PriceMatrix::create([
            'brand' => 'Apple',
            'model' => 'iPhone 14',
            'problem_type' => 'Screen Repair',
            'price_min' => 1500,
            'price_max' => 3000,
        ]);

        // Test partial matches - should not match
        $result1 = $this->pricingEngine->getPricing('Apple', 'iPhone 14 Pro', 'Screen Repair');
        $result2 = $this->pricingEngine->getPricing('Apple', 'iPhone 14', 'Battery Replacement');
        $result3 = $this->pricingEngine->getPricing('Samsung', 'iPhone 14', 'Screen Repair');

        $this->assertTrue($result1['needs_manual_quote']);
        $this->assertTrue($result2['needs_manual_quote']);
        $this->assertTrue($result3['needs_manual_quote']);
    }

    /**
     * Test hasPricing returns true when pricing exists.
     */
    public function test_has_pricing_returns_true_when_pricing_exists(): void
    {
        PriceMatrix::create([
            'brand' => 'Apple',
            'model' => 'iPhone 14',
            'problem_type' => 'Screen Repair',
            'price_min' => 1500,
            'price_max' => 3000,
        ]);

        $hasPricing = $this->pricingEngine->hasPricing('Apple', 'iPhone 14', 'Screen Repair');

        $this->assertTrue($hasPricing);
    }

    /**
     * Test hasPricing returns false when pricing does not exist.
     */
    public function test_has_pricing_returns_false_when_pricing_does_not_exist(): void
    {
        PriceMatrix::create([
            'brand' => 'Samsung',
            'model' => 'Galaxy S23',
            'problem_type' => 'Battery Replacement',
            'price_min' => 800,
            'price_max' => 1500,
        ]);

        $hasPricing = $this->pricingEngine->hasPricing('Apple', 'iPhone 14', 'Screen Repair');

        $this->assertFalse($hasPricing);
    }

    /**
     * Test multiple price matrix entries with same brand/model but different problem types.
     */
    public function test_get_pricing_matches_specific_problem_type(): void
    {
        PriceMatrix::create([
            'brand' => 'Apple',
            'model' => 'iPhone 14',
            'problem_type' => 'Screen Repair',
            'price_min' => 1500,
            'price_max' => 3000,
        ]);

        PriceMatrix::create([
            'brand' => 'Apple',
            'model' => 'iPhone 14',
            'problem_type' => 'Battery Replacement',
            'price_min' => 800,
            'price_max' => 1500,
        ]);

        $result1 = $this->pricingEngine->getPricing('Apple', 'iPhone 14', 'Screen Repair');
        $result2 = $this->pricingEngine->getPricing('Apple', 'iPhone 14', 'Battery Replacement');

        $this->assertFalse($result1['needs_manual_quote']);
        $this->assertEquals(1500, $result1['price_min']);
        $this->assertEquals(3000, $result1['price_max']);

        $this->assertFalse($result2['needs_manual_quote']);
        $this->assertEquals(800, $result2['price_min']);
        $this->assertEquals(1500, $result2['price_max']);
    }

    /**
     * Test empty database returns needs manual quote.
     */
    public function test_get_pricing_returns_needs_manual_quote_when_database_is_empty(): void
    {
        $result = $this->pricingEngine->getPricing('Apple', 'iPhone 14', 'Screen Repair');

        $this->assertTrue($result['needs_manual_quote']);
        $this->assertNull($result['price_min']);
        $this->assertNull($result['price_max']);
    }

    /**
     * Test with different brands and models.
     */
    public function test_get_pricing_with_various_brands_and_models(): void
    {
        $testCases = [
            ['brand' => 'Apple', 'model' => 'iPhone 14', 'problem' => 'Screen Repair', 'min' => 1500, 'max' => 3000],
            ['brand' => 'Samsung', 'model' => 'Galaxy S23', 'problem' => 'Battery Replacement', 'min' => 800, 'max' => 1500],
            ['brand' => 'Xiaomi', 'model' => 'Redmi Note 12', 'problem' => 'Charging Port', 'min' => 600, 'max' => 1200],
        ];

        foreach ($testCases as $testCase) {
            PriceMatrix::create([
                'brand' => $testCase['brand'],
                'model' => $testCase['model'],
                'problem_type' => $testCase['problem'],
                'price_min' => $testCase['min'],
                'price_max' => $testCase['max'],
            ]);

            $result = $this->pricingEngine->getPricing(
                $testCase['brand'],
                $testCase['model'],
                $testCase['problem']
            );

            $this->assertFalse($result['needs_manual_quote'], "Failed for {$testCase['brand']} {$testCase['model']}");
            $this->assertEquals($testCase['min'], $result['price_min']);
            $this->assertEquals($testCase['max'], $result['price_max']);
        }
    }
}

