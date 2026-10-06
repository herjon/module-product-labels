<?php
declare(strict_types=1);

namespace Majistar\ProductLabels\Model\Source;

use Majistar\ProductLabels\Api\Data\AppearanceInterface;

class Type extends AbstractOptions
{
    /**
     * @inheritdoc
     */
    protected function options(): array
    {
        return [
            AppearanceInterface::TYPE_TEXT => __('Text'),
            AppearanceInterface::TYPE_IMAGE => __('Image'),
        ];
    }
}
