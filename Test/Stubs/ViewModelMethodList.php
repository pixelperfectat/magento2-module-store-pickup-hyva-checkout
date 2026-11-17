<?php declare(strict_types=1);

namespace Hyva\Checkout\ViewModel\Checkout\Shipping;

use Hyva\Checkout\Model\ShippingMethodMetaData;
use Magento\Framework\View\Element\AbstractBlock;
use Magento\Framework\View\Element\Template;
use Magento\Quote\Api\Data\ShippingMethodInterface;

/**
 * Stand-in for the commercial Hyvä Checkout shipping method list view model, which is not installable in CI.
 * Declares only what the plugins under test and PHPStan need; loaded by Test/bootstrap.php when the real class is absent.
 */
class MethodList
{
    /**
     * @param Template $block
     * @param ShippingMethodInterface $method
     * @return false|AbstractBlock
     */
    public function getAdditionalViewBlock(Template $block, ShippingMethodInterface $method)
    {
        return $block->getChildBlock($method->getCarrierCode() . '_' . $method->getMethodCode());
    }

    /**
     * @param Template $block
     * @param ShippingMethodInterface $method
     * @return bool
     */
    public function hasAdditionalView(Template $block, ShippingMethodInterface $method): bool
    {
        $search = $this->getAdditionalViewBlock($block, $method);

        return $search && $search->getTemplate();
    }

    /**
     * @param Template $parent
     * @param ShippingMethodInterface $method
     * @return ShippingMethodMetaData
     */
    public function getMethodMetaData(Template $parent, ShippingMethodInterface $method): ShippingMethodMetaData
    {
        return new ShippingMethodMetaData();
    }
}
