<?php
declare(strict_types=1);

namespace Majistar\ProductLabels\Observer;

use Magento\Framework\Component\ComponentRegistrar;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;

class RegisterTailwindSources implements ObserverInterface
{
    /**
     * @param ComponentRegistrar $componentRegistrar
     */
    public function __construct(
        private readonly ComponentRegistrar $componentRegistrar
    ) {
    }

    /**
     * @inheritdoc
     */
    public function execute(Observer $observer): void
    {
        $config = $observer->getData('config');
        $extensions = $config->hasData('extensions') ? (array) $config->getData('extensions') : [];
        $path = (string) $this->componentRegistrar->getPath(ComponentRegistrar::MODULE, 'Majistar_ProductLabels');
        $extension = ['src' => substr($path, strlen(BP) + 1)];
        if (!in_array($extension, $extensions, true)) {
            $extensions[] = $extension;
        }
        $config->setData('extensions', $extensions);
    }
}
