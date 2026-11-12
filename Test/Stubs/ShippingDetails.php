<?php declare(strict_types=1);

namespace Hyva\Checkout\Magewire\Checkout\AddressView;

use Magewirephp\Magewire\Component;

/**
 * Stand-in for the commercial Hyvä Checkout shipping details component, which is not installable in CI.
 * Loaded by Test/bootstrap.php when the real class is absent.
 */
class ShippingDetails extends Component
{
    /** @var array<string, string> */
    protected $listeners = [
        'shipping_address_submitted' => 'refresh',
        'shipping_address_added' => 'refresh'
    ];
}
