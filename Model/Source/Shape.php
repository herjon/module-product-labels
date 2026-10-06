<?php
declare(strict_types=1);

namespace Majistar\ProductLabels\Model\Source;

use Majistar\ProductLabels\Api\Data\AppearanceInterface;

class Shape extends AbstractOptions
{
    /**
     * @inheritdoc
     */
    protected function options(): array
    {
        return [
            AppearanceInterface::SHAPE_RECTANGLE => __('Rectangle'),
            AppearanceInterface::SHAPE_PILL => __('Pill'),
            AppearanceInterface::SHAPE_CIRCLE => __('Circle'),
        ];
    }
}
