<?php
declare(strict_types=1);

namespace Majistar\ProductLabels\Model\Indexer;

use Majistar\ProductLabels\Model\Index\IndexBuilder;
use Magento\Framework\Indexer\ActionInterface as IndexerActionInterface;
use Magento\Framework\Mview\ActionInterface as MviewActionInterface;

/**
 * Indexer "Product Labels"; ids are product ids.
 */
class LabelIndexer implements IndexerActionInterface, MviewActionInterface
{
    public const INDEXER_ID = 'majistar_product_labels';

    /**
     * @param IndexBuilder $indexBuilder
     */
    public function __construct(
        private readonly IndexBuilder $indexBuilder
    ) {
    }

    /**
     * @inheritdoc
     */
    public function executeFull(): void
    {
        $this->indexBuilder->reindexAll();
    }

    /**
     * @inheritdoc
     */
    public function executeList(array $ids): void
    {
        $this->indexBuilder->reindexProducts($ids);
    }

    /**
     * @inheritdoc
     */
    public function executeRow($id): void
    {
        $this->indexBuilder->reindexProducts([(int) $id]);
    }

    /**
     * Update by Schedule: products changed since the last run.
     *
     * @param int[] $ids
     * @return void
     */
    public function execute($ids): void
    {
        $this->indexBuilder->reindexProducts($ids);
    }
}
