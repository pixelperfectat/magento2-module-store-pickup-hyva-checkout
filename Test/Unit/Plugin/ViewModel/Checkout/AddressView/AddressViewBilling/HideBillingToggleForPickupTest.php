<?php declare(strict_types=1);

namespace PixelPerfect\StorePickupHyvaCheckout\Test\Unit\Plugin\ViewModel\Checkout\AddressView\AddressViewBilling;

use Hyva\Checkout\ViewModel\Checkout\AddressView\AddressViewBilling;
use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Quote\Model\Quote;
use Magento\Quote\Model\Quote\Address;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use PixelPerfect\StorePickup\Api\Service\PickupMethodResolverInterface;
use PixelPerfect\StorePickupHyvaCheckout\Plugin\ViewModel\Checkout\AddressView\AddressViewBilling\HideBillingToggleForPickup;
use Psr\Log\LoggerInterface;

class HideBillingToggleForPickupTest extends TestCase
{
    private CheckoutSession&MockObject $checkoutSession;
    private PickupMethodResolverInterface&MockObject $resolver;
    private LoggerInterface&MockObject $logger;
    private HideBillingToggleForPickup $plugin;

    protected function setUp(): void
    {
        $this->checkoutSession = $this->createMock(CheckoutSession::class);
        $this->resolver = $this->createMock(PickupMethodResolverInterface::class);
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->plugin = new HideBillingToggleForPickup($this->checkoutSession, $this->resolver, $this->logger);
    }

    public function testPickupReportsVirtualEvenThoughTheOriginalSaidNo(): void
    {
        $this->quoteWithMethod('pppickup_main', true);

        $this->assertTrue($this->plugin->afterIsVirtual(new AddressViewBilling(), false));
    }

    public function testDeliveryKeepsTheOriginalResult(): void
    {
        $this->quoteWithMethod('flatrate_flatrate', false);

        $this->assertFalse($this->plugin->afterIsVirtual(new AddressViewBilling(), false));
    }

    public function testVirtualQuoteStaysVirtual(): void
    {
        $this->quoteWithMethod('flatrate_flatrate', false);

        $this->assertTrue($this->plugin->afterIsVirtual(new AddressViewBilling(), true));
    }

    public function testAFailingQuoteAccessKeepsTheOriginalResultAndLogs(): void
    {
        $exception = new \RuntimeException('boom');
        $this->checkoutSession->method('getQuote')->willThrowException($exception);
        $this->logger->expects($this->exactly(2))->method('error')->with(
            'Store pickup: hiding the same-address billing toggle for a pickup quote failed: boom',
            ['exception' => $exception]
        );

        $this->assertFalse($this->plugin->afterIsVirtual(new AddressViewBilling(), false));
        $this->assertTrue($this->plugin->afterIsVirtual(new AddressViewBilling(), true));
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
