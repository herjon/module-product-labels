<?php
declare(strict_types=1);

namespace Majistar\ProductLabels\Model;

use Majistar\ProductLabels\Api\ProductLabelAssignmentInterface;
use Majistar\ProductLabels\Model\ResourceModel\LabelProduct;
use Magento\Framework\Event\ManagerInterface;
use Magento\Framework\Exception\NoSuchEntityException;

class ProductLabelAssignment implements ProductLabelAssignmentInterface
{
    public const EVENT_SAVE_AFTER = 'majistar_product_labels_assignment_save_after';

    /**
     * @param LabelProduct $resource
     * @param ManagerInterface $eventManager
     */
    public function __construct(
        private readonly LabelProduct $resource,
        private readonly ManagerInterface $eventManager
    ) {
    }

    /**
     * @inheritdoc
     */
    public function getLabelIds(int $productId): array
    {
        return $this->resource->getLabelIds($productId);
    }

    /**
     * @inheritdoc
     */
    public function setLabelIds(int $productId, array $labelIds): bool
    {
        $labelIds = array_values(array_unique(array_map('intval', $labelIds)));
        if (!$this->resource->productExists($productId)) {
            throw new NoSuchEntityException(__('The product with ID "%1" does not exist.', $productId));
        }
        $unknown = array_diff($labelIds, $this->resource->filterExistingLabelIds($labelIds));
        if ($unknown) {
            throw new NoSuchEntityException(
                __('Product labels with these IDs do not exist: %1.', implode(', ', $unknown))
            );
        }
        $this->resource->replaceLabelIds($productId, $labelIds);
        $this->eventManager->dispatch(
            self::EVENT_SAVE_AFTER,
            ['product_id' => $productId, 'label_ids' => $labelIds]
        );
        return true;
    }
}
