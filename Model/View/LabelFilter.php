<?php
declare(strict_types=1);

namespace Majistar\ProductLabels\Model\View;

use DateTimeImmutable;
use DateTimeZone;
use Majistar\ProductLabels\Api\Data\LabelInterface;
use Majistar\ProductLabels\Model\Config;

/**
 * Decides whether a label applies to a product right now (the parts that change often).
 */
class LabelFilter
{
    /**
     * Whether the label applies.
     *
     * @param LabelInterface $label
     * @param ProductFacts $facts
     * @param FilterContext $context
     * @param bool $manual assigned by hand: quick filters do not apply
     * @return bool
     */
    public function passes(LabelInterface $label, ProductFacts $facts, FilterContext $context, bool $manual): bool
    {
        return $this->isVisible($label, $context) && ($manual || $this->matchesQuickFilters($label, $facts, $context));
    }

    /**
     * Active, inside its period, for this customer group and this area.
     *
     * @param LabelInterface $label
     * @param FilterContext $context
     * @return bool
     */
    private function isVisible(LabelInterface $label, FilterContext $context): bool
    {
        if (!$label->getIsActive()) {
            return false;
        }
        if ($label->getActiveFrom() !== null && $context->now < $label->getActiveFrom()) {
            return false;
        }
        if ($label->getActiveTo() !== null && $context->now > $label->getActiveTo()) {
            return false;
        }
        return in_array($context->customerGroupId, $label->getCustomerGroupIds(), true)
            && in_array($context->area, $label->getShowOn(), true);
    }

    /**
     * New, on sale, stock and price filters.
     *
     * @param LabelInterface $label
     * @param ProductFacts $facts
     * @param FilterContext $context
     * @return bool
     */
    private function matchesQuickFilters(LabelInterface $label, ProductFacts $facts, FilterContext $context): bool
    {
        if ($label->getIsNew() && !$this->isNew($facts, $context)) {
            return false;
        }
        if ($label->getIsOnSale()) {
            $discount = $facts->getDiscountPercent();
            if ($discount <= 0 || $discount < (float) ($label->getMinDiscountPercent() ?? 0)) {
                return false;
            }
        }
        if (!$this->matchesStock($label, $facts)) {
            return false;
        }
        if ($label->getPriceFrom() !== null && $facts->finalPrice < $label->getPriceFrom()) {
            return false;
        }
        return $label->getPriceTo() === null || $facts->finalPrice <= $label->getPriceTo();
    }

    /**
     * "New" as configured in Stores > Configuration > Catalog > Product Labels.
     *
     * @param ProductFacts $facts
     * @param FilterContext $context
     * @return bool
     */
    private function isNew(ProductFacts $facts, FilterContext $context): bool
    {
        if ($context->newSource === Config::NEW_SOURCE_CREATED_DAYS) {
            if ($facts->createdAt === null) {
                return false;
            }
            $limit = (new DateTimeImmutable($context->now, new DateTimeZone('UTC')))
                ->modify(sprintf('-%d days', $context->newDays))
                ->format('Y-m-d H:i:s');
            return $facts->createdAt >= $limit;
        }
        if ($facts->newsFromDate === null && $facts->newsToDate === null) {
            return false;
        }
        return ($facts->newsFromDate === null || $facts->newsFromDate <= $context->today)
            && ($facts->newsToDate === null || $context->today <= $facts->newsToDate);
    }

    /**
     * Stock filter.
     *
     * @param LabelInterface $label
     * @param ProductFacts $facts
     * @return bool
     */
    private function matchesStock(LabelInterface $label, ProductFacts $facts): bool
    {
        return match ($label->getStockStatus()) {
            LabelInterface::STOCK_IN => $facts->isSalable,
            LabelInterface::STOCK_OUT => !$facts->isSalable,
            LabelInterface::STOCK_LOW => $facts->isSalable
                && $facts->qty !== null
                && $facts->qty < (float) $label->getLowStockQty(),
            default => true,
        };
    }
}
