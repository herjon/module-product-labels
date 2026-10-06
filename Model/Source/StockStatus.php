<?php
declare(strict_types=1);

namespace Majistar\ProductLabels\Model\Source;

use Majistar\ProductLabels\Api\Data\LabelInterface;

class StockStatus extends AbstractOptions
{
    /**
     * @inheritdoc
     */
    protected function options(): array
    {
        return [
            LabelInterface::STOCK_ANY => __('Any'),
            LabelInterface::STOCK_IN => __('In stock'),
            LabelInterface::STOCK_OUT => __('Out of stock'),
            LabelInterface::STOCK_LOW => __('Low stock'),
        ];
    }
}
