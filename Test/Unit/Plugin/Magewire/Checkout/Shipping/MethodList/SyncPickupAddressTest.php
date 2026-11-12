<?php declare(strict_types=1);

namespace PixelPerfect\StorePickupHyvaCheckout\Test\Unit\Plugin\Magewire\Checkout\Shipping\MethodList;

use Hyva\Checkout\Magewire\Checkout\Shipping\MethodList;
use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Quote\Model\Quote;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use PixelPerfect\StorePickup\Api\Service\PickupAddressApplierInterface;
use PixelPerfect\StorePickupHyvaCheckout\Plugin\Magewire\Checkout\Shipping\MethodList\SyncPickupAddress;
use Psr\Log\LoggerInterface;

class SyncPickupAddressTest extends TestCase
{
    private const QUOTE_ID = 42;

    /** @var CartRepositoryInterface&MockObject */
    private CartRepositoryInterface $cartRepository;

    /** @var PickupAddressApplierInterface&MockObject */
    private PickupAddressApplierInterface $applier;

    /** @var LoggerInterface&MockObject */
    private LoggerInterface $logger;

    /** @var MethodList&MockObject */
    private MethodList $methodList;

    private SyncPickupAddress $plugin;

    /** @var Quote&MockObject */
    private Quote $quote;

    protected function setUp(): void
    {
        $this->cartRepository = $this->createMock(CartRepositoryInterface::class);
        $this->applier = $this->createMock(PickupAddressApplierInterface::class);
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->methodList = $this->createMock(MethodList::class);
        $this->quote = $this->createMock(Quote::class);

        $session = $this->getMockBuilder(CheckoutSession::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getQuoteId'])
            ->getMock();
        $session->method('getQuoteId')->willReturn((string) self::QUOTE_ID);

        $this->cartRepository->method('getActive')->with(self::QUOTE_ID)->willReturn($this->quote);

        $this->plugin = new SyncPickupAddress($this->cartRepository, $session, $this->applier, $this->logger);
    }

    public function testSelectingPickupEmitsBothEventsOnce(): void
    {
        $this->quoteCodeGoes(null, 'main');

        $emitted = [];
        $this->methodList->expects($this->exactly(2))->method('emit')
            ->willReturnCallback(function (string $event) use (&$emitted) {
                $emitted[] = $event;
            });

        $result = $this->plugin->afterUpdatedMethod($this->methodList, 'pppickup_main', 'pppickup_main');

        $this->assertSame('pppickup_main', $result);
        $this->assertSame(['shipping_address_saved', 'pp_pickup_changed'], $emitted);
    }

    public function testSwitchingBetweenLocationsEmitsBothEvents(): void
    {
        $this->quoteCodeGoes('main', 'second');
        $this->methodList->expects($this->exactly(2))->method('emit');

        $this->plugin->afterUpdatedMethod($this->methodList, 'pppickup_second', 'pppickup_second');
    }

    public function testLeavingPickupEmitsBothEvents(): void
    {
        $this->quoteCodeGoes('main', null);
        $this->methodList->expects($this->exactly(2))->method('emit');

        $this->plugin->afterUpdatedMethod($this->methodList, 'flatrate_flatrate', 'flatrate_flatrate');
    }

    public function testSwitchBetweenNonPickupMethodsEmitsNothing(): void
    {
        $this->quoteCodeGoes(null, null);
        $this->applier->expects($this->once())->method('sync')->with($this->quote);
        $this->methodList->expects($this->never())->method('emit');

        $result = $this->plugin->afterUpdatedMethod(
            $this->methodList,
            'freeshipping_freeshipping',
            'freeshipping_freeshipping'
        );

        $this->assertSame('freeshipping_freeshipping', $result);
    }

    public function testEmptyAndNonStringCodesCountAsNoLocation(): void
    {
        $this->quoteCodeGoes('', null);
        $this->methodList->expects($this->never())->method('emit');
        $this->logger->expects($this->never())->method('error');

        $this->plugin->afterUpdatedMethod($this->methodList, 'x', 'x');
    }

    public function testNonStringCodeCountsAsNoLocation(): void
    {
        $this->quoteCodeGoes(5, null);
        $this->methodList->expects($this->never())->method('emit');
        $this->logger->expects($this->never())->method('error');

        $this->plugin->afterUpdatedMethod($this->methodList, 'x', 'x');
    }

    public function testSyncFailureIsLoggedAndSwallowed(): void
    {
        $failure = new \RuntimeException('boom');
        $this->quote->method('getData')->with('pp_pickup_location_code')->willReturn(null);
        $this->applier->method('sync')->willThrowException($failure);
        $this->logger->expects($this->once())->method('error')->with(
            'Store pickup: syncing the pickup address after the shipping method change failed: boom',
            ['exception' => $failure]
        );
        $this->methodList->expects($this->never())->method('emit');

        $result = $this->plugin->afterUpdatedMethod($this->methodList, 'pppickup_main', 'pppickup_main');

        $this->assertSame('pppickup_main', $result);
    }

    public function testQuoteLoadFailureIsLoggedAndSwallowed(): void
    {
        $cartRepository = $this->createMock(CartRepositoryInterface::class);
        $cartRepository->method('getActive')->willThrowException(new \RuntimeException('no cart'));
        $session = $this->getMockBuilder(CheckoutSession::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getQuoteId'])
            ->getMock();
        $plugin = new SyncPickupAddress($cartRepository, $session, $this->applier, $this->logger);
        $this->logger->expects($this->once())->method('error');
        $this->methodList->expects($this->never())->method('emit');

        $this->assertSame('x', $plugin->afterUpdatedMethod($this->methodList, 'x', 'x'));
    }

    /**
     * The quote code before sync() and after it.
     *
     * @param string|int|null $before
     * @param string|null $after
     */
    private function quoteCodeGoes(string|int|null $before, ?string $after): void
    {
        $this->quote->method('getData')->with('pp_pickup_location_code')
            ->willReturnOnConsecutiveCalls($before, $after);
    }
}
