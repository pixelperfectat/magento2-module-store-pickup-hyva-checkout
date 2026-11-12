<?php declare(strict_types=1);

namespace Hyva\Checkout\Magewire\Checkout\AddressView;

use Magewirephp\Magewire\Component;

/**
 * Stand-in for the commercial Hyvä Checkout billing details component, which is not installable in CI.
 * Declares only what the plugins under test and PHPStan need; loaded by Test/bootstrap.php when the real class is absent.
 */
class BillingDetails extends Component
{
    /** @var array<string, string> */
    protected $listeners = [
        'billing_address_submitted' => 'refresh',
        'shipping_address_submitted' => 'refresh',
        'shipping_address_activated' => 'refresh'
    ];

    /**
     * @param bool $value
     * @return bool
     */
    public function updatedBillingAsShipping(bool $value): bool
    {
        return $value;
    }
}
