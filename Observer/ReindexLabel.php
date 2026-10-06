<?php
declare(strict_types=1);

namespace Majistar\ProductLabels\Observer;

use Majistar\ProductLabels\Model\Index\IndexBuilder;
use Magento\Framework\App\CacheInterface;
use Magento\Framework\Event\ManagerInterface;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;

/**
 * Reindexes a label after commit, then cleans its caches again: the model did it before the rows changed.
 */
class ReindexLabel implements ObserverInterface
{
    /**
     * @param IndexBuilder $indexBuilder
     * @param CacheInterface $cache
     * @param ManagerInterface $eventManager
     */
    public function __construct(
        private readonly IndexBuilder $indexBuilder,
        private readonly CacheInterface $cache,
        private readonly ManagerInterface $eventManager
    ) {
    }

    /**
     * @inheritdoc
     */
    public function execute(Observer $observer): void
    {
        $label = $observer->getEvent()->getData('label');
        if ($label && $label->getId()) {
            $this->indexBuilder->reindexLabel((int) $label->getId());
            // Block cache (product cards), then full page cache (built-in or Varnish).
            $this->cache->clean($label->getIdentities());
            $this->eventManager->dispatch('clean_cache_by_tags', ['object' => $label]);
        }
    }
}
