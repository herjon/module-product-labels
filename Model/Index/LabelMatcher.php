<?php
declare(strict_types=1);

namespace Majistar\ProductLabels\Model\Index;

use Majistar\ProductLabels\Model\Label;
use Magento\Catalog\Model\Product\Attribute\Source\Status;
use Magento\Catalog\Model\ProductFactory;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory;
use Magento\Framework\Model\ResourceModel\Iterator;

/**
 * Enabled products of a store view that pass the categories filter and the advanced conditions of a label.
 */
class LabelMatcher
{
    /**
     * @var int[]
     */
    private array $matched = [];

    /**
     * @param CollectionFactory $productCollectionFactory
     * @param Iterator $iterator
     * @param ProductFactory $productFactory
     * @param CategoryExpander $categoryExpander
     */
    public function __construct(
        private readonly CollectionFactory $productCollectionFactory,
        private readonly Iterator $iterator,
        private readonly ProductFactory $productFactory,
        private readonly CategoryExpander $categoryExpander
    ) {
    }

    /**
     * Ids of the matching products.
     *
     * @param Label $label
     * @param int $storeId
     * @param int[]|null $productIds only these products (partial reindex); null for the whole catalog
     * @return int[]
     */
    public function getMatchingProductIds(Label $label, int $storeId, ?array $productIds = null): array
    {
        if ($productIds === []) {
            return [];
        }
        $collection = $this->productCollectionFactory->create();
        $collection->setStoreId($storeId)->addStoreFilter($storeId);
        // A disabled child would otherwise label its visible parent through "Use for Parent".
        $collection->addAttributeToFilter('status', Status::STATUS_ENABLED);
        if ($productIds !== null) {
            $collection->addIdFilter($productIds);
        }
        if ($label->getCategoryIds()) {
            // Direct assignments, unlike the category index, include the "Not Visible Individually" children.
            $collection->addCategoriesFilter(['in' => $this->categoryExpander->expand($label->getCategoryIds())]);
        }
        $conditions = $label->getConditions();
        if (!$conditions->getConditions()) {
            $ids = array_map('intval', $collection->getAllIds());
            sort($ids);
            return $ids;
        }
        $conditions->collectValidatedAttributes($collection);

        $this->matched = [];
        $this->iterator->walk(
            $collection->getSelect(),
            [[$this, 'validateRow']],
            ['conditions' => $conditions, 'product' => $this->productFactory->create(), 'store_id' => $storeId]
        );
        sort($this->matched);
        return $this->matched;
    }

    /**
     * Products among some ids that are enabled in a store view (store value, else default value).
     *
     * @param int[] $productIds
     * @param int $storeId
     * @return int[]
     */
    public function getEnabledProductIds(array $productIds, int $storeId): array
    {
        if (!$productIds) {
            return [];
        }
        $collection = $this->productCollectionFactory->create();
        $collection->setStoreId($storeId);
        $collection->addIdFilter($productIds);
        $collection->addAttributeToFilter('status', Status::STATUS_ENABLED);
        $enabled = array_map('intval', $collection->getAllIds());
        sort($enabled);
        return $enabled;
    }

    /**
     * Iterator callback: validates one database row.
     *
     * @param array $args row, conditions, product, store_id
     * @return void
     */
    public function validateRow(array $args): void
    {
        $product = clone $args['product'];
        $product->setData($args['row']);
        $product->setStoreId($args['store_id']);
        if ($args['conditions']->validate($product)) {
            $this->matched[] = (int) $product->getId();
        }
    }
}
