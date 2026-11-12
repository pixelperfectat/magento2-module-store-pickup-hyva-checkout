<?php declare(strict_types=1);

namespace Hyva\Checkout\Magewire\Checkout\Shipping;

/**
 * Stand-in for the commercial Hyvä Checkout shipping method list component, which is not installable in CI.
 * Declares only what the plugins under test and PHPStan need; loaded by Test/bootstrap.php when the real class is absent.
 */
class MethodList
{
    /**
     * @param string $value
     * @return string
     */
    public function updatedMethod(string $value): string
    {
        return $value;
    }

    /**
     * @param string $event
     * @param array<string, mixed> $params
     * @return mixed
     */
    public function emit(string $event, array $params = [])
    {
        return null;
    }
}
