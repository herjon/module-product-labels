<?php
declare(strict_types=1);

namespace Majistar\ProductLabels\Plugin\Store;

use Majistar\ProductLabels\Model\Indexer\LabelIndexer;
use Magento\Framework\Indexer\IndexerRegistry;
use Magento\Framework\Model\AbstractModel;
use Magento\Store\Model\ResourceModel\Store as StoreResource;

/**
 * A new or moved store view lacks the index rows of "All Store Views" labels: invalidate the index.
 */
class InvalidateLabelIndex
{
    /**
     * @param IndexerRegistry $indexerRegistry
     */
    public function __construct(
        private readonly IndexerRegistry $indexerRegistry
    ) {
    }

    /**
     * After a store view is saved.
     *
     * @param StoreResource $subject
     * @param StoreResource $result
     * @param AbstractModel $store
     * @return StoreResource
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function afterSave(StoreResource $subject, StoreResource $result, AbstractModel $store): StoreResource
    {
        if ($store->isObjectNew() || $store->dataHasChangedFor('group_id') || $store->dataHasChangedFor('website_id')) {
            $this->indexerRegistry->get(LabelIndexer::INDEXER_ID)->invalidate();
        }
        return $result;
    }
}
