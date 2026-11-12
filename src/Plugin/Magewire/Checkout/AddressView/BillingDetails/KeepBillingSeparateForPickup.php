<?php declare(strict_types=1);

namespace PixelPerfect\StorePickupHyvaCheckout\Plugin\Magewire\Checkout\AddressView\BillingDetails;

use Hyva\Checkout\Magewire\Checkout\AddressView\BillingDetails;
use Magento\Checkout\Model\Session as CheckoutSession;
use PixelPerfect\StorePickup\Api\Service\PickupMethodResolverInterface;
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
     * @param CheckoutSession $checkoutSession
     * @param PickupMethodResolverInterface $methodResolver
     * @param LoggerInterface $logger
     */
    public function __construct(
        private readonly CheckoutSession $checkoutSession,
        private readonly PickupMethodResolverInterface $methodResolver,
        private readonly LoggerInterface $logger
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
            // The shipping method alone decides, so a location deactivated mid-checkout cannot slip through.
            $method = $this->checkoutSession->getQuote()->getShippingAddress()->getShippingMethod();
            if ($this->methodResolver->isPickupMethod($method)) {
                return false;
            }
        } catch (\Throwable $exception) {
            $this->logger->error(
                'Store pickup: checking the pickup quote for the billing toggle failed: ' . $exception->getMessage(),
                ['exception' => $exception]
            );
        }

        return $proceed($value);
    }
}
