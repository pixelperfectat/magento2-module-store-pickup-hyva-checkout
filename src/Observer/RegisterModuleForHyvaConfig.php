<?php declare(strict_types=1);

namespace PixelPerfect\StorePickupHyvaCheckout\Observer;

use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Component\ComponentRegistrar;
use Magento\Framework\DataObject;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Psr\Log\LoggerInterface;

/**
 * Adds this module to Hyvä's Tailwind configuration (event `hyva_config_generate_before`), so the classes of its
 * templates are generated into the theme CSS when the merchant runs `bin/magento hyva:config:generate` and
 * rebuilds the theme styles. A failure is logged and never aborts `hyva:config:generate`.
 */
class RegisterModuleForHyvaConfig implements ObserverInterface
{
    /**
     * @param ComponentRegistrar $componentRegistrar
     * @param DirectoryList $directoryList
     * @param LoggerInterface $logger
     * @param string $moduleName
     */
    public function __construct(
        private readonly ComponentRegistrar $componentRegistrar,
        private readonly DirectoryList $directoryList,
        private readonly LoggerInterface $logger,
        private readonly string $moduleName = 'PixelPerfect_StorePickupHyvaCheckout'
    ) {
    }

    /**
     * Appends the module path, relative to the Magento root, to the extensions of the Hyvä config.
     *
     * @param Observer $observer
     * @return void
     */
    public function execute(Observer $observer): void
    {
        try {
            $config = $observer->getData('config');
            if (!$config instanceof DataObject) {
                return;
            }
            $path = $this->componentRegistrar->getPath(ComponentRegistrar::MODULE, $this->moduleName);
            if ($path === null) {
                return;
            }
            $extensions = $config->getData('extensions') ?? [];
            $extensions[] = ['src' => substr($path, strlen($this->directoryList->getRoot()) + 1)];
            $config->setData('extensions', $extensions);
        } catch (\Throwable $e) {
            $this->logger->error(
                'Store pickup module could not be added to the Hyvä config: ' . $e->getMessage(),
                ['exception' => $e]
            );
        }
    }
}
