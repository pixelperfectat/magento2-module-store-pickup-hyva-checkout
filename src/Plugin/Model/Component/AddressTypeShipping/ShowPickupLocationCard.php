<?php declare(strict_types=1);

namespace PixelPerfect\StorePickupHyvaCheckout\Plugin\Model\Component\AddressTypeShipping;

use Hyva\Checkout\Model\Component\AddressTypeShipping;
use Magento\Framework\View\LayoutInterface;
use PixelPerfect\StorePickupHyvaCheckout\ViewModel\PickupLocationView;

/**
 * Renders the pickup location card instead of the shipping address form or list for a pickup quote.
 *
 * Hyvä's address view renders whatever block getComponentViewBlock() returns before it falls back to the form.
 */
class ShowPickupLocationCard
{
    public const string CARD_BLOCK_NAME = 'checkout.shipping-details.pp-pickup';

    /**
     * Constructor.
     *
     * @param PickupLocationView $view
     * @param LayoutInterface $layout
     */
    public function __construct(
        private readonly PickupLocationView $view,
        private readonly LayoutInterface $layout
    ) {
    }

    /**
     * Swap in the card when the session quote ships to an active pickup location.
     *
     * @param AddressTypeShipping $subject
     * @param mixed $result
     * @return mixed
     */
    public function afterGetComponentViewBlock(AddressTypeShipping $subject, mixed $result): mixed
    {
        if ($this->view->getSelectedLocation() === null) {
            return $result;
        }

        $card = $this->layout->getBlock(self::CARD_BLOCK_NAME);

        return $card === false ? $result : $card;
    }
}
