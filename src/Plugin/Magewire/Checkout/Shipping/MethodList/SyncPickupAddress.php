<?php declare(strict_types=1);

namespace PixelPerfect\StorePickupHyvaCheckout\Plugin\Magewire\Checkout\Shipping\MethodList;

use Hyva\Checkout\Magewire\Checkout\Shipping\MethodList;
use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Quote\Api\Data\CartInterface;
use Magento\Quote\Model\Quote;
use PixelPerfect\StorePickup\Api\Service\PickupAddressApplierInterface;
use Psr\Log\LoggerInterface;

/**
 * Keeps the shipping address in line with the shipping method chosen in the Hyvä Checkout method list.
 *
 * Runs after the component has saved the method. When the pickup location changed (selected, deselected or
 * switched), the payment methods and totals refresh and the pickup location card follows.
 */
class SyncPickupAddress
{
    /**
     * Event the payment method list and the totals listen to.
     */
    public const string EVENT_ADDRESS_SAVED = 'shipping_address_saved';

    /**
     * Event the pickup location card listens to.
     */
    public const string EVENT_PICKUP_CHANGED = 'pp_pickup_changed';

    /**
     * Constructor.
     *
     * @param CartRepositoryInterface $cartRepository
     * @param CheckoutSession $checkoutSession
     * @param PickupAddressApplierInterface $addressApplier
     * @param LoggerInterface $logger
     */
    public function __construct(
        private readonly CartRepositoryInterface $cartRepository,
        private readonly CheckoutSession $checkoutSession,
        private readonly PickupAddressApplierInterface $addressApplier,
        private readonly LoggerInterface $logger
    ) {
    }

    /**
     * Sync the pickup address with the saved shipping method and tell the checkout when the location changed.
     *
     * Never throws: a failure is logged and the checkout carries on with the method the customer chose.
     *
     * @param MethodList $subject
     * @param string $result
     * @param string $value
     * @return string
     */
    public function afterUpdatedMethod(MethodList $subject, string $result, string $value): string
    {
        $quoteId = (int) $this->checkoutSession->getQuoteId();
        if ($quoteId === 0) {
            return $result;
        }

        try {
            $quote = $this->cartRepository->getActive($quoteId);
            $before = $this->locationCode($quote);
            $this->addressApplier->sync($quote);
            $changed = $this->locationCode($quote) !== $before;
        } catch (\Throwable $exception) {
            $this->logger->error(
                'Store pickup: syncing the pickup address after the shipping method change failed: '
                . $exception->getMessage(),
                ['exception' => $exception]
            );

            return $result;
        }

        if ($changed) {
            try {
                $subject->emit(self::EVENT_ADDRESS_SAVED);
                $subject->emit(self::EVENT_PICKUP_CHANGED);
            } catch (\Throwable $exception) {
                $this->logger->error(
                    'Store pickup: refreshing the checkout after the pickup location changed failed: '
                    . $exception->getMessage(),
                    ['exception' => $exception]
                );
            }
        }

        return $result;
    }

    /**
     * The pickup location code stored on the quote.
     *
     * @param CartInterface $quote
     * @return string|null
     */
    private function locationCode(CartInterface $quote): ?string
    {
        $code = $quote instanceof Quote
            ? $quote->getData(PickupAddressApplierInterface::LOCATION_CODE_FIELD)
            : null;

        return is_string($code) && $code !== '' ? $code : null;
    }
}
