<?php
declare(strict_types=1);

namespace Majistar\ProductLabels\Plugin\Catalog;

use Majistar\ProductLabels\Model\ResourceModel\LabelProduct;
use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Api\Data\ProductSearchResultsInterface;
use Magento\Catalog\Api\ProductRepositoryInterface;

/**
 * Web API: adds the ids of the labels assigned by hand to products read through the repository.
 */
class LoadProductLabels
{
    /**
     * @param LabelProduct $labelProduct
     */
    public function __construct(private readonly LabelProduct $labelProduct)
    {
    }

    /**
     * Labels of a product read by SKU.
     *
     * @param ProductRepositoryInterface $subject
     * @param ProductInterface $result
     * @return ProductInterface
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function afterGet(ProductRepositoryInterface $subject, ProductInterface $result): ProductInterface
    {
        $this->attach([$result]);
        return $result;
    }

    /**
     * Labels of a product read by id.
     *
     * @param ProductRepositoryInterface $subject
     * @param ProductInterface $result
     * @return ProductInterface
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function afterGetById(ProductRepositoryInterface $subject, ProductInterface $result): ProductInterface
    {
        $this->attach([$result]);
        return $result;
    }

    /**
     * Labels of a product list, with one query.
     *
     * @param ProductRepositoryInterface $subject
     * @param ProductSearchResultsInterface $result
     * @return ProductSearchResultsInterface
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function afterGetList(
        ProductRepositoryInterface $subject,
        ProductSearchResultsInterface $result
    ): ProductSearchResultsInterface {
        $this->attach($result->getItems());
        return $result;
    }

    /**
     * Sets the assigned label ids on each product.
     *
     * @param ProductInterface[] $products
     * @return void
     */
    private function attach(array $products): void
    {
        $ids = array_values(array_filter(array_map(static fn($product): int => (int) $product->getId(), $products)));
        $labels = $ids ? $this->labelProduct->getLabelIdsByProducts($ids) : [];
        foreach ($products as $product) {
            $extension = $product->getExtensionAttributes();
            if ($extension !== null && $product->getId()) {
                $extension->setMajistarProductLabels($labels[(int) $product->getId()] ?? []);
            }
        }
    }
}
