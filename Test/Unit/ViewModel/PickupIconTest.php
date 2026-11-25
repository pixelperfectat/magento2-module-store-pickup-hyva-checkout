<?php declare(strict_types=1);

namespace PixelPerfect\StorePickupHyvaCheckout\Test\Unit\ViewModel;

use Hyva\Theme\ViewModel\SvgIcons;
use Magento\Framework\View\Asset\File\NotFoundException;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use PixelPerfect\StorePickupHyvaCheckout\ViewModel\PickupIcon;
use Psr\Log\LoggerInterface;

class PickupIconTest extends TestCase
{
    private SvgIcons&MockObject $svgIcons;
    private LoggerInterface&MockObject $logger;

    protected function setUp(): void
    {
        $this->svgIcons = $this->createMock(SvgIcons::class);
        $this->logger = $this->createMock(LoggerInterface::class);
    }

    public function testTheDefaultIconIsTheHeroiconsLocationMarker(): void
    {
        $icon = new PickupIcon($this->svgIcons, $this->logger);

        $this->assertSame('heroicons/outline/location-marker', $icon->getIconPath());
    }

    public function testTheIconPathCanBeOverriddenLikeFromDiXml(): void
    {
        $icon = new PickupIcon($this->svgIcons, $this->logger, 'my-icons/map-pin');

        $this->assertSame('my-icons/map-pin', $icon->getIconPath());
    }

    public function testTheIconIsRenderedThroughSvgIconsHiddenFromScreenReaders(): void
    {
        $this->svgIcons->expects($this->once())
            ->method('renderHtml')
            ->with('my-icons/map-pin', 'w-6 h-6 shrink-0', 24, 20, ['aria-hidden' => 'true'])
            ->willReturn('<svg/>');
        $icon = new PickupIcon($this->svgIcons, $this->logger, 'my-icons/map-pin');

        $this->assertSame('<svg/>', $icon->getIconHtml('w-6 h-6 shrink-0', 24, 20));
    }

    public function testSizeAndClassesAreOptional(): void
    {
        $this->svgIcons->expects($this->once())
            ->method('renderHtml')
            ->with('heroicons/outline/location-marker', '', null, null, ['aria-hidden' => 'true'])
            ->willReturn('<svg/>');

        $this->assertSame('<svg/>', (new PickupIcon($this->svgIcons, $this->logger))->getIconHtml());
    }

    public function testAMissingIconRendersNothingAndIsLogged(): void
    {
        $this->svgIcons->method('renderHtml')->willThrowException(new NotFoundException('no such icon'));
        $this->logger->expects($this->once())->method('warning')->with(
            'Store pickup: the pickup icon "no/such" could not be rendered: no such icon',
            $this->callback(static fn (array $context): bool => array_keys($context) === ['exception']
                && $context['exception'] instanceof \Throwable)
        );

        $this->assertSame('', (new PickupIcon($this->svgIcons, $this->logger, 'no/such'))->getIconHtml('w-5'));
    }
}
