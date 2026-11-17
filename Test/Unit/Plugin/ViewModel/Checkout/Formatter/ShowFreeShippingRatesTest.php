<?php declare(strict_types=1);

namespace PixelPerfect\StorePickupHyvaCheckout\Test\Unit\Plugin\ViewModel\Checkout\Formatter;

use Hyva\Checkout\ViewModel\Checkout\Formatter;
use Magento\Framework\Phrase;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use PixelPerfect\StorePickupHyvaCheckout\Plugin\ViewModel\Checkout\Formatter\ShowFreeShippingRates;

class ShowFreeShippingRatesTest extends TestCase
{
    private Formatter&MockObject $subject;

    protected function setUp(): void
    {
        $this->subject = $this->createMock(Formatter::class);
    }

    /**
     * @param array<string|int, callable> $conditions
     * @return callable
     */
    private function addedCondition(array $conditions, array $before = []): callable
    {
        $added = array_diff_key($conditions, $before);
        $this->assertCount(1, $added);

        return reset($added);
    }

    private function freeCondition(): callable
    {
        $plugin = new ShowFreeShippingRates();

        return $this->addedCondition($plugin->afterFilterCurrencyConditions($this->subject, []));
    }

    public function testAZeroAmountInTheShippingListBecomesFree(): void
    {
        $condition = $this->freeCondition();

        $result = $condition(0.0, 'shipping-list-item', []);

        $this->assertInstanceOf(Phrase::class, $result);
        $this->assertSame('Free', (string) $result);
    }

    public function testAFeeKeepsItsAmount(): void
    {
        $condition = $this->freeCondition();

        $this->assertSame(2.5, $condition(2.5, 'shipping-list-item', []));
    }

    public function testAZeroAmountInAnotherAreaKeepsItsAmount(): void
    {
        $condition = $this->freeCondition();

        $this->assertSame(0.0, $condition(0.0, 'global', []));
    }

    public function testExistingConditionsAreKept(): void
    {
        $existing = static fn (float $amount): float => $amount;

        $result = (new ShowFreeShippingRates())->afterFilterCurrencyConditions($this->subject, ['other' => $existing]);

        $this->assertSame($existing, $result['other']);
        $this->assertCount(2, $result);
    }

    public function testDisabledLeavesTheConditionsUnchanged(): void
    {
        $existing = ['other' => static fn (float $amount): float => $amount];

        $this->assertSame(
            $existing,
            (new ShowFreeShippingRates(false))->afterFilterCurrencyConditions($this->subject, $existing)
        );
    }
}
