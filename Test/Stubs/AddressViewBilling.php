<?php declare(strict_types=1);

namespace Hyva\Checkout\ViewModel\Checkout\AddressView;

/**
 * Stand-in for the commercial Hyvä Checkout billing address view model, which is not installable in CI.
 * Declares only what the plugins under test and PHPStan need; loaded by Test/bootstrap.php when the real class is absent.
 */
class AddressViewBilling
{
    /**
     * @return bool
     */
    public function isVirtual(): bool
    {
        return false;
    }
}
