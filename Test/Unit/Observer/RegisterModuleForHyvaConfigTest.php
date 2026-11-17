<?php declare(strict_types=1);

namespace PixelPerfect\StorePickupHyvaCheckout\Test\Unit\Observer;

use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Component\ComponentRegistrar;
use Magento\Framework\DataObject;
use Magento\Framework\Event\Observer;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use PixelPerfect\StorePickupHyvaCheckout\Observer\RegisterModuleForHyvaConfig;
use Psr\Log\LoggerInterface;

class RegisterModuleForHyvaConfigTest extends TestCase
{
    private LoggerInterface&MockObject $logger;

    protected function setUp(): void
    {
        $this->logger = $this->createMock(LoggerInterface::class);
    }

    private function observer(?string $path, ?\Throwable $failure = null): RegisterModuleForHyvaConfig
    {
        $registrar = $this->createMock(ComponentRegistrar::class);
        $getPath = $registrar->method('getPath')->with(ComponentRegistrar::MODULE, 'Vendor_Module');
        $failure === null ? $getPath->willReturn($path) : $getPath->willThrowException($failure);
        $directoryList = $this->createMock(DirectoryList::class);
        $directoryList->method('getRoot')->willReturn('/var/www/html');

        return new RegisterModuleForHyvaConfig($registrar, $directoryList, $this->logger, 'Vendor_Module');
    }

    public function testAppendsTheModulePathRelativeToTheRoot(): void
    {
        $config = new DataObject(['extensions' => [['src' => 'vendor/other/src']]]);
        $this->observer('/var/www/html/vendor/vendor/module/src')
            ->execute(new Observer(['config' => $config]));

        self::assertSame(
            [['src' => 'vendor/other/src'], ['src' => 'vendor/vendor/module/src']],
            $config->getData('extensions')
        );
    }

    public function testCreatesTheExtensionsListWhenThereIsNone(): void
    {
        $config = new DataObject();
        $this->observer('/var/www/html/packages/module/src')->execute(new Observer(['config' => $config]));

        self::assertSame([['src' => 'packages/module/src']], $config->getData('extensions'));
    }

    public function testUnknownModuleLeavesTheConfigAlone(): void
    {
        $this->logger->expects(self::never())->method('error');
        $config = new DataObject(['extensions' => []]);
        $this->observer(null)->execute(new Observer(['config' => $config]));

        self::assertSame([], $config->getData('extensions'));
    }

    public function testMissingConfigObjectIsIgnored(): void
    {
        $this->logger->expects(self::never())->method('error');
        $observer = new Observer();
        $this->observer('/var/www/html/x')->execute($observer);

        self::assertNull($observer->getData('config'));
    }

    public function testFailureIsLoggedAndNeverAbortsTheConfigGeneration(): void
    {
        $failure = new \RuntimeException('registrar broken');
        $this->logger->expects(self::once())->method('error')->with(
            'Store pickup module could not be added to the Hyvä config: registrar broken',
            ['exception' => $failure]
        );
        $config = new DataObject(['extensions' => [['src' => 'vendor/other/src']]]);

        $this->observer(null, $failure)->execute(new Observer(['config' => $config]));

        self::assertSame([['src' => 'vendor/other/src']], $config->getData('extensions'));
    }
}
