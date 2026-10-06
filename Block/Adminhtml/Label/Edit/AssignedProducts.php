<?php
declare(strict_types=1);

namespace Majistar\ProductLabels\Block\Adminhtml\Label\Edit;

use Majistar\ProductLabels\Model\ResourceModel\LabelProduct;
use Magento\Backend\Block\Template;
use Magento\Backend\Block\Template\Context;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory as ProductCollectionFactory;

/**
 * Read-only list of the products assigned to the label by hand.
 */
class AssignedProducts extends Template
{
    public const LIMIT = 50;

    /**
     * @var string
     */
    protected $_template = 'Majistar_ProductLabels::label/assigned-products.phtml';

    /**
     * @param Context $context
     * @param LabelProduct $labelProduct
     * @param ProductCollectionFactory $productCollectionFactory
     * @param array $data
     */
    public function __construct(
        Context $context,
        private readonly LabelProduct $labelProduct,
        private readonly ProductCollectionFactory $productCollectionFactory,
        array $data = []
    ) {
        parent::__construct($context, $data);
    }

    /**
     * First products assigned to the label.
     *
     * @return array<int, array{id: int, sku: string, name: string, url: string}>
     */
    public function getProducts(): array
    {
        $labelId = $this->getLabelId();
        $ids = $labelId ? $this->labelProduct->getProductIds($labelId, self::LIMIT) : [];
        if (!$ids) {
            return [];
        }
        $collection = $this->productCollectionFactory->create()
            ->addAttributeToSelect('name')
            ->addIdFilter($ids)
            ->setOrder('entity_id', 'ASC');
        $products = [];
        foreach ($collection as $product) {
            $products[] = [
                'id' => (int) $product->getId(),
                'sku' => (string) $product->getSku(),
                'name' => (string) $product->getName(),
                'url' => $this->getUrl('catalog/product/edit', ['id' => $product->getId()]),
            ];
        }
        return $products;
    }

    /**
     * Number of products assigned to the label.
     *
     * @return int
     */
    public function getTotal(): int
    {
        $labelId = $this->getLabelId();
        return $labelId ? $this->labelProduct->countProducts($labelId) : 0;
    }

    /**
     * Id of the edited label (0 for a new one).
     *
     * @return int
     */
    private function getLabelId(): int
    {
        return (int) $this->getRequest()->getParam('id');
    }
}
