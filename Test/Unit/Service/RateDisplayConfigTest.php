<?php declare(strict_types=1);

namespace PixelPerfect\StorePickupHyvaCheckout\Test\Unit\Service;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use PixelPerfect\StorePickupHyvaCheckout\Api\Service\RateDisplayConfigInterface;
use PixelPerfect\StorePickupHyvaCheckout\Service\RateDisplayConfig;

class RateDisplayConfigTest extends TestCase
{
    private ScopeConfigInterface&MockObject $scopeConfig;
    private RateDisplayConfig $config;

    protected function setUp(): void
    {
        $this->scopeConfig = $this->createMock(ScopeConfigInterface::class);
        $this->config = new RateDisplayConfig($this->scopeConfig);
    }

    /**
     * @param array<string, mixed> $values
     */
    private function configure(array $values): void
    {
        $this->scopeConfig->method('getValue')
            ->willReturnCallback(static fn (string $path): mixed => $values[$path] ?? null);
    }

    public function testTheConfigPathsBelongToTheCoreSection(): void
    {
        $this->assertSame('pp_store_pickup/hyva_checkout/rate_display', RateDisplayConfigInterface::XML_PATH_MODE);
        $this->assertSame('pp_store_pickup/hyva_checkout/rate_label', RateDisplayConfigInterface::XML_PATH_RATE_LABEL);
        $this->assertSame(
            'pp_store_pickup/hyva_checkout/group_pickup_title',
            RateDisplayConfigInterface::XML_PATH_PICKUP_GROUP_TITLE
        );
        $this->assertSame(
            'pp_store_pickup/hyva_checkout/group_shipping_title',
            RateDisplayConfigInterface::XML_PATH_SHIPPING_GROUP_TITLE
        );
    }

    public function testTheModeIsLabelWhenNothingIsConfigured(): void
    {
        $this->configure([]);

        $this->assertSame(RateDisplayConfigInterface::MODE_LABEL, $this->config->getMode());
    }

    /**
     * @return array<string, array{string}>
     */
    public static function knownModes(): array
    {
        return [
            'label' => ['label'],
            'groups' => ['groups'],
            'none' => ['none'],
        ];
    }

    /**
     * @dataProvider knownModes
     */
    public function testAKnownModeIsReturnedAsConfigured(string $mode): void
    {
        $this->configure([RateDisplayConfigInterface::XML_PATH_MODE => $mode]);

        $this->assertSame($mode, $this->config->getMode());
    }

    /**
     * @return array<string, array{mixed}>
     */
    public static function unknownModes(): array
    {
        return [
            'empty' => [''],
            'unknown' => ['tabs'],
            'other case' => ['GROUPS'],
            'padded' => [' groups'],
        ];
    }

    /**
     * @dataProvider unknownModes
     */
    public function testAnUnknownModeFallsBackToLabel(mixed $value): void
    {
        $this->configure([RateDisplayConfigInterface::XML_PATH_MODE => $value]);

        $this->assertSame(RateDisplayConfigInterface::MODE_LABEL, $this->config->getMode());
    }

    public function testTheModeIsReadInStoreScope(): void
    {
        $this->scopeConfig->expects($this->once())
            ->method('getValue')
            ->with(RateDisplayConfigInterface::XML_PATH_MODE, ScopeInterface::SCOPE_STORE, 3)
            ->willReturn('groups');

        $this->assertSame('groups', $this->config->getMode(3));
    }

    public function testTheTextsAreReadInStoreScope(): void
    {
        $this->scopeConfig->expects($this->exactly(3))
            ->method('getValue')
            ->willReturnCallback(function (string $path, string $scope, ?int $storeId): string {
                $this->assertSame(ScopeInterface::SCOPE_STORE, $scope);
                $this->assertSame(5, $storeId);

                return $path;
            });

        $this->assertSame(RateDisplayConfigInterface::XML_PATH_RATE_LABEL, $this->config->getRateLabel(5));
        $this->assertSame(
            RateDisplayConfigInterface::XML_PATH_PICKUP_GROUP_TITLE,
            $this->config->getPickupGroupTitle(5)
        );
        $this->assertSame(
            RateDisplayConfigInterface::XML_PATH_SHIPPING_GROUP_TITLE,
            $this->config->getShippingGroupTitle(5)
        );
    }

    public function testConfiguredTextsAreTrimmed(): void
    {
        $this->configure([
            RateDisplayConfigInterface::XML_PATH_RATE_LABEL => '  Click & Collect ',
            RateDisplayConfigInterface::XML_PATH_PICKUP_GROUP_TITLE => "\tCollect in a store\n",
            RateDisplayConfigInterface::XML_PATH_SHIPPING_GROUP_TITLE => ' Delivery ',
        ]);

        $this->assertSame('Click & Collect', $this->config->getRateLabel());
        $this->assertSame('Collect in a store', $this->config->getPickupGroupTitle());
        $this->assertSame('Delivery', $this->config->getShippingGroupTitle());
    }

    /**
     * @return array<string, array{mixed}>
     */
    public static function emptyTexts(): array
    {
        return [
            'not set' => [null],
            'empty' => [''],
            'blank' => ['   '],
        ];
    }

    /**
     * @dataProvider emptyTexts
     */
    public function testEmptyTextsFallBackToTheTranslatedDefaults(mixed $value): void
    {
        $this->configure([
            RateDisplayConfigInterface::XML_PATH_RATE_LABEL => $value,
            RateDisplayConfigInterface::XML_PATH_PICKUP_GROUP_TITLE => $value,
            RateDisplayConfigInterface::XML_PATH_SHIPPING_GROUP_TITLE => $value,
        ]);

        $this->assertSame('In-store pickup', $this->config->getRateLabel());
        $this->assertSame('In-store pickup', $this->config->getPickupGroupTitle());
        $this->assertSame('Shipping', $this->config->getShippingGroupTitle());
    }
}
