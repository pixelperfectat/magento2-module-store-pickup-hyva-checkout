<?php declare(strict_types=1);

namespace Hyva\Checkout\Model;

use Magento\Framework\DataObject;

/**
 * Stand-in for the commercial Hyvä Checkout shipping method meta data, which is not installable in CI.
 * Loaded by Test/bootstrap.php when the real class is absent.
 */
class ShippingMethodMetaData extends DataObject
{
}
