<?php
declare(strict_types=1);

namespace Majistar\ProductLabels\Model\Source;

use Majistar\ProductLabels\Model\ResourceModel\Label as LabelResource;
use Magento\Framework\Data\OptionSourceInterface;

/**
 * Every label by priority, inactive ones too, so saving a product keeps assignments to disabled labels.
 */
class Labels implements OptionSourceInterface
{
    /**
     * @param LabelResource $labelResource
     */
    public function __construct(
        private readonly LabelResource $labelResource
    ) {
    }

    /**
     * @inheritdoc
     */
    public function toOptionArray(): array
    {
        $options = [];
        foreach ($this->labelResource->getOptionRows() as $row) {
            $name = (string) $row['name'];
            $options[] = [
                'value' => (string) $row['label_id'],
                'label' => (int) $row['is_active'] ? $name : (string) __('%1 (inactive)', $name),
            ];
        }
        return $options;
    }
}
