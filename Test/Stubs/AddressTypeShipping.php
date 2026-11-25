<?php declare(strict_types=1);

namespace Hyva\Checkout\Model\Component;

/**
 * Stand-in for the commercial Hyvä Checkout shipping address type, which is not installable in CI.
 * Declares only what the plugins under test and PHPStan need; loaded by Test/bootstrap.php when the real class is absent.
 */
class AddressTypeShipping
{
    /**
     * @return mixed
     */
    public function getComponentViewBlock()
    {
        return false;
    }
}
