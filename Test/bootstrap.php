<?php declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

// Hyvä Checkout is commercial and only suggested: use a stand-in unless it is installed.
if (!class_exists(\Hyva\Checkout\Magewire\Checkout\Shipping\MethodList::class)) {
    require __DIR__ . '/Stubs/MethodList.php';
}

if (!class_exists(\Hyva\Checkout\ViewModel\Checkout\Shipping\MethodList::class)) {
    require __DIR__ . '/Stubs/ViewModelMethodList.php';
}

if (!class_exists(\Magewirephp\Magewire\Model\Element\Event::class)) {
    require __DIR__ . '/Stubs/Event.php';
}

if (!class_exists(\Magewirephp\Magewire\Component::class)) {
    require __DIR__ . '/Stubs/Component.php';
}

if (!class_exists(\Hyva\Checkout\Model\Component\AddressTypeShipping::class)) {
    require __DIR__ . '/Stubs/AddressTypeShipping.php';
}

if (!class_exists(\Hyva\Checkout\Magewire\Checkout\AddressView\ShippingDetails::class)) {
    require __DIR__ . '/Stubs/ShippingDetails.php';
}

if (!class_exists(\Hyva\Checkout\Magewire\Checkout\AddressView\BillingDetails::class)) {
    require __DIR__ . '/Stubs/BillingDetails.php';
}

if (!class_exists(\Hyva\Checkout\ViewModel\Checkout\AddressView\AddressViewBilling::class)) {
    require __DIR__ . '/Stubs/AddressViewBilling.php';
}

if (!class_exists(\Hyva\Theme\ViewModel\SvgIcons::class)) {
    require __DIR__ . '/Stubs/SvgIcons.php';
}

if (!class_exists(\Hyva\Checkout\Model\ShippingMethodMetaData::class)) {
    require __DIR__ . '/Stubs/ShippingMethodMetaData.php';
}

// Generated factories only exist in a generated Magento installation.
if (!class_exists(\Magento\Directory\Model\CountryFactory::class)) {
    require __DIR__ . '/Stubs/CountryFactory.php';
}
