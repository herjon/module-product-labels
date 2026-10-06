<?php
declare(strict_types=1);

namespace Majistar\ProductLabels\Model\Source;

class Status extends AbstractOptions
{
    /**
     * @inheritdoc
     */
    protected function options(): array
    {
        return [
            1 => __('Enabled'),
            0 => __('Disabled'),
        ];
    }
}
