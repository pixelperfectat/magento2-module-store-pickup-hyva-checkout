<?php declare(strict_types=1);

namespace PixelPerfect\StorePickupHyvaCheckout\ViewModel;

use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Directory\Model\CountryFactory;
use Magento\Framework\View\Element\Block\ArgumentInterface;
use PixelPerfect\StorePickup\Api\Data\PickupLocationInterface;
use PixelPerfect\StorePickup\Api\Service\LocationProviderInterface;
use PixelPerfect\StorePickup\Api\Service\PickupMethodResolverInterface;
use Psr\Log\LoggerInterface;

/**
 * Pickup location data for the Hyvä Checkout templates.
 *
 * Locations come from the core location provider in the store of the session quote, so every text (name, opening
 * hours, note) is the one configured for that store view.
 */
class PickupLocationView implements ArgumentInterface
{
    /** @var array<string, PickupLocationInterface|null> */
    private array $selected = [];

    /**
     * Constructor.
     *
     * @param CheckoutSession $checkoutSession
     * @param PickupMethodResolverInterface $methodResolver
     * @param LocationProviderInterface $locationProvider
     * @param CountryFactory $countryFactory
     * @param LoggerInterface $logger
     */
    public function __construct(
        private readonly CheckoutSession $checkoutSession,
        private readonly PickupMethodResolverInterface $methodResolver,
        private readonly LocationProviderInterface $locationProvider,
        private readonly CountryFactory $countryFactory,
        private readonly LoggerInterface $logger
    ) {
    }

    /**
     * The active pickup location chosen on the session quote, if any.
     *
     * Null for a virtual quote, a non-pickup shipping method, an unknown code or an inactive location.
     *
     * @return PickupLocationInterface|null
     */
    public function getSelectedLocation(): ?PickupLocationInterface
    {
        try {
            $quote = $this->checkoutSession->getQuote();
            if ($quote->isVirtual()) {
                return null;
            }

            $code = $this->methodResolver->getLocationCode($quote->getShippingAddress()->getShippingMethod());
            if ($code === null) {
                return null;
            }

            // One provider lookup per quote, store and location within a request; a method change gets a new key.
            $key = (int) $quote->getId() . '|' . (int) $quote->getStoreId() . '|' . $code;
            if (!array_key_exists($key, $this->selected)) {
                $this->selected[$key] = $this->activeOrNull(
                    $this->locationProvider->getLocation($code, (int) $quote->getStoreId())
                );
            }

            return $this->selected[$key];
        } catch (\Throwable $exception) {
            $this->logFailure($exception);

            return null;
        }
    }

    /**
     * The active location with the given code in the store of the session quote.
     *
     * @param string $code
     * @return PickupLocationInterface|null
     */
    public function getLocation(string $code): ?PickupLocationInterface
    {
        try {
            $storeId = (int) $this->checkoutSession->getQuote()->getStoreId();

            return $this->activeOrNull($this->locationProvider->getLocation($code, $storeId));
        } catch (\Throwable $exception) {
            $this->logFailure($exception);

            return null;
        }
    }

    /**
     * Address lines of a location: street lines, "<postcode> <city>" and the country name.
     *
     * @param PickupLocationInterface $location
     * @return list<string>
     */
    public function getAddressLines(PickupLocationInterface $location): array
    {
        $lines = [];
        foreach ($location->getStreet() as $street) {
            $lines[] = $street;
        }
        $lines[] = $location->getPostcode() . ' ' . $location->getCity();
        $lines[] = $this->countryName($location->getCountryId());

        return array_values(array_filter(array_map('trim', $lines), static fn (string $line): bool => $line !== ''));
    }

    /**
     * Country display name; falls back to the ISO code when the directory does not know it.
     *
     * @param string $countryId
     * @return string
     */
    private function countryName(string $countryId): string
    {
        $name = (string) $this->countryFactory->create()->loadByCode($countryId)->getName();

        return $name !== '' ? $name : $countryId;
    }

    /**
     * Keep only an active location.
     *
     * @param PickupLocationInterface|null $location
     * @return PickupLocationInterface|null
     */
    private function activeOrNull(?PickupLocationInterface $location): ?PickupLocationInterface
    {
        return $location !== null && $location->isActive() ? $location : null;
    }

    /**
     * Log a failure; the checkout renders without the card instead of breaking.
     *
     * @param \Throwable $exception
     * @return void
     */
    private function logFailure(\Throwable $exception): void
    {
        $this->logger->error(
            'Store pickup: reading the pickup location for the checkout failed: ' . $exception->getMessage(),
            ['exception' => $exception]
        );
    }
}
