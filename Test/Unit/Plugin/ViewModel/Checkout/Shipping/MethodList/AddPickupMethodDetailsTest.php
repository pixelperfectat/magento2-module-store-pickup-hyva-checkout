<?php declare(strict_types=1);

namespace PixelPerfect\StorePickupHyvaCheckout\Test\Unit\Plugin\ViewModel\Checkout\Shipping\MethodList;

use Hyva\Checkout\ViewModel\Checkout\Shipping\MethodList;
use Magento\Framework\View\Element\Template;
use Magento\Quote\Api\Data\ShippingMethodInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use PixelPerfect\StorePickupHyvaCheckout\Plugin\ViewModel\Checkout\Shipping\MethodList\AddPickupMethodDetails;

class AddPickupMethodDetailsTest extends TestCase
{
    private MethodList&MockObject $subject;
    private Template&MockObject $block;
    private AddPickupMethodDetails $plugin;

    protected function setUp(): void
    {
        $this->subject = $this->createMock(MethodList::class);
        $this->block = $this->createMock(Template::class);
        $this->plugin = new AddPickupMethodDetails();
    }

    private function method(string $carrier, string $code): ShippingMethodInterface&MockObject
    {
        $method = $this->createMock(ShippingMethodInterface::class);
        $method->method('getCarrierCode')->willReturn($carrier);
        $method->method('getMethodCode')->willReturn($code);

        return $method;
    }

    public function testAPickupRateGetsTheDetailsBlockCarryingItsLocationCode(): void
    {
        $child = $this->createMock(Template::class);
        $this->block->expects($this->once())->method('getChildBlock')->with('pppickup')->willReturn($child);
        $child->expects($this->once())->method('setData')->with('pp_location_code', 'main')->willReturnSelf();

        $this->assertSame(
            $child,
            $this->plugin->afterGetAdditionalViewBlock($this->subject, false, $this->block, $this->method('pppickup', 'main'))
        );
    }

    public function testAnotherCarrierKeepsTheOriginalResult(): void
    {
        $original = $this->createMock(Template::class);
        $this->block->expects($this->never())->method('getChildBlock');

        $this->assertSame(
            $original,
            $this->plugin->afterGetAdditionalViewBlock($this->subject, $original, $this->block, $this->method('flatrate', 'flatrate'))
        );
        $this->assertFalse(
            $this->plugin->afterGetAdditionalViewBlock($this->subject, false, $this->block, $this->method('flatrate', 'flatrate'))
        );
    }

    public function testAMissingDetailsBlockKeepsTheOriginalResult(): void
    {
        $this->block->method('getChildBlock')->with('pppickup')->willReturn(false);

        $this->assertFalse(
            $this->plugin->afterGetAdditionalViewBlock($this->subject, false, $this->block, $this->method('pppickup', 'main'))
        );
    }
}
