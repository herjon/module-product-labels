<?php
declare(strict_types=1);

namespace Majistar\ProductLabels\Model\Source;

use Majistar\ProductLabels\Api\Data\AppearanceInterface;

class TextSize extends AbstractOptions
{
    /**
     * @inheritdoc
     */
    protected function options(): array
    {
        return [
            AppearanceInterface::TEXT_SIZE_SMALL => __('Small'),
            AppearanceInterface::TEXT_SIZE_MEDIUM => __('Medium'),
            AppearanceInterface::TEXT_SIZE_LARGE => __('Large'),
        ];
    }
}
