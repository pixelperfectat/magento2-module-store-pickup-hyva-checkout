<?php declare(strict_types=1);

namespace Hyva\Checkout\ViewModel\Checkout;

/**
 * Stand-in for the commercial Hyvä Checkout price formatter view model, which is not installable in CI.
 * Declares only what the plugins under test and PHPStan need; loaded by Test/bootstrap.php when the real class is absent.
 */
class Formatter
{
    /**
     * @param array<string|int, callable> $mergeWith
     * @return array<string|int, callable>
     */
    public function filterCurrencyConditions(array $mergeWith = []): array
    {
        return array_filter($mergeWith, 'is_callable');
    }
}
