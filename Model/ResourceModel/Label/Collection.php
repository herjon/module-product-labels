<?php
declare(strict_types=1);

namespace Majistar\ProductLabels\Model\ResourceModel\Label;

use Majistar\ProductLabels\Api\Data\LabelInterface;
use Majistar\ProductLabels\Model\Label as LabelModel;
use Majistar\ProductLabels\Model\ResourceModel\Label as LabelResource;
use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;

class Collection extends AbstractCollection
{
    /**
     * @var string
     */
    protected $_idFieldName = LabelInterface::LABEL_ID;

    /**
     * @var string
     */
    protected $_eventPrefix = 'majistar_product_label_collection';

    /**
     * @var string
     */
    protected $_eventObject = 'label_collection';

    /**
     * @inheritdoc
     */
    protected function _construct(): void
    {
        $this->_init(LabelModel::class, LabelResource::class);
    }

    /**
     * @inheritdoc
     */
    protected function _afterLoad()
    {
        parent::_afterLoad();
        /** @var LabelResource $resource */
        $resource = $this->getResource();
        $resource->attachRelations($this->_items);
        return $this;
    }
}
