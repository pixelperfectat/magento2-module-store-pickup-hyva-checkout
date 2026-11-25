<?php declare(strict_types=1);

namespace Magewirephp\Magewire;

/**
 * Stand-in for the Magewire base component, which ships with Hyvä Checkout and is not installable in CI.
 * Declares only what the plugins under test and PHPStan need; loaded by Test/bootstrap.php when the real class is absent.
 */
abstract class Component
{
    /** @var array<string, string> */
    protected $listeners = [];

    /**
     * @return array<int|string, string>
     */
    public function getListeners(): array
    {
        return $this->listeners;
    }
}
