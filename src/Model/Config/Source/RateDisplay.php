<?php declare(strict_types=1);

namespace PixelPerfect\StorePickupHyvaCheckout\Model\Config\Source;

use Magento\Framework\Data\OptionSourceInterface;
use PixelPerfect\StorePickupHyvaCheckout\Api\Service\RateDisplayConfigInterface;

/**
 * Options of the "Pickup Rates in the Shipping Method List" setting.
 */
class RateDisplay implements OptionSourceInterface
{
    /**
     * @inheritDoc
     *
     * @return array<int, array{value: string, label: \Magento\Framework\Phrase}>
     */
    public function toOptionArray(): array
    {
        return [
            ['value' => RateDisplayConfigInterface::MODE_LABEL, 'label' => __('Label above the pickup rates')],
            [
                'value' => RateDisplayConfigInterface::MODE_GROUPS,
                'label' => __('Separate groups for pickup and shipping'),
            ],
            ['value' => RateDisplayConfigInterface::MODE_NONE, 'label' => __('None (Hyvä default list)')],
        ];
    }
}
