<?php
declare(strict_types=1);

namespace Majistar\ProductLabels\Api;

/**
 * Labels assigned to a product by hand, besides those assigned by rules.
 *
 * @api
 */
interface ProductLabelAssignmentInterface
{
    /**
     * Get label ids.
     *
     * @param int $productId
     * @return int[]
     */
    public function getLabelIds(int $productId): array;

    /**
     * Replaces the manual assignments of a product; an empty list removes them all.
     *
     * @param int $productId
     * @param int[] $labelIds
     * @return bool
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function setLabelIds(int $productId, array $labelIds): bool;
}
