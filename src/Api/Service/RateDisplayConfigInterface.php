<?php declare(strict_types=1);

namespace PixelPerfect\StorePickupHyvaCheckout\Api\Service;

/**
 * How the pickup rates are told apart in Hyvä Checkout's shipping method list, per store view.
 *
 * @api
 */
interface RateDisplayConfigInterface
{
    public const string XML_PATH_MODE = 'pp_store_pickup/hyva_checkout/rate_display';
    public const string XML_PATH_RATE_LABEL = 'pp_store_pickup/hyva_checkout/rate_label';
    public const string XML_PATH_PICKUP_GROUP_TITLE = 'pp_store_pickup/hyva_checkout/group_pickup_title';
    public const string XML_PATH_SHIPPING_GROUP_TITLE = 'pp_store_pickup/hyva_checkout/group_shipping_title';

    /** A small line above the title of each pickup rate. */
    public const string MODE_LABEL = 'label';

    /** The pickup rates and the other rates in two groups with a heading each. */
    public const string MODE_GROUPS = 'groups';

    /** Hyvä's own list, unchanged. */
    public const string MODE_NONE = 'none';

    /**
     * The display mode; an unknown or empty value falls back to the label mode.
     *
     * @param int|null $storeId
     * @return string One of the MODE_* constants.
     */
    public function getMode(?int $storeId = null): string;

    /**
     * The line above a pickup rate in the label mode, or the translated default "In-store pickup".
     *
     * @param int|null $storeId
     * @return string
     */
    public function getRateLabel(?int $storeId = null): string;

    /**
     * The heading of the pickup group, or the translated default "In-store pickup".
     *
     * @param int|null $storeId
     * @return string
     */
    public function getPickupGroupTitle(?int $storeId = null): string;

    /**
     * The heading of the group with all other rates, or the translated default "Shipping".
     *
     * @param int|null $storeId
     * @return string
     */
    public function getShippingGroupTitle(?int $storeId = null): string;
}
