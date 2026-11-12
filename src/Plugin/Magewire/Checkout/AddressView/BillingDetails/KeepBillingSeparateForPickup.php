<?php declare(strict_types=1);

namespace PixelPerfect\StorePickupHyvaCheckout\Plugin\Magewire\Checkout\AddressView\BillingDetails;

use Hyva\Checkout\Magewire\Checkout\AddressView\BillingDetails;
use PixelPerfect\StorePickupHyvaCheckout\ViewModel\PickupLocationView;
use Psr\Log\LoggerInterface;

/**
 * Keeps "billing same as shipping" off for a pickup quote, so the pickup location is never copied into billing.
 *
 * The original method must not run: for a guest or a customer without a primary billing address it imports the
 * shipping address (the pickup location) into the billing address and saves the quote, whatever the value. The
 * billing address stays a required, separate address; Magewire writes the returned false back to the checkbox.
 */
class KeepBillingSeparateForPickup
{
    /**
     * Constructor.
     *
     * @param PickupLocationView $view
     * @param LoggerInterface|null $logger
     */
    public function __construct(
        private readonly PickupLocationView $view,
        private readonly ?LoggerInterface $logger = null
    ) {
    }

    /**
     * Skip the original and answer false for a pickup quote; otherwise run it.
     *
     * @param BillingDetails $subject
     * @param callable $proceed
     * @param bool $value
     * @return bool
     */
    public function aroundUpdatedBillingAsShipping(BillingDetails $subject, callable $proceed, bool $value): bool
    {
        try {
            if ($this->view->getSelectedLocation() !== null) {
                return false;
            }
        } catch (\Throwable $exception) {
            $this->logger?->error(
                'Store pickup: checking the pickup quote for the billing toggle failed: ' . $exception->getMessage(),
                ['exception' => $exception]
            );
        }

        return $proceed($value);
    }
}
