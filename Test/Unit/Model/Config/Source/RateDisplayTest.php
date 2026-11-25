<?php declare(strict_types=1);

namespace PixelPerfect\StorePickupHyvaCheckout\Test\Unit\Model\Config\Source;

use PHPUnit\Framework\TestCase;
use PixelPerfect\StorePickupHyvaCheckout\Model\Config\Source\RateDisplay;

class RateDisplayTest extends TestCase
{
    public function testTheOptionsAreLabelGroupsAndNoneInThisOrder(): void
    {
        $options = (new RateDisplay())->toOptionArray();

        $this->assertSame(['label', 'groups', 'none'], array_column($options, 'value'));
        $this->assertSame(
            [
                'Label above the pickup rates',
                'Separate groups for pickup and shipping',
                'None (Hyvä default list)',
            ],
            array_map(static fn (array $option): string => (string) $option['label'], $options)
        );
    }
}
