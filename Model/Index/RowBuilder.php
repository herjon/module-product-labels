<?php
declare(strict_types=1);

namespace Majistar\ProductLabels\Model\Index;

/**
 * Turns the products matching a label into index rows; manual assignments (manual = 1) win.
 */
class RowBuilder
{
    /**
     * Rows of one label, ordered by store and product.
     *
     * @param int $labelId
     * @param int[] $storeIds store views of the label (no 0)
     * @param array<int,int[]> $matchingByStore store id => ids of the products that match
     * @param int[] $manualProductIds products assigned by hand
     * @param array<int,int[]> $parentsByChild child id => parent ids
     * @param bool $useForParent
     * @return array<int,array{label_id:int,product_id:int,store_id:int,manual:int}>
     */
    public function build(
        int $labelId,
        array $storeIds,
        array $matchingByStore,
        array $manualProductIds,
        array $parentsByChild,
        bool $useForParent
    ): array {
        $rows = [];
        foreach ($storeIds as $storeId) {
            $manualById = [];
            foreach ($matchingByStore[$storeId] ?? [] as $productId) {
                $manualById[(int) $productId] = 0;
            }
            foreach ($manualProductIds as $productId) {
                $manualById[(int) $productId] = 1;
            }
            if ($useForParent) {
                foreach ($manualById as $childId => $manual) {
                    foreach ($parentsByChild[$childId] ?? [] as $parentId) {
                        $manualById[(int) $parentId] = max($manualById[(int) $parentId] ?? 0, $manual);
                    }
                }
            }
            ksort($manualById);
            foreach ($manualById as $productId => $manual) {
                $rows[] = [
                    'label_id' => $labelId,
                    'product_id' => $productId,
                    'store_id' => (int) $storeId,
                    'manual' => $manual,
                ];
            }
        }
        return $rows;
    }
}
