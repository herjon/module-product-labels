<?php
declare(strict_types=1);

namespace Majistar\ProductLabels\Model\Source;

use Majistar\ProductLabels\Model\Config;

/**
 * Options of "New" Means.
 */
class NewSource extends AbstractOptions
{
    /**
     * @inheritdoc
     */
    protected function options(): array
    {
        return [
            Config::NEW_SOURCE_NEWS_DATES => __('"Set Product as New" dates of the product'),
            Config::NEW_SOURCE_CREATED_DAYS => __('Days since the product was created'),
        ];
    }
}
