<?php declare(strict_types=1);

namespace PixelPerfect\StorePickupHyvaCheckout\Test\Unit\Plugin\ViewModel\Checkout\Shipping\MethodList;

use Hyva\Checkout\Model\ShippingMethodMetaData;
use Hyva\Checkout\ViewModel\Checkout\Shipping\MethodList;
use Hyva\Theme\ViewModel\SvgIcons;
use Magento\Framework\View\Element\Template;
use Magento\Quote\Api\Data\ShippingMethodInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use PixelPerfect\StorePickupHyvaCheckout\Plugin\ViewModel\Checkout\Shipping\MethodList\AddPickupMethodIcon;
use PixelPerfect\StorePickupHyvaCheckout\ViewModel\PickupIcon;
use Psr\Log\LoggerInterface;

class AddPickupMethodIconTest extends TestCase
{
    private MethodList&MockObject $subject;
    private Template&MockObject $block;
    private PickupIcon&MockObject $pickupIcon;
    private LoggerInterface&MockObject $logger;
    private AddPickupMethodIcon $plugin;

    protected function setUp(): void
    {
        $this->subject = $this->createMock(MethodList::class);
        $this->block = $this->createMock(Template::class);
        $this->pickupIcon = $this->getMockBuilder(PickupIcon::class)
            ->setConstructorArgs([$this->createMock(SvgIcons::class), $this->createMock(LoggerInterface::class)])
            ->onlyMethods(['getIconPath'])
            ->getMock();
        $this->pickupIcon->method('getIconPath')->willReturn('heroicons/outline/location-marker');
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->plugin = new AddPickupMethodIcon($this->pickupIcon, $this->logger);
    }

    private function method(string $carrier, string $code): ShippingMethodInterface&MockObject
    {
        $method = $this->createMock(ShippingMethodInterface::class);
        $method->method('getCarrierCode')->willReturn($carrier);
        $method->method('getMethodCode')->willReturn($code);

        return $method;
    }

    public function testAPickupRateWithoutAnIconGetsThePickupIcon(): void
    {
        $result = new ShippingMethodMetaData();

        $returned = $this->plugin->afterGetMethodMetaData(
            $this->subject,
            $result,
            $this->block,
            $this->method('pppickup', 'main')
        );

        $this->assertSame($result, $returned);
        $this->assertSame(
            ['svg' => 'heroicons/outline/location-marker', 'attributes' => ['aria-hidden' => 'true']],
            $returned->getData('icon')
        );
    }

    public function testAnIconFromLayoutMetadataWins(): void
    {
        $result = new ShippingMethodMetaData(['icon' => ['svg' => 'my/own-icon']]);

        $returned = $this->plugin->afterGetMethodMetaData(
            $this->subject,
            $result,
            $this->block,
            $this->method('pppickup', 'main')
        );

        $this->assertSame(['svg' => 'my/own-icon'], $returned->getData('icon'));
    }

    public function testAnotherCarrierIsUntouched(): void
    {
        $result = new ShippingMethodMetaData();

        $returned = $this->plugin->afterGetMethodMetaData(
            $this->subject,
            $result,
            $this->block,
            $this->method('flatrate', 'flatrate')
        );

        $this->assertSame($result, $returned);
        $this->assertFalse($returned->hasData('icon'));
    }

    public function testAFailureIsLoggedAndTheResultReturned(): void
    {
        $pickupIcon = $this->createMock(PickupIcon::class);
        $pickupIcon->method('getIconPath')->willThrowException(new \RuntimeException('boom'));
        $this->logger->expects($this->once())->method('error')->with($this->stringContains('boom'));
        $plugin = new AddPickupMethodIcon($pickupIcon, $this->logger);
        $result = new ShippingMethodMetaData();

        $this->assertSame(
            $result,
            $plugin->afterGetMethodMetaData($this->subject, $result, $this->block, $this->method('pppickup', 'main'))
        );
        $this->assertFalse($result->hasData('icon'));
    }
}
