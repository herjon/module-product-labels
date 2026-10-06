<?php
declare(strict_types=1);

namespace Majistar\ProductLabels\Model\Source;

/**
 * Variables of the label text, replaced on the storefront.
 */
class Variable extends AbstractOptions
{
    /**
     * @inheritdoc
     */
    protected function options(): array
    {
        return [
            '{SAVE_PERCENT}' => __('Discount percent'),
            '{SAVE_AMOUNT}' => __('Discount amount'),
            '{PRICE}' => __('Price'),
            '{SPECIAL_PRICE}' => __('Special price'),
            '{STOCK_QTY}' => __('Quantity in stock'),
            '{SKU}' => __('SKU'),
            '{ATTR:code}' => __('Product attribute (replace "code" with the attribute code)'),
        ];
    }
}
