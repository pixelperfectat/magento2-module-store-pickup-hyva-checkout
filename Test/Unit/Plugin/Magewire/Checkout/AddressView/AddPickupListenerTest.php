<?php declare(strict_types=1);

namespace PixelPerfect\StorePickupHyvaCheckout\Test\Unit\Plugin\Magewire\Checkout\AddressView;

use Hyva\Checkout\Magewire\Checkout\AddressView\BillingDetails;
use Hyva\Checkout\Magewire\Checkout\AddressView\ShippingDetails;
use PHPUnit\Framework\TestCase;
use PixelPerfect\StorePickupHyvaCheckout\Plugin\Magewire\Checkout\AddressView\AddPickupListener;
use PixelPerfect\StorePickupHyvaCheckout\Plugin\Magewire\Checkout\Shipping\MethodList\SyncPickupAddress;

class AddPickupListenerTest extends TestCase
{
    public function testTheEventNameIsTheOneTheSyncPluginEmits(): void
    {
        $result = (new AddPickupListener())->afterGetListeners(new ShippingDetails(), []);

        $this->assertSame([SyncPickupAddress::EVENT_PICKUP_CHANGED => 'refresh'], $result);
        $this->assertArrayHasKey('pp_pickup_changed', $result);
    }

    public function testExistingListenersAreKept(): void
    {
        $subject = new BillingDetails();
        $existing = $subject->getListeners();

        $result = (new AddPickupListener())->afterGetListeners($subject, $existing);

        $this->assertSame('refresh', $result['pp_pickup_changed']);
        foreach ($existing as $event => $method) {
            $this->assertSame($method, $result[$event]);
        }
        $this->assertCount(count($existing) + 1, $result);
    }
}
