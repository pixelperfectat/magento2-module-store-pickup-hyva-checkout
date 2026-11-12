<?php declare(strict_types=1);

namespace PixelPerfect\StorePickupHyvaCheckout\Test\Unit\ViewModel;

use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Directory\Model\Country;
use Magento\Directory\Model\CountryFactory;
use Magento\Quote\Model\Quote;
use Magento\Quote\Model\Quote\Address;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use PixelPerfect\StorePickup\Api\Data\PickupLocationInterface;
use PixelPerfect\StorePickup\Api\Service\LocationProviderInterface;
use PixelPerfect\StorePickup\Api\Service\PickupMethodResolverInterface;
use PixelPerfect\StorePickupHyvaCheckout\ViewModel\PickupLocationView;
use Psr\Log\LoggerInterface;

class PickupLocationViewTest extends TestCase
{
    private CheckoutSession&MockObject $checkoutSession;
    private PickupMethodResolverInterface&MockObject $resolver;
    private LocationProviderInterface&MockObject $provider;
    private CountryFactory&MockObject $countryFactory;
    private LoggerInterface&MockObject $logger;
    private PickupLocationView $viewModel;

    protected function setUp(): void
    {
        $this->checkoutSession = $this->createMock(CheckoutSession::class);
        $this->resolver = $this->createMock(PickupMethodResolverInterface::class);
        $this->provider = $this->createMock(LocationProviderInterface::class);
        $this->countryFactory = $this->createMock(CountryFactory::class);
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->viewModel = new PickupLocationView(
            $this->checkoutSession,
            $this->resolver,
            $this->provider,
            $this->countryFactory,
            $this->logger
        );
    }

    public function testAddressLinesListStreetPostcodeCityAndCountryName(): void
    {
        $location = $this->createMock(PickupLocationInterface::class);
        $location->method('getStreet')->willReturn(['1 Example Street', 'Building B']);
        $location->method('getPostcode')->willReturn('1010');
        $location->method('getCity')->willReturn('Vienna');
        $location->method('getCountryId')->willReturn('AT');

        $country = $this->createMock(Country::class);
        $country->expects($this->once())->method('loadByCode')->with('AT')->willReturnSelf();
        $country->method('getName')->willReturn('Austria');
        $this->countryFactory->method('create')->willReturn($country);

        $this->assertSame(
            ['1 Example Street', 'Building B', '1010 Vienna', 'Austria'],
            $this->viewModel->getAddressLines($location)
        );
    }

    public function testAddressLinesSkipBlankPartsAndFallBackToTheCountryCode(): void
    {
        $location = $this->createMock(PickupLocationInterface::class);
        $location->method('getStreet')->willReturn(['1 Example Street', '']);
        $location->method('getPostcode')->willReturn('');
        $location->method('getCity')->willReturn('Vienna');
        $location->method('getCountryId')->willReturn('ZZ');

        $country = $this->createMock(Country::class);
        $country->method('loadByCode')->willReturnSelf();
        $country->method('getName')->willReturn(null);
        $this->countryFactory->method('create')->willReturn($country);

        $this->assertSame(['1 Example Street', 'Vienna', 'ZZ'], $this->viewModel->getAddressLines($location));
    }

    public function testSelectedLocationIsNullWithoutAPickupMethod(): void
    {
        $this->quote('flatrate_flatrate');
        $this->resolver->method('getLocationCode')->willReturn(null);
        $this->provider->expects($this->never())->method('getLocation');

        $this->assertNull($this->viewModel->getSelectedLocation());
    }

    public function testSelectedLocationIsNullWithoutAnyShippingMethod(): void
    {
        $this->quote(null);
        $this->resolver->method('getLocationCode')->with(null)->willReturn(null);

        $this->assertNull($this->viewModel->getSelectedLocation());
    }

    public function testSelectedLocationIsNullForAVirtualQuote(): void
    {
        $this->quote('pppickup_main', virtual: true);
        $this->provider->expects($this->never())->method('getLocation');

        $this->assertNull($this->viewModel->getSelectedLocation());
    }

    public function testSelectedLocationComesFromTheProviderInTheQuoteStore(): void
    {
        $location = $this->activeLocation();
        $this->quote('pppickup_main', storeId: 7);
        $this->resolver->method('getLocationCode')->with('pppickup_main')->willReturn('main');
        $this->provider->expects($this->once())->method('getLocation')->with('main', 7)->willReturn($location);

        $this->assertSame($location, $this->viewModel->getSelectedLocation());
    }

    public function testSelectedLocationIsNullForAnUnknownCode(): void
    {
        $this->quote('pppickup_gone');
        $this->resolver->method('getLocationCode')->willReturn('gone');
        $this->provider->method('getLocation')->willReturn(null);

        $this->assertNull($this->viewModel->getSelectedLocation());
    }

    public function testSelectedLocationIsNullForAnInactiveLocation(): void
    {
        $location = $this->createMock(PickupLocationInterface::class);
        $location->method('isActive')->willReturn(false);
        $this->quote('pppickup_main');
        $this->resolver->method('getLocationCode')->willReturn('main');
        $this->provider->method('getLocation')->willReturn($location);

        $this->assertNull($this->viewModel->getSelectedLocation());
    }

    public function testSelectedLocationIsNullAndLoggedWhenTheSessionFails(): void
    {
        $this->checkoutSession->method('getQuote')->willThrowException(new \RuntimeException('boom'));
        $this->logger->expects($this->once())->method('error');

        $this->assertNull($this->viewModel->getSelectedLocation());
    }

    public function testLocationByCodeUsesTheSessionQuoteStore(): void
    {
        $location = $this->activeLocation();
        $quote = $this->createMock(Quote::class);
        $quote->method('getStoreId')->willReturn(3);
        $this->checkoutSession->method('getQuote')->willReturn($quote);
        $this->provider->expects($this->once())->method('getLocation')->with('main', 3)->willReturn($location);

        $this->assertSame($location, $this->viewModel->getLocation('main'));
    }

    public function testLocationByCodeIsNullWhenInactiveOrUnknown(): void
    {
        $inactive = $this->createMock(PickupLocationInterface::class);
        $inactive->method('isActive')->willReturn(false);
        $quote = $this->createMock(Quote::class);
        $quote->method('getStoreId')->willReturn(3);
        $this->checkoutSession->method('getQuote')->willReturn($quote);
        $this->provider->method('getLocation')->willReturnMap([['off', 3, $inactive], ['none', 3, null]]);

        $this->assertNull($this->viewModel->getLocation('off'));
        $this->assertNull($this->viewModel->getLocation('none'));
    }

    public function testLocationByCodeIsNullAndLoggedWhenTheSessionFails(): void
    {
        $this->checkoutSession->method('getQuote')->willThrowException(new \RuntimeException('boom'));
        $this->logger->expects($this->once())->method('error');

        $this->assertNull($this->viewModel->getLocation('main'));
    }

    private function activeLocation(): PickupLocationInterface&MockObject
    {
        $location = $this->createMock(PickupLocationInterface::class);
        $location->method('isActive')->willReturn(true);

        return $location;
    }

    private function quote(?string $shippingMethod, bool $virtual = false, int $storeId = 1): void
    {
        $address = $this->createMock(Address::class);
        $address->method('getShippingMethod')->willReturn($shippingMethod);
        $quote = $this->createMock(Quote::class);
        $quote->method('isVirtual')->willReturn($virtual);
        $quote->method('getShippingAddress')->willReturn($address);
        $quote->method('getStoreId')->willReturn($storeId);
        $this->checkoutSession->method('getQuote')->willReturn($quote);
    }
}
