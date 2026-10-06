<?php
declare(strict_types=1);

namespace Majistar\ProductLabels\Model\ResourceModel\Label\Grid;

use Majistar\ProductLabels\Model\ResourceModel\Label as LabelResource;
use Magento\Framework\View\Element\UiComponent\DataProvider\SearchResult;

/**
 * Grid rows: label columns plus the type of the product page appearance.
 */
class Collection extends SearchResult
{
    /**
     * @inheritdoc
     */
    protected function _initSelect()
    {
        parent::_initSelect();
        $this->getSelect()->joinLeft(
            ['appearance' => $this->getTable(LabelResource::APPEARANCE_TABLE)],
            sprintf(
                "appearance.label_id = main_table.label_id AND appearance.context = '%s'",
                LabelResource::CONTEXT_PRODUCT
            ),
            ['type' => 'appearance.type']
        );
        $this->addFilterToMap('label_id', 'main_table.label_id');
        $this->addFilterToMap('type', 'appearance.type');
        return $this;
    }
}
