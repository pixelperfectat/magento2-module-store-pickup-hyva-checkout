<?php declare(strict_types=1);

namespace PixelPerfect\StorePickupHyvaCheckout\Test\Unit\Plugin\Magewire\Checkout\AddressView\BillingDetails;

use Hyva\Checkout\Magewire\Checkout\AddressView\BillingDetails;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use PixelPerfect\StorePickup\Api\Data\PickupLocationInterface;
use PixelPerfect\StorePickupHyvaCheckout\Plugin\Magewire\Checkout\AddressView\BillingDetails\KeepBillingSeparateForPickup;
use PixelPerfect\StorePickupHyvaCheckout\ViewModel\PickupLocationView;

class KeepBillingSeparateForPickupTest extends TestCase
{
    private PickupLocationView&MockObject $view;
    private KeepBillingSeparateForPickup $plugin;

    protected function setUp(): void
    {
        $this->view = $this->createMock(PickupLocationView::class);
        $this->plugin = new KeepBillingSeparateForPickup($this->view);
    }

    public function testPickupForcesBillingAsShippingOff(): void
    {
        $this->view->method('getSelectedLocation')->willReturn($this->createMock(PickupLocationInterface::class));

        $this->assertSame([false], $this->plugin->beforeUpdatedBillingAsShipping(new BillingDetails(), true));
    }

    public function testDeliveryKeepsTheValue(): void
    {
        $this->view->method('getSelectedLocation')->willReturn(null);

        $this->assertSame([true], $this->plugin->beforeUpdatedBillingAsShipping(new BillingDetails(), true));
        $this->assertSame([false], $this->plugin->beforeUpdatedBillingAsShipping(new BillingDetails(), false));
    }
}
