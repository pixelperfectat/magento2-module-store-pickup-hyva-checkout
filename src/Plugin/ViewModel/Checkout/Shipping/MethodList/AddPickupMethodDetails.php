<?php declare(strict_types=1);

namespace PixelPerfect\StorePickupHyvaCheckout\Plugin\ViewModel\Checkout\Shipping\MethodList;

use Hyva\Checkout\ViewModel\Checkout\Shipping\MethodList;
use Magento\Framework\View\Element\Template;
use Magento\Quote\Api\Data\ShippingMethodInterface;
use PixelPerfect\StorePickup\Api\Service\PickupMethodResolverInterface;

/**
 * Shows the location details under each pickup rate of the shipping method list.
 */
class AddPickupMethodDetails
{
    public const string CHILD_ALIAS = 'pppickup';

    /**
     * Swap in the details block for a pickup rate.
     *
     * @param MethodList $subject
     * @param mixed $result
     * @param Template $block
     * @param ShippingMethodInterface $method
     * @return mixed
     */
    public function afterGetAdditionalViewBlock(
        MethodList $subject,
        mixed $result,
        Template $block,
        ShippingMethodInterface $method
    ): mixed {
        // Hyvä looks children up by the static alias <carrier>_<method>, which cannot match a dynamic location code.
        if ($method->getCarrierCode() !== PickupMethodResolverInterface::CARRIER_CODE) {
            return $result;
        }

        $details = $block->getChildBlock(self::CHILD_ALIAS);
        if ($details === false) {
            return $result;
        }

        return $details->setData('pp_location_code', $method->getMethodCode());
    }
}
