<?php declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

// Hyvä Checkout is commercial and only suggested: use a stand-in unless it is installed.
if (!class_exists(\Hyva\Checkout\Magewire\Checkout\Shipping\MethodList::class)) {
    require __DIR__ . '/Stubs/MethodList.php';
}
