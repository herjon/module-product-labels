<?php
declare(strict_types=1);

namespace Majistar\ProductLabels\Model\Source;

use Majistar\ProductLabels\Api\Data\LabelInterface;

class ShowOn extends AbstractOptions
{
    /**
     * @inheritdoc
     */
    protected function options(): array
    {
        return [
            LabelInterface::SHOW_ON_PRODUCT => __('Product page'),
            LabelInterface::SHOW_ON_LISTING => __('Product listings (categories, search, widgets, related)'),
            LabelInterface::SHOW_ON_CART => __('Shopping cart page'),
            LabelInterface::SHOW_ON_MINICART => __('Mini-cart'),
            LabelInterface::SHOW_ON_CHECKOUT => __('Checkout'),
        ];
    }
}
