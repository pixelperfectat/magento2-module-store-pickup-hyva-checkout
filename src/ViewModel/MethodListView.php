<?php declare(strict_types=1);

namespace PixelPerfect\StorePickupHyvaCheckout\ViewModel;

use Magento\Framework\View\Element\Block\ArgumentInterface;
use Magento\Framework\View\Element\Template;
use Magento\Quote\Api\Data\ShippingMethodInterface;
use Magento\Tax\Helper\Data as TaxHelper;
use PixelPerfect\StorePickup\Api\Service\PickupMethodResolverInterface;
use PixelPerfect\StorePickupHyvaCheckout\Api\Service\RateDisplayConfigInterface;
use Psr\Log\LoggerInterface;

/**
 * The module's shipping method list: tells pickup rates from the others and exposes the rate display settings.
 *
 * Rates that are not available are left out, as in Hyvä's own list.
 */
class MethodListView implements ArgumentInterface
{
    /**
     * Constructor.
     *
     * @param RateDisplayConfigInterface $config
     * @param PickupMethodResolverInterface $pickupMethodResolver
     * @param TaxHelper $taxHelper
     * @param LoggerInterface $logger
     */
    public function __construct(
        private readonly RateDisplayConfigInterface $config,
        private readonly PickupMethodResolverInterface $pickupMethodResolver,
        private readonly TaxHelper $taxHelper,
        private readonly LoggerInterface $logger
    ) {
    }

    /**
     * The rate display mode of the current store view.
     *
     * @return string One of the RateDisplayConfigInterface::MODE_* constants.
     */
    public function getMode(): string
    {
        return $this->config->getMode();
    }

    /**
     * Whether the rates are shown in a pickup and a shipping group.
     *
     * @return bool
     */
    public function isGroupsMode(): bool
    {
        return $this->getMode() === RateDisplayConfigInterface::MODE_GROUPS;
    }

    /**
     * Whether the module's list replaces Hyvä's: in every mode but "none".
     *
     * Decided before the rates are estimated, so Hyvä's list in the "none" mode costs no extra estimate.
     *
     * @return bool
     */
    public function usesOwnList(): bool
    {
        return $this->getMode() !== RateDisplayConfigInterface::MODE_NONE;
    }

    /**
     * Whether the groups mode shows headings: only when both the pickup group and the other group have rates.
     *
     * @param ShippingMethodInterface[]|null $methods
     * @return bool
     */
    public function showsGroupHeadings(?array $methods): bool
    {
        return $this->isGroupsMode() && $this->getPickupRates($methods) !== [] && $this->getOtherRates($methods) !== [];
    }

    /**
     * The template file named by a block argument, or the default one; a template that cannot be found is logged.
     *
     * @param Template $block
     * @param string $argument
     * @param string $default
     * @return string
     */
    public function getTemplateFile(Template $block, string $argument, string $default): string
    {
        $configured = (string) $block->getData($argument);
        if ($configured !== '') {
            $file = $block->getTemplateFile($configured);
            if ($file) {
                return $file;
            }
            $this->logger->error(
                'Store pickup: the template "' . $configured . '" of the block argument "' . $argument
                . '" was not found; the default template is used.'
            );
        }

        return (string) $block->getTemplateFile($default);
    }

    /**
     * Whether the rate belongs to the pickup carrier.
     *
     * @param ShippingMethodInterface $method
     * @return bool
     */
    public function isPickupRate(ShippingMethodInterface $method): bool
    {
        return $this->pickupMethodResolver->isPickupMethod($method->getCarrierCode() . '_' . $method->getMethodCode());
    }

    /**
     * The available rates in the list's order.
     *
     * @param ShippingMethodInterface[]|null $methods
     * @return ShippingMethodInterface[]
     */
    public function getAvailableRates(?array $methods): array
    {
        return array_values(
            array_filter(
                $methods ?? [],
                static fn (ShippingMethodInterface $method): bool => (bool) $method->getAvailable()
            )
        );
    }

    /**
     * The available pickup rates in the list's order.
     *
     * @param ShippingMethodInterface[]|null $methods
     * @return ShippingMethodInterface[]
     */
    public function getPickupRates(?array $methods): array
    {
        return array_values(array_filter($this->getAvailableRates($methods), $this->isPickupRate(...)));
    }

    /**
     * The available rates that are not pickup rates, in the list's order.
     *
     * @param ShippingMethodInterface[]|null $methods
     * @return ShippingMethodInterface[]
     */
    public function getOtherRates(?array $methods): array
    {
        return array_values(
            array_filter(
                $this->getAvailableRates($methods),
                fn (ShippingMethodInterface $method): bool => !$this->isPickupRate($method)
            )
        );
    }

    /**
     * The line above a pickup rate in the label mode.
     *
     * @return string
     */
    public function getRateLabel(): string
    {
        return $this->config->getRateLabel();
    }

    /**
     * The heading of the pickup group.
     *
     * @return string
     */
    public function getPickupGroupTitle(): string
    {
        return $this->config->getPickupGroupTitle();
    }

    /**
     * The heading of the group with the other rates.
     *
     * @return string
     */
    public function getShippingGroupTitle(): string
    {
        return $this->config->getShippingGroupTitle();
    }

    /**
     * Whether the price including tax is shown, following the shipping price display setting.
     *
     * @return bool
     */
    public function showsPriceInclTax(): bool
    {
        return $this->taxHelper->displayShippingPriceIncludingTax() || $this->taxHelper->displayShippingBothPrices();
    }

    /**
     * Whether the price excluding tax is shown; with both prices only when it differs from the price including tax.
     *
     * @param ShippingMethodInterface $method
     * @return bool
     */
    public function showsPriceExclTax(ShippingMethodInterface $method): bool
    {
        return $this->taxHelper->displayShippingPriceExcludingTax()
            || ($this->showsBothPrices() && $method->getPriceExclTax() !== $method->getPriceInclTax());
    }

    /**
     * Whether both prices are shown, each with its label.
     *
     * @return bool
     */
    public function showsBothPrices(): bool
    {
        return $this->taxHelper->displayShippingBothPrices();
    }
}
