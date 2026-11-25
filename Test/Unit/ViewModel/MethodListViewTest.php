<?php declare(strict_types=1);

namespace PixelPerfect\StorePickupHyvaCheckout\Test\Unit\ViewModel;

use Magento\Framework\View\Element\Template;
use Magento\Quote\Api\Data\ShippingMethodInterface;
use Magento\Tax\Helper\Data as TaxHelper;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use PixelPerfect\StorePickup\Api\Service\PickupMethodResolverInterface;
use PixelPerfect\StorePickupHyvaCheckout\Api\Service\RateDisplayConfigInterface;
use PixelPerfect\StorePickupHyvaCheckout\ViewModel\MethodListView;
use Psr\Log\LoggerInterface;

class MethodListViewTest extends TestCase
{
    private RateDisplayConfigInterface&MockObject $config;
    private PickupMethodResolverInterface&MockObject $resolver;
    private TaxHelper&MockObject $taxHelper;
    private LoggerInterface&MockObject $logger;
    private MethodListView $view;

    protected function setUp(): void
    {
        $this->config = $this->createMock(RateDisplayConfigInterface::class);
        $this->resolver = $this->createMock(PickupMethodResolverInterface::class);
        $this->resolver->method('isPickupMethod')
            ->willReturnCallback(static fn (?string $method): bool => str_starts_with((string) $method, 'pppickup_'));
        $this->taxHelper = $this->createMock(TaxHelper::class);
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->view = new MethodListView($this->config, $this->resolver, $this->taxHelper, $this->logger);
    }

    private function rate(
        string $carrier,
        string $method,
        bool $available = true,
        float $inclTax = 0.0,
        float $exclTax = 0.0
    ): ShippingMethodInterface&MockObject {
        $rate = $this->createMock(ShippingMethodInterface::class);
        $rate->method('getCarrierCode')->willReturn($carrier);
        $rate->method('getMethodCode')->willReturn($method);
        $rate->method('getAvailable')->willReturn($available);
        $rate->method('getPriceInclTax')->willReturn($inclTax);
        $rate->method('getPriceExclTax')->willReturn($exclTax);

        return $rate;
    }

    public function testTheModeAndTextsComeFromTheConfig(): void
    {
        $this->config->method('getMode')->willReturn('groups');
        $this->config->method('getRateLabel')->willReturn('Click & Collect');
        $this->config->method('getPickupGroupTitle')->willReturn('Collect');
        $this->config->method('getShippingGroupTitle')->willReturn('Delivery');

        $this->assertSame('groups', $this->view->getMode());
        $this->assertTrue($this->view->isGroupsMode());
        $this->assertSame('Click & Collect', $this->view->getRateLabel());
        $this->assertSame('Collect', $this->view->getPickupGroupTitle());
        $this->assertSame('Delivery', $this->view->getShippingGroupTitle());
    }

    public function testTheLabelModeIsNotTheGroupsMode(): void
    {
        $this->config->method('getMode')->willReturn('label');

        $this->assertFalse($this->view->isGroupsMode());
    }

    public function testARateIsAPickupRateByItsFullMethodCode(): void
    {
        $this->assertTrue($this->view->isPickupRate($this->rate('pppickup', 'main')));
        $this->assertFalse($this->view->isPickupRate($this->rate('flatrate', 'flatrate')));
    }

    public function testThePickupCheckUsesTheResolverWithCarrierAndMethod(): void
    {
        $resolver = $this->createMock(PickupMethodResolverInterface::class);
        $resolver->expects($this->once())->method('isPickupMethod')->with('pppickup_second')->willReturn(true);
        $view = new MethodListView($this->config, $resolver, $this->taxHelper, $this->logger);

        $this->assertTrue($view->isPickupRate($this->rate('pppickup', 'second')));
    }

    public function testTheListIsSplitIntoPickupAndOtherRatesKeepingTheOrder(): void
    {
        $flat = $this->rate('flatrate', 'flatrate');
        $main = $this->rate('pppickup', 'main');
        $free = $this->rate('freeshipping', 'freeshipping');
        $second = $this->rate('pppickup', 'second');
        $list = [3 => $flat, 7 => $main, 9 => $free, 11 => $second];

        $this->assertSame([$main, $second], $this->view->getPickupRates($list));
        $this->assertSame([$flat, $free], $this->view->getOtherRates($list));
        $this->assertSame([$flat, $main, $free, $second], $this->view->getAvailableRates($list));
    }

    public function testUnavailableRatesAreLeftOutLikeInHyvasList(): void
    {
        $flat = $this->rate('flatrate', 'flatrate');
        $brokenPickup = $this->rate('pppickup', 'closed', false);
        $brokenFlat = $this->rate('tablerate', 'bestway', false);
        $main = $this->rate('pppickup', 'main');
        $list = [$flat, $brokenPickup, $brokenFlat, $main];

        $this->assertSame([$main], $this->view->getPickupRates($list));
        $this->assertSame([$flat], $this->view->getOtherRates($list));
        $this->assertSame([$flat, $main], $this->view->getAvailableRates($list));
    }

    public function testARateWithoutAnAvailabilityFlagIsLeftOut(): void
    {
        $rate = $this->createMock(ShippingMethodInterface::class);
        $rate->method('getAvailable')->willReturn(null);

        $this->assertSame([], $this->view->getAvailableRates([$rate]));
    }

    public function testEmptyGroupsAreEmptyLists(): void
    {
        $this->assertSame([], $this->view->getPickupRates([$this->rate('flatrate', 'flatrate')]));
        $this->assertSame([], $this->view->getOtherRates([$this->rate('pppickup', 'main')]));
        $this->assertSame([], $this->view->getPickupRates(null));
        $this->assertSame([], $this->view->getOtherRates([]));
        $this->assertSame([], $this->view->getAvailableRates(null));
    }

    /**
     * @return array<string, array{string, bool}>
     */
    public static function modes(): array
    {
        return [
            'label' => ['label', true],
            'groups' => ['groups', true],
            'none' => ['none', false],
        ];
    }

    /**
     * @dataProvider modes
     */
    public function testTheOwnListIsUsedUnlessTheModeIsNone(string $mode, bool $own): void
    {
        $this->config->method('getMode')->willReturn($mode);

        $this->assertSame($own, $this->view->usesOwnList());
    }

    public function testGroupHeadingsShowWhenBothGroupsHaveRates(): void
    {
        $this->config->method('getMode')->willReturn('groups');

        $this->assertTrue(
            $this->view->showsGroupHeadings([$this->rate('flatrate', 'flatrate'), $this->rate('pppickup', 'main')])
        );
    }

    public function testGroupHeadingsAreLeftOutWhenOnlyOneGroupHasRates(): void
    {
        $this->config->method('getMode')->willReturn('groups');

        $this->assertFalse($this->view->showsGroupHeadings([$this->rate('flatrate', 'flatrate')]));
        $this->assertFalse($this->view->showsGroupHeadings([$this->rate('pppickup', 'main')]));
        $this->assertFalse(
            $this->view->showsGroupHeadings([$this->rate('flatrate', 'flatrate'), $this->rate('pppickup', 'x', false)])
        );
        $this->assertFalse($this->view->showsGroupHeadings([]));
        $this->assertFalse($this->view->showsGroupHeadings(null));
    }

    public function testGroupHeadingsShowOnlyInTheGroupsMode(): void
    {
        $this->config->method('getMode')->willReturn('label');

        $this->assertFalse(
            $this->view->showsGroupHeadings([$this->rate('flatrate', 'flatrate'), $this->rate('pppickup', 'main')])
        );
    }

    public function testTheDefaultTemplateIsUsedWithoutABlockArgument(): void
    {
        $block = $this->createMock(Template::class);
        $block->method('getData')->with('pp_method_item_template')->willReturn(null);
        $block->expects($this->once())->method('getTemplateFile')
            ->with('Vendor_Module::item.phtml')->willReturn('/path/item.phtml');

        $this->assertSame(
            '/path/item.phtml',
            $this->view->getTemplateFile($block, 'pp_method_item_template', 'Vendor_Module::item.phtml')
        );
    }

    public function testABlockArgumentReplacesTheDefaultTemplate(): void
    {
        $block = $this->createMock(Template::class);
        $block->method('getData')->with('pp_method_item_template')->willReturn('Theme_Module::mine.phtml');
        $block->expects($this->once())->method('getTemplateFile')
            ->with('Theme_Module::mine.phtml')->willReturn('/path/mine.phtml');
        $this->logger->expects($this->never())->method('error');

        $this->assertSame(
            '/path/mine.phtml',
            $this->view->getTemplateFile($block, 'pp_method_item_template', 'Vendor_Module::item.phtml')
        );
    }

    public function testAMissingArgumentTemplateIsLoggedAndTheDefaultIsUsed(): void
    {
        $block = $this->createMock(Template::class);
        $block->method('getData')->willReturn('Theme_Module::missing.phtml');
        $block->method('getTemplateFile')->willReturnMap([
            ['Theme_Module::missing.phtml', false],
            ['Vendor_Module::item.phtml', '/path/item.phtml'],
        ]);
        $this->logger->expects($this->once())->method('error')
            ->with(
                'Store pickup: the template "Theme_Module::missing.phtml" of the block argument '
                . '"pp_method_item_template" was not found; the default template is used.'
            );

        $this->assertSame(
            '/path/item.phtml',
            $this->view->getTemplateFile($block, 'pp_method_item_template', 'Vendor_Module::item.phtml')
        );
    }

    /**
     * @return array<string, array{bool, bool, bool, bool}>
     */
    public static function inclTaxSettings(): array
    {
        return [
            'incl' => [true, false, false, true],
            'both' => [false, false, true, true],
            'excl' => [false, true, false, false],
        ];
    }

    /**
     * @dataProvider inclTaxSettings
     */
    public function testThePriceInclTaxFollowsTheTaxDisplaySettings(
        bool $incl,
        bool $excl,
        bool $both,
        bool $expected
    ): void {
        $this->taxHelper->method('displayShippingPriceIncludingTax')->willReturn($incl);
        $this->taxHelper->method('displayShippingPriceExcludingTax')->willReturn($excl);
        $this->taxHelper->method('displayShippingBothPrices')->willReturn($both);

        $this->assertSame($expected, $this->view->showsPriceInclTax());
        $this->assertSame($both, $this->view->showsBothPrices());
    }

    public function testThePriceExclTaxShowsWhenConfiguredAlone(): void
    {
        $this->taxHelper->method('displayShippingPriceExcludingTax')->willReturn(true);
        $this->taxHelper->method('displayShippingBothPrices')->willReturn(false);

        $this->assertTrue($this->view->showsPriceExclTax($this->rate('flatrate', 'flatrate', true, 6.0, 5.0)));
    }

    public function testWithBothPricesTheExclTaxPriceShowsOnlyWhenItDiffers(): void
    {
        $this->taxHelper->method('displayShippingPriceExcludingTax')->willReturn(false);
        $this->taxHelper->method('displayShippingBothPrices')->willReturn(true);

        $this->assertTrue($this->view->showsPriceExclTax($this->rate('flatrate', 'flatrate', true, 6.0, 5.0)));
        $this->assertFalse($this->view->showsPriceExclTax($this->rate('pppickup', 'main', true, 0.0, 0.0)));
    }

    public function testTheExclTaxPriceIsHiddenWhenOnlyInclTaxIsShown(): void
    {
        $this->taxHelper->method('displayShippingPriceExcludingTax')->willReturn(false);
        $this->taxHelper->method('displayShippingBothPrices')->willReturn(false);

        $this->assertFalse($this->view->showsPriceExclTax($this->rate('flatrate', 'flatrate', true, 6.0, 5.0)));
    }
}
