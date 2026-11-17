<?php declare(strict_types=1);

namespace PixelPerfect\StorePickupHyvaCheckout\Plugin\ViewModel\Checkout\Shipping\MethodList;

use Hyva\Checkout\Model\ShippingMethodMetaData;
use Hyva\Checkout\ViewModel\Checkout\Shipping\MethodList;
use Magento\Framework\View\Element\Template;
use Magento\Quote\Api\Data\ShippingMethodInterface;
use PixelPerfect\StorePickup\Api\Service\PickupMethodResolverInterface;
use PixelPerfect\StorePickupHyvaCheckout\ViewModel\PickupIcon;
use Psr\Log\LoggerInterface;

/**
 * Marks the pickup rates of the shipping method list with the pickup icon.
 *
 * Hyvä reads the rate's icon from the metadata of a child block named <carrier>_<method>, which cannot match a
 * dynamic location code, so the icon is added to the metadata here.
 */
class AddPickupMethodIcon
{
    /**
     * Constructor.
     *
     * @param PickupIcon $pickupIcon
     * @param LoggerInterface $logger
     */
    public function __construct(
        private readonly PickupIcon $pickupIcon,
        private readonly LoggerInterface $logger
    ) {
    }

    /**
     * Set the pickup icon on a pickup rate that has none; an icon from layout metadata wins.
     *
     * @param MethodList $subject
     * @param ShippingMethodMetaData $result
     * @param Template $parent
     * @param ShippingMethodInterface $method
     * @return ShippingMethodMetaData
     */
    public function afterGetMethodMetaData(
        MethodList $subject,
        ShippingMethodMetaData $result,
        Template $parent,
        ShippingMethodInterface $method
    ): ShippingMethodMetaData {
        if ($method->getCarrierCode() !== PickupMethodResolverInterface::CARRIER_CODE || $result->hasData('icon')) {
            return $result;
        }

        try {
            // The array form lets the icon be hidden from screen readers; the plain string form would add a title.
            $result->setData(
                'icon',
                ['svg' => $this->pickupIcon->getIconPath(), 'attributes' => ['aria-hidden' => 'true']]
            );
        } catch (\Throwable $exception) {
            $this->logger->error(
                'Store pickup: adding the icon to the pickup rate failed: ' . $exception->getMessage(),
                ['exception' => $exception]
            );
        }

        return $result;
    }
}
