<?php declare(strict_types=1);

namespace PixelPerfect\StorePickupHyvaCheckout\Test\Unit\Plugin\Magewire\Checkout\AddressView\BillingDetails;

use Hyva\Checkout\Magewire\Checkout\AddressView\BillingDetails;
use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Quote\Model\Quote;
use Magento\Quote\Model\Quote\Address;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use PixelPerfect\StorePickup\Api\Service\PickupMethodResolverInterface;
use PixelPerfect\StorePickupHyvaCheckout\Plugin\Magewire\Checkout\AddressView\BillingDetails\KeepBillingSeparateForPickup;
use Psr\Log\LoggerInterface;

class KeepBillingSeparateForPickupTest extends TestCase
{
    private CheckoutSession&MockObject $checkoutSession;
    private PickupMethodResolverInterface&MockObject $resolver;
    private LoggerInterface&MockObject $logger;
    private KeepBillingSeparateForPickup $plugin;

    protected function setUp(): void
    {
        $this->checkoutSession = $this->createMock(CheckoutSession::class);
        $this->resolver = $this->createMock(PickupMethodResolverInterface::class);
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->plugin = new KeepBillingSeparateForPickup($this->checkoutSession, $this->resolver, $this->logger);
    }

    public function testPickupNeverRunsTheOriginalAndReturnsFalse(): void
    {
        $this->quoteWithMethod('pppickup_main', true);
        $calls = 0;
        $proceed = static function (bool $value) use (&$calls): bool {
            $calls++;

            return $value;
        };

        $this->assertFalse($this->plugin->aroundUpdatedBillingAsShipping(new BillingDetails(), $proceed, true));
        $this->assertSame(0, $calls);
    }

    public function testPickupMethodWithAnInactiveOrUnknownLocationStillNeverRunsTheOriginal(): void
    {
        // The guard is the shipping method alone: the location state (provider lookup) plays no part.
        $this->quoteWithMethod('pppickup_deactivated', true);
        $calls = 0;
        $proceed = static function (bool $value) use (&$calls): bool {
            $calls++;

            return $value;
        };

        $this->assertFalse($this->plugin->aroundUpdatedBillingAsShipping(new BillingDetails(), $proceed, true));
        $this->assertSame(0, $calls);
    }

    public function testDeliveryRunsTheOriginalWithTheValueAndReturnsItsResult(): void
    {
        $this->quoteWithMethod('flatrate_flatrate', false);
        $received = [];
        $proceed = static function (bool $value) use (&$received): bool {
            $received[] = $value;

            return !$value;
        };

        $this->assertFalse($this->plugin->aroundUpdatedBillingAsShipping(new BillingDetails(), $proceed, true));
        $this->assertTrue($this->plugin->aroundUpdatedBillingAsShipping(new BillingDetails(), $proceed, false));
        $this->assertSame([true, false], $received);
    }

    public function testAFailingPickupCheckFallsBackToTheOriginal(): void
    {
        $this->checkoutSession->method('getQuote')->willThrowException(new \RuntimeException('boom'));
        $this->logger->expects($this->once())->method('error')->with($this->stringContains('boom'));
        $received = [];
        $proceed = static function (bool $value) use (&$received): bool {
            $received[] = $value;

            return $value;
        };

        $this->assertTrue($this->plugin->aroundUpdatedBillingAsShipping(new BillingDetails(), $proceed, true));
        $this->assertSame([true], $received);
    }

    private function quoteWithMethod(string $method, bool $isPickup): void
    {
        $address = $this->createMock(Address::class);
        $address->method('getShippingMethod')->willReturn($method);
        $quote = $this->createMock(Quote::class);
        $quote->method('getShippingAddress')->willReturn($address);
        $this->checkoutSession->method('getQuote')->willReturn($quote);
        $this->resolver->method('isPickupMethod')->with($method)->willReturn($isPickup);
    }
}
