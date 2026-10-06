<?php
declare(strict_types=1);

namespace Majistar\ProductLabels\Observer;

use Majistar\ProductLabels\Model\ResourceModel\LabelProduct;
use Magento\Framework\DataObject;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;

class DeleteProductAssignments implements ObserverInterface
{
    /**
     * @param LabelProduct $labelProduct
     */
    public function __construct(
        private readonly LabelProduct $labelProduct
    ) {
    }

    /**
     * @inheritdoc
     */
    public function execute(Observer $observer): void
    {
        $product = $observer->getEvent()->getData('product');
        if ($product instanceof DataObject && $product->getId()) {
            $this->labelProduct->deleteByProductId((int) $product->getId());
        }
    }
}
