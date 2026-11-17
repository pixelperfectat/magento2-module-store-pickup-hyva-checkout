<?php declare(strict_types=1);

namespace PixelPerfect\StorePickupHyvaCheckout\Plugin\ViewModel\Checkout\AddressView\AddressViewBilling;

use Hyva\Checkout\ViewModel\Checkout\AddressView\AddressViewBilling;
use Magento\Checkout\Model\Session as CheckoutSession;
use PixelPerfect\StorePickup\Api\Service\PickupMethodResolverInterface;
use Psr\Log\LoggerInterface;

/**
 * Hides the "billing same as shipping" toggle for a pickup quote.
 *
 * Hyvä Checkout uses isVirtual() of the billing view model only to decide whether to show that toggle, and for
 * pickup it does nothing useful: the shipping address is the pickup location, never a billing address. Reporting
 * the quote as virtual here therefore removes just the checkbox.
 */
class HideBillingToggleForPickup
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
     * Answer true for a pickup quote; otherwise keep the original result.
     *
     * @param AddressViewBilling $subject
     * @param bool $result
     * @return bool
     */
    public function afterIsVirtual(AddressViewBilling $subject, bool $result): bool
    {
        try {
            $method = $this->checkoutSession->getQuote()->getShippingAddress()->getShippingMethod();
            if ($this->methodResolver->isPickupMethod($method)) {
                return true;
            }
        } catch (\Throwable $exception) {
            $this->logger->error(
                'Store pickup: hiding the same-address billing toggle for a pickup quote failed: ' . $exception->getMessage(),
                ['exception' => $exception]
            );
        }

        return $result;
    }
}
