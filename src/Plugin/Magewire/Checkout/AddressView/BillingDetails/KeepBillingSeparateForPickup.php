<?php declare(strict_types=1);

namespace PixelPerfect\StorePickupHyvaCheckout\Plugin\Magewire\Checkout\AddressView\BillingDetails;

use Hyva\Checkout\Magewire\Checkout\AddressView\BillingDetails;
use PixelPerfect\StorePickupHyvaCheckout\ViewModel\PickupLocationView;

/**
 * Keeps "billing same as shipping" off for a pickup quote, so the pickup location is never copied into billing.
 *
 * The billing address stays a required, separate address.
 */
class KeepBillingSeparateForPickup
{
    /**
     * Constructor.
     *
     * @param PickupLocationView $view
     */
    public function __construct(private readonly PickupLocationView $view)
    {
    }

    /**
     * Force the toggle off for a pickup quote.
     *
     * @param BillingDetails $subject
     * @param bool $value
     * @return array{0: bool}
     */
    public function beforeUpdatedBillingAsShipping(BillingDetails $subject, bool $value): array
    {
        return [$this->view->getSelectedLocation() === null ? $value : false];
    }
}
