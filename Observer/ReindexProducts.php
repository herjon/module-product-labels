<?php
declare(strict_types=1);

namespace Majistar\ProductLabels\Observer;

use Majistar\ProductLabels\Model\Indexer\Processor;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;

/**
 * Reindexes products whose data or label assignments changed (only in "Update on Save" mode).
 */
class ReindexProducts implements ObserverInterface
{
    /**
     * @param Processor $processor
     */
    public function __construct(
        private readonly Processor $processor
    ) {
    }

    /**
     * @inheritdoc
     */
    public function execute(Observer $observer): void
    {
        $event = $observer->getEvent();
        $ids = (array) ($event->getData('product_ids') ?? []);
        $product = $event->getData('product');
        if ($product && $product->getId()) {
            $ids[] = $product->getId();
        }
        if ($event->getData('product_id')) {
            $ids[] = $event->getData('product_id');
        }
        $ids = array_values(array_unique(array_map('intval', $ids)));
        if ($ids) {
            $this->processor->reindexList($ids);
        }
    }
}
