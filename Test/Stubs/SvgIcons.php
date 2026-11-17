<?php declare(strict_types=1);

namespace Hyva\Theme\ViewModel;

use Magento\Framework\View\Element\Block\ArgumentInterface;

/**
 * Stand-in for the Hyvä Theme SVG icon view model, which is not installable in CI.
 * Declares only what the code under test and PHPStan need; loaded by Test/bootstrap.php when the real class is absent.
 */
class SvgIcons implements ArgumentInterface
{
    /**
     * @param string $icon
     * @param string $classNames
     * @param int|null $width
     * @param int|null $height
     * @param array<string, mixed> $attributes
     * @return string
     */
    public function renderHtml(
        string $icon,
        string $classNames = '',
        ?int $width = 24,
        ?int $height = 24,
        array $attributes = []
    ): string {
        return '';
    }
}
