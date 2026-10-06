<?php
declare(strict_types=1);

namespace Majistar\ProductLabels\Api\Data;

use Magento\Framework\Api\SearchResultsInterface;

/**
 * @api
 */
interface LabelSearchResultsInterface extends SearchResultsInterface
{
    /**
     * Get items.
     *
     * @return \Majistar\ProductLabels\Api\Data\LabelInterface[]
     */
    public function getItems();

    /**
     * Set items.
     *
     * @param \Majistar\ProductLabels\Api\Data\LabelInterface[] $items
     * @return $this
     */
    public function setItems(array $items);
}
