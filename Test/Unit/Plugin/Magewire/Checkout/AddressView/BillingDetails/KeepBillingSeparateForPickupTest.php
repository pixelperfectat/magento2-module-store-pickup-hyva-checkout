<?php declare(strict_types=1);

namespace PixelPerfect\StorePickupHyvaCheckout\Test\Unit\Plugin\Magewire\Checkout\AddressView\BillingDetails;

use Hyva\Checkout\Magewire\Checkout\AddressView\BillingDetails;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use PixelPerfect\StorePickup\Api\Data\PickupLocationInterface;
use PixelPerfect\StorePickupHyvaCheckout\Plugin\Magewire\Checkout\AddressView\BillingDetails\KeepBillingSeparateForPickup;
use PixelPerfect\StorePickupHyvaCheckout\ViewModel\PickupLocationView;
use Psr\Log\LoggerInterface;

class KeepBillingSeparateForPickupTest extends TestCase
{
    private PickupLocationView&MockObject $view;
    private KeepBillingSeparateForPickup $plugin;

    protected function setUp(): void
    {
        $this->view = $this->createMock(PickupLocationView::class);
        $this->plugin = new KeepBillingSeparateForPickup($this->view);
    }

    public function testPickupNeverRunsTheOriginalAndReturnsFalse(): void
    {
        $this->view->method('getSelectedLocation')->willReturn($this->createMock(PickupLocationInterface::class));
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
        $this->view->method('getSelectedLocation')->willReturn(null);
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
        $this->view->method('getSelectedLocation')->willThrowException(new \RuntimeException('boom'));
        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->once())->method('error')->with($this->stringContains('boom'));
        $plugin = new KeepBillingSeparateForPickup($this->view, $logger);
        $received = [];
        $proceed = static function (bool $value) use (&$received): bool {
            $received[] = $value;

            return $value;
        };

        $this->assertTrue($plugin->aroundUpdatedBillingAsShipping(new BillingDetails(), $proceed, true));
        $this->assertSame([true], $received);
    }
}
