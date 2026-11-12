<?php declare(strict_types=1);

namespace PixelPerfect\StorePickupHyvaCheckout\Plugin\Magewire\Checkout\AddressView;

use Magewirephp\Magewire\Component;
use PixelPerfect\StorePickupHyvaCheckout\Plugin\Magewire\Checkout\Shipping\MethodList\SyncPickupAddress;

/**
 * Makes the shipping and billing details components re-render when the pickup location changes.
 *
 * Registered on both components, so the pickup card and the billing view follow a switch.
 */
class AddPickupListener
{
    /**
     * Add the pickup event to the component's listeners.
     *
     * @param Component $subject
     * @param array<int|string, string> $result
     * @return array<int|string, string>
     */
    public function afterGetListeners(Component $subject, array $result): array
    {
        $result[SyncPickupAddress::EVENT_PICKUP_CHANGED] = 'refresh';

        return $result;
    }
}
