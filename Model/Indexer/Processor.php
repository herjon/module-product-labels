<?php
declare(strict_types=1);

namespace Majistar\ProductLabels\Model\Indexer;

use Magento\Framework\Indexer\AbstractProcessor;

/**
 * Reindexes products on save, unless the indexer is "Update by Schedule".
 */
class Processor extends AbstractProcessor
{
    public const INDEXER_ID = LabelIndexer::INDEXER_ID;
}
