<?php
declare(strict_types=1);

namespace Majistar\ProductLabels\Model\Source;

use Majistar\ProductLabels\Api\Data\AppearanceInterface;

/**
 * Positions on the 3x3 grid over the product image.
 */
class Position extends AbstractOptions
{
    /**
     * @inheritdoc
     */
    protected function options(): array
    {
        return [
            AppearanceInterface::POSITION_TOP_LEFT => __('Top left'),
            AppearanceInterface::POSITION_TOP_CENTER => __('Top center'),
            AppearanceInterface::POSITION_TOP_RIGHT => __('Top right'),
            AppearanceInterface::POSITION_MIDDLE_LEFT => __('Middle left'),
            AppearanceInterface::POSITION_CENTER => __('Center'),
            AppearanceInterface::POSITION_MIDDLE_RIGHT => __('Middle right'),
            AppearanceInterface::POSITION_BOTTOM_LEFT => __('Bottom left'),
            AppearanceInterface::POSITION_BOTTOM_CENTER => __('Bottom center'),
            AppearanceInterface::POSITION_BOTTOM_RIGHT => __('Bottom right'),
        ];
    }
}
