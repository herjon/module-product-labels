<?php
declare(strict_types=1);

namespace Majistar\ProductLabels\Model\View;

use Majistar\ProductLabels\Api\Data\LabelInterface;

/**
 * Picks which of the applicable labels are shown: priority, "Stop Further Labels", maximum, out-of-stock rule.
 */
class LabelSelector
{
    /**
     * Labels to show, in display order.
     *
     * @param LabelInterface[] $labels labels that passed the filter
     * @param int $maxLabels
     * @param bool $outOfStockOnly global setting "Out of Stock Products Show Only Out-of-Stock Labels"
     * @param bool $productOutOfStock
     * @return LabelInterface[]
     */
    public function select(array $labels, int $maxLabels, bool $outOfStockOnly, bool $productOutOfStock): array
    {
        if ($outOfStockOnly && $productOutOfStock) {
            $labels = array_filter(
                $labels,
                static fn(LabelInterface $label): bool => $label->getStockStatus() === LabelInterface::STOCK_OUT
            );
        }
        usort(
            $labels,
            static fn(LabelInterface $a, LabelInterface $b): int
                => [$a->getPriority(), $a->getLabelId()] <=> [$b->getPriority(), $b->getLabelId()]
        );
        $selected = [];
        foreach ($labels as $label) {
            $selected[] = $label;
            if ($label->getStopProcessing() || count($selected) >= $maxLabels) {
                break;
            }
        }
        return $selected;
    }
}
