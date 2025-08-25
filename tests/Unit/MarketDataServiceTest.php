<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Services\Trading\MarketDataService;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

#[Group('market')]
#[Group('crypto')]
class MarketDataServiceTest extends TestCase
{
    private MarketDataService $marketData;

    protected function setUp(): void
    {
        parent::setUp();
        $this->marketData = new MarketDataService;
    }

    #[Test]
    public function get_best_price_returns_realistic_crypto_prices()
    {
        $btcPrice = $this->marketData->getBestPrice('BTCUSDT');
        $ethPrice = $this->marketData->getBestPrice('ETHUSDT');
        $solPrice = $this->marketData->getBestPrice('SOLUSDT');
        $xrpPrice = $this->marketData->getBestPrice('XRPUSDT');

        $this->assertEquals(50000.0, $btcPrice);
        $this->assertEquals(3000.0, $ethPrice);
        $this->assertEquals(100.0, $solPrice);
        $this->assertEquals(0.6, $xrpPrice);

        // Test unknown symbol returns default
        $unknownPrice = $this->marketData->getBestPrice('UNKNOWNUSDT');
        $this->assertEquals(1.0, $unknownPrice);
    }

    #[Test]
    public function current_price_includes_realistic_spread()
    {
        $btcBest = $this->marketData->getBestPrice('BTCUSDT');
        $btcCurrent = $this->marketData->getCurrentPrice('BTCUSDT');

        // Current price should be slightly higher (0.05% spread)
        $expectedSpread = $btcBest * 0.0005;
        $this->assertEquals($btcBest + $expectedSpread, $btcCurrent);

        // Verify spread is reasonable for crypto trading
        $spreadPercentage = (($btcCurrent - $btcBest) / $btcBest) * 100;
        $this->assertEquals(0.05, $spreadPercentage);
    }

    #[Test]
    public function volatility_reflects_crypto_market_characteristics()
    {
        $btcVolatility = $this->marketData->getVolatility('BTCUSDT');
        $ethVolatility = $this->marketData->getVolatility('ETHUSDT');
        $solVolatility = $this->marketData->getVolatility('SOLUSDT');
        $xrpVolatility = $this->marketData->getVolatility('XRPUSDT');

        // Bitcoin should have lower volatility than altcoins
        $this->assertEquals(0.02, $btcVolatility); // 2%
        $this->assertEquals(0.025, $ethVolatility); // 2.5%
        $this->assertEquals(0.04, $solVolatility); // 4% (higher vol altcoin)
        $this->assertEquals(0.03, $xrpVolatility); // 3%

        // SOL should be more volatile than BTC
        $this->assertGreaterThan($btcVolatility, $solVolatility);

        // Default volatility for unknown symbols
        $defaultVol = $this->marketData->getVolatility('NEWCOINUSDT');
        $this->assertEquals(0.01, $defaultVol);
    }

    #[Test]
    public function liquidity_scores_reflect_market_depth()
    {
        $btcLiquidity = $this->marketData->getLiquidityScore('BTCUSDT');
        $ethLiquidity = $this->marketData->getLiquidityScore('ETHUSDT');
        $solLiquidity = $this->marketData->getLiquidityScore('SOLUSDT');
        $xrpLiquidity = $this->marketData->getLiquidityScore('XRPUSDT');

        // Bitcoin should have highest liquidity
        $this->assertEquals(0.95, $btcLiquidity);
        $this->assertEquals(0.90, $ethLiquidity);
        $this->assertEquals(0.75, $solLiquidity);
        $this->assertEquals(0.70, $xrpLiquidity);

        // All scores should be between 0 and 1
        $this->assertGreaterThanOrEqual(0, $btcLiquidity);
        $this->assertLessThanOrEqual(1, $btcLiquidity);

        // BTC should have better liquidity than smaller caps
        $this->assertGreaterThan($solLiquidity, $btcLiquidity);
        $this->assertGreaterThan($xrpLiquidity, $ethLiquidity);
    }

    #[Test]
    public function spread_calculation_is_accurate()
    {
        $btcSpread = $this->marketData->getSpread('BTCUSDT');
        $ethSpread = $this->marketData->getSpread('ETHUSDT');

        $btcBest = $this->marketData->getBestPrice('BTCUSDT');
        $btcCurrent = $this->marketData->getCurrentPrice('BTCUSDT');
        $expectedBtcSpread = abs($btcCurrent - $btcBest);

        $this->assertEquals($expectedBtcSpread, $btcSpread);

        // Spread should be positive
        $this->assertGreaterThan(0, $btcSpread);
        $this->assertGreaterThan(0, $ethSpread);
    }

    #[Test]
    public function order_book_depth_provides_realistic_levels()
    {
        $btcOrderBook = $this->marketData->getOrderBookDepth('BTCUSDT', 5);

        $this->assertIsArray($btcOrderBook);
        $this->assertArrayHasKey('bids', $btcOrderBook);
        $this->assertArrayHasKey('asks', $btcOrderBook);
        $this->assertArrayHasKey('timestamp', $btcOrderBook);

        // Should have 5 levels each side
        $this->assertCount(5, $btcOrderBook['bids']);
        $this->assertCount(5, $btcOrderBook['asks']);

        // Check bid structure
        $firstBid = $btcOrderBook['bids'][0];
        $this->assertArrayHasKey('price', $firstBid);
        $this->assertArrayHasKey('quantity', $firstBid);
        $this->assertIsFloat($firstBid['price']);
        $this->assertIsFloat($firstBid['quantity']);

        // Bids should be descending price order
        for ($i = 0; $i < 4; $i++) {
            $this->assertGreaterThan(
                $btcOrderBook['bids'][$i + 1]['price'],
                $btcOrderBook['bids'][$i]['price']
            );
        }

        // Asks should be ascending price order
        for ($i = 0; $i < 4; $i++) {
            $this->assertLessThan(
                $btcOrderBook['asks'][$i + 1]['price'],
                $btcOrderBook['asks'][$i]['price']
            );
        }
    }

    #[Test]
    public function order_book_different_depths_work()
    {
        $shallowBook = $this->marketData->getOrderBookDepth('ETHUSDT', 3);
        $deepBook = $this->marketData->getOrderBookDepth('ETHUSDT', 10);

        $this->assertCount(3, $shallowBook['bids']);
        $this->assertCount(3, $shallowBook['asks']);

        $this->assertCount(10, $deepBook['bids']);
        $this->assertCount(10, $deepBook['asks']);
    }

    #[Test]
    public function all_crypto_symbols_have_consistent_data()
    {
        $symbols = ['BTCUSDT', 'ETHUSDT', 'SOLUSDT', 'XRPUSDT'];

        foreach ($symbols as $symbol) {
            $price = $this->marketData->getBestPrice($symbol);
            $currentPrice = $this->marketData->getCurrentPrice($symbol);
            $volatility = $this->marketData->getVolatility($symbol);
            $liquidity = $this->marketData->getLiquidityScore($symbol);
            $spread = $this->marketData->getSpread($symbol);

            // All values should be positive
            $this->assertGreaterThan(0, $price, "Price should be positive for {$symbol}");
            $this->assertGreaterThan(0, $currentPrice, "Current price should be positive for {$symbol}");
            $this->assertGreaterThan(0, $volatility, "Volatility should be positive for {$symbol}");
            $this->assertGreaterThan(0, $liquidity, "Liquidity should be positive for {$symbol}");
            $this->assertGreaterThanOrEqual(0, $spread, "Spread should be non-negative for {$symbol}");

            // Current price should be slightly higher than best price (spread)
            $this->assertGreaterThan($price, $currentPrice, "Current price should include spread for {$symbol}");

            // Liquidity score should be between 0 and 1
            $this->assertLessThanOrEqual(1, $liquidity, "Liquidity should be <= 1 for {$symbol}");

            // Volatility should be reasonable for crypto (< 50%)
            $this->assertLessThan(0.5, $volatility, "Volatility should be < 50% for {$symbol}");
        }
    }

    #[Test]
    public function price_relationships_are_logical()
    {
        $btcPrice = $this->marketData->getBestPrice('BTCUSDT');
        $ethPrice = $this->marketData->getBestPrice('ETHUSDT');
        $solPrice = $this->marketData->getBestPrice('SOLUSDT');
        $xrpPrice = $this->marketData->getBestPrice('XRPUSDT');

        // BTC should be most expensive
        $this->assertGreaterThan($ethPrice, $btcPrice);
        $this->assertGreaterThan($solPrice, $btcPrice);
        $this->assertGreaterThan($xrpPrice, $btcPrice);

        // ETH should be second most expensive
        $this->assertGreaterThan($solPrice, $ethPrice);
        $this->assertGreaterThan($xrpPrice, $ethPrice);

        // SOL should be more expensive than XRP
        $this->assertGreaterThan($xrpPrice, $solPrice);
    }

    #[Test]
    public function market_data_supports_trading_decisions()
    {
        $symbol = 'BTCUSDT';

        $price = $this->marketData->getBestPrice($symbol);
        $volatility = $this->marketData->getVolatility($symbol);
        $liquidity = $this->marketData->getLiquidityScore($symbol);
        $orderBook = $this->marketData->getOrderBookDepth($symbol, 10);

        // Calculate market impact for a 1 BTC order
        $orderSize = 1.0;
        $availableLiquidity = array_sum(array_column($orderBook['asks'], 'quantity'));

        $this->assertGreaterThan($orderSize, $availableLiquidity);

        // High liquidity symbols should support larger orders
        $this->assertGreaterThan(0.8, $liquidity); // BTC should have high liquidity

        // Volatility should inform position sizing
        $volatilityBasedLeverage = min(20, 1 / $volatility); // Simple vol-based leverage
        $this->assertGreaterThan(10, $volatilityBasedLeverage); // BTC should allow decent leverage
    }

    #[Test]
    public function mock_data_is_deterministic()
    {
        // Multiple calls should return same values
        $price1 = $this->marketData->getBestPrice('BTCUSDT');
        $price2 = $this->marketData->getBestPrice('BTCUSDT');

        $this->assertEquals($price1, $price2);

        $vol1 = $this->marketData->getVolatility('ETHUSDT');
        $vol2 = $this->marketData->getVolatility('ETHUSDT');

        $this->assertEquals($vol1, $vol2);

        // This ensures consistent testing environment
    }
}
