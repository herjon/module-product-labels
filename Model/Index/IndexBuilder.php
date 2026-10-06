<?php
declare(strict_types=1);

namespace Majistar\ProductLabels\Model\Index;

use Majistar\ProductLabels\Model\Label;
use Majistar\ProductLabels\Model\ResourceModel\Index;
use Majistar\ProductLabels\Model\ResourceModel\Label\CollectionFactory;
use Majistar\ProductLabels\Model\ResourceModel\LabelProduct;
use Magento\Catalog\Model\Product;
use Magento\Framework\Indexer\CacheContext;
use Magento\Store\Model\StoreManagerInterface;

/**
 * Fills majistar_product_label_index: everything, one label or some products.
 */
class IndexBuilder
{
    /**
     * @param CollectionFactory $labelCollectionFactory
     * @param LabelMatcher $matcher
     * @param ParentProvider $parentProvider
     * @param LabelProduct $labelProduct
     * @param Index $index
     * @param RowBuilder $rowBuilder
     * @param StoreManagerInterface $storeManager
     * @param CacheContext $cacheContext
     */
    public function __construct(
        private readonly CollectionFactory $labelCollectionFactory,
        private readonly LabelMatcher $matcher,
        private readonly ParentProvider $parentProvider,
        private readonly LabelProduct $labelProduct,
        private readonly Index $index,
        private readonly RowBuilder $rowBuilder,
        private readonly StoreManagerInterface $storeManager,
        private readonly CacheContext $cacheContext
    ) {
    }

    /**
     * Rebuilds the whole index, one label at a time, without ever emptying it.
     *
     * @return void
     */
    public function reindexAll(): void
    {
        $indexed = [];
        foreach ($this->getActiveLabelIds() as $labelId) {
            if ($this->indexLabel($labelId, null)) {
                $indexed[] = $labelId;
            }
        }
        $this->index->deleteOtherLabels($indexed);
        // Magento cleans these tags (block cache and FPC) after the indexer run, i.e. once the rows exist.
        $this->cacheContext->registerTags([Label::CACHE_TAG]);
    }

    /**
     * Rebuilds one label (after it was saved); an inactive label just loses its rows.
     *
     * @param int $labelId
     * @return void
     */
    public function reindexLabel(int $labelId): void
    {
        if (!$this->indexLabel($labelId, null)) {
            $this->index->deleteByLabel($labelId);
        }
    }

    /**
     * Rebuilds some products and their configurable/grouped parents.
     *
     * @param int[] $productIds
     * @return void
     */
    public function reindexProducts(array $productIds): void
    {
        $productIds = array_values(array_unique(array_map('intval', $productIds)));
        if (!$productIds) {
            return;
        }
        $parentIds = $this->flatten($this->parentProvider->getParentsByChild($productIds));
        $affected = array_values(array_unique(array_merge($productIds, $parentIds)));
        // A parent row depends on all its children: evaluate the siblings too, write only $affected.
        $scope = array_values(array_unique(array_merge(
            $affected,
            $this->flatten($this->parentProvider->getChildrenByParent($parentIds))
        )));

        $this->index->deleteByProducts($affected);
        foreach ($this->getActiveLabelIds() as $labelId) {
            $this->indexLabel($labelId, $scope, $affected);
        }
        // With Update by Schedule the rows change after the product save cleaned the cache.
        $this->cacheContext->registerEntities(Product::CACHE_TAG, $affected);
    }

    /**
     * Writes the rows of one label if it is active; only one label is kept in memory at a time.
     *
     * @param int $labelId
     * @param int[]|null $scope products to evaluate; null for the whole catalog
     * @param int[]|null $affected products to write; null for all
     * @return bool whether the label is active
     */
    private function indexLabel(int $labelId, ?array $scope, ?array $affected = null): bool
    {
        $isActive = false;
        foreach ($this->getActiveLabels([$labelId]) as $label) {
            $this->writeRows($label, $scope, $affected);
            $isActive = true;
        }
        unset($label);
        // The label and its condition tree reference each other: free them now.
        gc_collect_cycles();
        return $isActive;
    }

    /**
     * Writes the index rows of one label, one store view at a time.
     *
     * @param Label $label
     * @param int[]|null $scope
     * @param int[]|null $affected
     * @return void
     */
    private function writeRows(Label $label, ?array $scope, ?array $affected): void
    {
        $labelId = (int) $label->getLabelId();
        $manual = $this->labelProduct->getProductIds($labelId);
        if ($scope !== null) {
            $manual = array_values(array_intersect($manual, $scope));
        }
        $isAffected = $affected === null ? null : array_flip($affected);

        $storeIds = $this->getStoreIds($label);
        foreach ($storeIds as $storeId) {
            $matching = $this->matcher->getMatchingProductIds($label, $storeId, $scope);
            // Disabled products get no rows, so a disabled child assigned by hand does not label its parent.
            $enabledManual = $this->matcher->getEnabledProductIds($manual, $storeId);
            $parentsByChild = $label->getUseForParent()
                ? $this->parentProvider->getParentsByChild([...$matching, ...$enabledManual])
                : [];
            $rows = $this->rowBuilder->build(
                $labelId,
                [$storeId],
                [$storeId => $matching],
                $enabledManual,
                $parentsByChild,
                $label->getUseForParent()
            );
            if ($isAffected !== null) {
                $rows = array_values(array_filter(
                    $rows,
                    static fn(array $row): bool => isset($isAffected[$row['product_id']])
                ));
            }
            if ($scope === null) {
                $this->index->replaceRows($labelId, $storeId, $rows);
            } else {
                $this->index->insertRows($rows);
            }
        }
        if ($scope === null) {
            $this->index->deleteByLabel($labelId, $storeIds);
        }
    }

    /**
     * Store views of a label; "All Store Views" (0) means every store view.
     *
     * @param Label $label
     * @return int[]
     */
    private function getStoreIds(Label $label): array
    {
        $existing = array_map('intval', array_keys($this->storeManager->getStores()));
        $storeIds = $label->getStoreIds();
        return in_array(0, $storeIds, true) ? $existing : array_values(array_intersect($storeIds, $existing));
    }

    /**
     * Ids of the active labels (without loading them).
     *
     * @return int[]
     */
    private function getActiveLabelIds(): array
    {
        return array_map(
            'intval',
            $this->labelCollectionFactory->create()->addFieldToFilter('is_active', 1)->getAllIds()
        );
    }

    /**
     * Active labels among some ids.
     *
     * @param int[] $labelIds
     * @return Label[]
     */
    private function getActiveLabels(array $labelIds): array
    {
        return $this->labelCollectionFactory->create()
            ->addFieldToFilter('is_active', 1)
            ->addFieldToFilter('label_id', ['in' => $labelIds])
            ->getItems();
    }

    /**
     * Values of a map of lists, as one list without duplicates.
     *
     * @param array<int,int[]> $map
     * @return int[]
     */
    private function flatten(array $map): array
    {
        return array_values(array_unique(array_merge([], ...array_values($map))));
    }
}
