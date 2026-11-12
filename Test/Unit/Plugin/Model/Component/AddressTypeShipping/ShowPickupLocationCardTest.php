<?php declare(strict_types=1);

namespace PixelPerfect\StorePickupHyvaCheckout\Test\Unit\Plugin\Model\Component\AddressTypeShipping;

use Hyva\Checkout\Model\Component\AddressTypeShipping;
use Magento\Framework\View\Element\BlockInterface;
use Magento\Framework\View\LayoutInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use PixelPerfect\StorePickup\Api\Data\PickupLocationInterface;
use PixelPerfect\StorePickupHyvaCheckout\Plugin\Model\Component\AddressTypeShipping\ShowPickupLocationCard;
use PixelPerfect\StorePickupHyvaCheckout\ViewModel\PickupLocationView;

class ShowPickupLocationCardTest extends TestCase
{
    private PickupLocationView&MockObject $view;
    private LayoutInterface&MockObject $layout;
    private AddressTypeShipping&MockObject $subject;
    private ShowPickupLocationCard $plugin;

    protected function setUp(): void
    {
        $this->view = $this->createMock(PickupLocationView::class);
        $this->layout = $this->createMock(LayoutInterface::class);
        $this->subject = $this->createMock(AddressTypeShipping::class);
        $this->plugin = new ShowPickupLocationCard($this->view, $this->layout);
    }

    public function testAPickupQuoteWithAnActiveLocationShowsTheCard(): void
    {
        $card = $this->createMock(BlockInterface::class);
        $this->view->method('getSelectedLocation')->willReturn($this->createMock(PickupLocationInterface::class));
        $this->layout->expects($this->once())->method('getBlock')
            ->with('checkout.shipping-details.pp-pickup')->willReturn($card);

        $this->assertSame($card, $this->plugin->afterGetComponentViewBlock($this->subject, false));
    }

    public function testWithoutASelectedLocationTheResultStaysUntouched(): void
    {
        // Covers virtual quotes, non-pickup methods, unknown codes and inactive locations: the view model yields null.
        $this->view->method('getSelectedLocation')->willReturn(null);
        $this->layout->expects($this->never())->method('getBlock');

        $this->assertFalse($this->plugin->afterGetComponentViewBlock($this->subject, false));

        $other = $this->createMock(BlockInterface::class);
        $this->assertSame($other, $this->plugin->afterGetComponentViewBlock($this->subject, $other));
    }

    public function testAMissingCardBlockKeepsTheOriginalResult(): void
    {
        $this->view->method('getSelectedLocation')->willReturn($this->createMock(PickupLocationInterface::class));
        $this->layout->method('getBlock')->willReturn(false);

        $this->assertFalse($this->plugin->afterGetComponentViewBlock($this->subject, false));
    }
}
