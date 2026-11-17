<?php declare(strict_types=1);

namespace PixelPerfect\StorePickupHyvaCheckout\Service;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;
use PixelPerfect\StorePickupHyvaCheckout\Api\Service\RateDisplayConfigInterface;

/**
 * Reads the rate display settings of the "Hyvä Checkout" group in the store pickup configuration.
 */
class RateDisplayConfig implements RateDisplayConfigInterface
{
    private const array MODES = [self::MODE_LABEL, self::MODE_GROUPS, self::MODE_NONE];

    /**
     * Constructor.
     *
     * @param ScopeConfigInterface $scopeConfig
     */
    public function __construct(
        private readonly ScopeConfigInterface $scopeConfig
    ) {
    }

    /**
     * @inheritDoc
     */
    public function getMode(?int $storeId = null): string
    {
        $mode = $this->scopeConfig->getValue(self::XML_PATH_MODE, ScopeInterface::SCOPE_STORE, $storeId);

        return in_array($mode, self::MODES, true) ? $mode : self::MODE_LABEL;
    }

    /**
     * @inheritDoc
     */
    public function getRateLabel(?int $storeId = null): string
    {
        return $this->text(self::XML_PATH_RATE_LABEL, $storeId) ?? (string) __('In-store pickup');
    }

    /**
     * @inheritDoc
     */
    public function getPickupGroupTitle(?int $storeId = null): string
    {
        return $this->text(self::XML_PATH_PICKUP_GROUP_TITLE, $storeId) ?? (string) __('In-store pickup');
    }

    /**
     * @inheritDoc
     */
    public function getShippingGroupTitle(?int $storeId = null): string
    {
        return $this->text(self::XML_PATH_SHIPPING_GROUP_TITLE, $storeId) ?? (string) __('Shipping');
    }

    /**
     * A trimmed text value, or null when it is empty.
     *
     * @param string $path
     * @param int|null $storeId
     * @return string|null
     */
    private function text(string $path, ?int $storeId): ?string
    {
        $value = trim((string) $this->scopeConfig->getValue($path, ScopeInterface::SCOPE_STORE, $storeId));

        return $value === '' ? null : $value;
    }
}
