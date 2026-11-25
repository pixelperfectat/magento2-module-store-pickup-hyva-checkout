<?php declare(strict_types=1);

namespace PixelPerfect\StorePickupHyvaCheckout\ViewModel;

use Hyva\Theme\ViewModel\SvgIcons;
use Magento\Framework\View\Element\Block\ArgumentInterface;
use Psr\Log\LoggerInterface;

/**
 * The one pickup icon of the checkout: on the pickup rates, the payment step banner and the summary card.
 *
 * The icon path uses the format of Hyvä's SvgIcons, so it also works as the "icon" of a shipping method's metadata.
 * The default is Hyvä's Heroicons outline location marker (a map pin). Override the "iconPath" argument in di.xml
 * to use another icon.
 */
class PickupIcon implements ArgumentInterface
{
    public const string DEFAULT_ICON_PATH = 'heroicons/outline/location-marker';

    /**
     * Constructor.
     *
     * @param SvgIcons $svgIcons
     * @param LoggerInterface $logger
     * @param string $iconPath
     */
    public function __construct(
        private readonly SvgIcons $svgIcons,
        private readonly LoggerInterface $logger,
        private readonly string $iconPath = self::DEFAULT_ICON_PATH
    ) {
    }

    /**
     * The configured icon path, in the format of Hyvä's SvgIcons.
     *
     * @return string
     */
    public function getIconPath(): string
    {
        return $this->iconPath;
    }

    /**
     * The icon as inline SVG, hidden from screen readers; empty when it cannot be rendered.
     *
     * @param string $classes
     * @param int|null $width
     * @param int|null $height
     * @return string
     */
    public function getIconHtml(string $classes = '', ?int $width = null, ?int $height = null): string
    {
        try {
            return $this->svgIcons->renderHtml($this->iconPath, $classes, $width, $height, ['aria-hidden' => 'true']);
        } catch (\Throwable $exception) {
            $this->logger->warning(
                'Store pickup: the pickup icon "' . $this->iconPath . '" could not be rendered: '
                . $exception->getMessage(),
                ['exception' => $exception]
            );
        }

        return '';
    }
}
