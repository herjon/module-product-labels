<?php
declare(strict_types=1);

namespace Majistar\ProductLabels\Model\View;

use Majistar\ProductLabels\Model\Label;
use Majistar\ProductLabels\Model\ResourceModel\Label\CollectionFactory;

/**
 * Active labels by id, loaded once per request.
 */
class LabelProvider
{
    /**
     * @var array<int, Label|false> false = not active or not found
     */
    private array $labels = [];

    /**
     * @param CollectionFactory $collectionFactory
     */
    public function __construct(
        private readonly CollectionFactory $collectionFactory
    ) {
    }

    /**
     * Active labels among some ids.
     *
     * @param int[] $labelIds
     * @return array<int,Label>
     */
    public function getActive(array $labelIds): array
    {
        $labelIds = array_values(array_unique(array_map('intval', $labelIds)));
        $missing = array_values(array_diff($labelIds, array_keys($this->labels)));
        if ($missing) {
            foreach ($missing as $labelId) {
                $this->labels[$labelId] = false;
            }
            $collection = $this->collectionFactory->create()
                ->addFieldToFilter('label_id', ['in' => $missing])
                ->addFieldToFilter('is_active', 1);
            foreach ($collection->getItems() as $label) {
                $this->labels[(int) $label->getId()] = $label;
            }
        }
        $active = [];
        foreach ($labelIds as $labelId) {
            if ($this->labels[$labelId]) {
                $active[$labelId] = $this->labels[$labelId];
            }
        }
        return $active;
    }
}
