<?php
declare(strict_types=1);

namespace Majistar\ProductLabels\Observer;

use Majistar\ProductLabels\Model\Indexer\LabelIndexer;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Indexer\IndexerRegistry;

/**
 * Invalidates the index when a category moves or its "Is Anchor" changes (the "Categories" filter).
 */
class InvalidateIndexOnCategoryTreeChange implements ObserverInterface
{
    /**
     * @param IndexerRegistry $indexerRegistry
     */
    public function __construct(
        private readonly IndexerRegistry $indexerRegistry
    ) {
    }

    /**
     * @inheritdoc
     */
    public function execute(Observer $observer): void
    {
        $event = $observer->getEvent();
        $category = $event->getData('category');
        $moved = $event->getName() === 'catalog_category_move_after';
        $anchorChanged = $category && !$category->isObjectNew() && $category->dataHasChangedFor('is_anchor');
        if ($moved || $anchorChanged) {
            $this->indexerRegistry->get(LabelIndexer::INDEXER_ID)->invalidate();
        }
    }
}
