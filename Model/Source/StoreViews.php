<?php
declare(strict_types=1);

namespace Majistar\ProductLabels\Model\Source;

use Magento\Store\Ui\Component\Listing\Column\Store\Options;

/**
 * Store views grouped by website, with "All Store Views" (0) first.
 */
class StoreViews extends Options
{
    /**
     * @inheritdoc
     */
    public function toOptionArray()
    {
        if ($this->options !== null) {
            return $this->options;
        }
        $this->currentOptions['All Store Views']['label'] = __('All Store Views');
        $this->currentOptions['All Store Views']['value'] = '0';
        $this->generateCurrentOptions();
        $this->options = array_values($this->currentOptions);
        return $this->options;
    }
}
