<?php declare(strict_types=1);

namespace PixelPerfect\StorePickupHyvaCheckout\Plugin\ViewModel\Checkout\Formatter;

use Hyva\Checkout\ViewModel\Checkout\Formatter;
use Magento\Framework\Phrase;

/**
 * Shows "Free" instead of a zero amount on the rates of the shipping method list.
 *
 * Hyvä's formatter lets a condition replace the formatted price. The condition does not see the shipping method,
 * so it applies to every zero-priced rate in the list, not only to pickup rates.
 */
class ShowFreeShippingRates
{
    public const string AREA = 'shipping-list-item';

    /**
     * Constructor.
     *
     * @param bool $enabled
     */
    public function __construct(
        private readonly bool $enabled = true
    ) {
    }

    /**
     * Add the condition that labels a zero amount in the shipping list as free.
     *
     * @param Formatter $subject
     * @param array<string|int, callable> $result
     * @return array<string|int, callable>
     */
    public function afterFilterCurrencyConditions(Formatter $subject, array $result): array
    {
        if (!$this->enabled) {
            return $result;
        }

        $result['pp_store_pickup_free_shipping'] = static fn (float $amount, string $area): float|Phrase =>
            $area === self::AREA && $amount === 0.0 ? __('Free') : $amount;

        return $result;
    }
}
