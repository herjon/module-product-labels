<?php
declare(strict_types=1);

namespace Majistar\ProductLabels\Model\View;

use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory;
use Magento\Eav\Model\Config as EavConfig;
use Magento\InventoryConfigurationApi\Model\IsSourceItemManagementAllowedForProductTypeInterface;

/**
 * Builds the ProductFacts of the products of a page with as few queries as possible.
 */
class FactsProvider
{
    private const NEWS_ATTRIBUTES = ['news_from_date', 'news_to_date'];

    /**
     * @param StockProvider $stockProvider
     * @param CollectionFactory $productCollectionFactory
     * @param EavConfig $eavConfig
     * @param IsSourceItemManagementAllowedForProductTypeInterface $hasOwnQuantity
     */
    public function __construct(
        private readonly StockProvider $stockProvider,
        private readonly CollectionFactory $productCollectionFactory,
        private readonly EavConfig $eavConfig,
        private readonly IsSourceItemManagementAllowedForProductTypeInterface $hasOwnQuantity
    ) {
    }

    /**
     * Facts of each product.
     *
     * @param array<int,ProductInterface> $productsById
     * @param int $storeId
     * @param string[] $attributeCodes attributes used by {ATTR:code}
     * @return array<int,ProductFacts>
     */
    public function collect(array $productsById, int $storeId, array $attributeCodes): array
    {
        if (!$productsById) {
            return [];
        }
        $attributeCodes = array_values(array_filter($attributeCodes, [$this, 'isProductAttribute']));
        $skus = array_map(
            static fn(ProductInterface $product): string => (string) $product->getSku(),
            array_values($productsById)
        );
        $stock = $this->stockProvider->getStock($skus, $storeId);
        $values = $this->loadValues(array_keys($productsById), $storeId, $attributeCodes);

        $facts = [];
        foreach ($productsById as $productId => $product) {
            /** @var Product $product */
            $sku = (string) $product->getSku();
            $priceInfo = $product->getPriceInfo();
            $specialPrice = $priceInfo->getPrice('special_price')->getValue();
            $productValues = $values[$productId] ?? [];
            $facts[$productId] = new ProductFacts(
                (int) $productId,
                $sku,
                (float) $priceInfo->getPrice('final_price')->getAmount()->getValue(),
                (float) $priceInfo->getPrice('regular_price')->getAmount()->getValue(),
                $specialPrice === false || $specialPrice === null ? null : (float) $specialPrice,
                $stock[$sku]['is_salable'] ?? (bool) $product->isSalable(),
                // Configurable, grouped and bundle products have quantity 0 in the stock index: unknown.
                $this->hasOwnQuantity->execute((string) $product->getTypeId()) ? ($stock[$sku]['qty'] ?? null) : null,
                isset($productValues['news_from_date']) ? substr($productValues['news_from_date'], 0, 10) : null,
                isset($productValues['news_to_date']) ? substr($productValues['news_to_date'], 0, 10) : null,
                $productValues['created_at'] ?? null,
                array_intersect_key($productValues, array_flip($attributeCodes))
            );
        }
        return $facts;
    }

    /**
     * Whether a product attribute with this code exists.
     *
     * @param string $code
     * @return bool
     */
    public function isProductAttribute(string $code): bool
    {
        return (bool) $this->eavConfig->getAttribute(Product::ENTITY, $code)->getId();
    }

    /**
     * News dates, creation date and attribute values (text) per product.
     *
     * @param int[] $productIds
     * @param int $storeId
     * @param string[] $attributeCodes
     * @return array<int,array<string,string>>
     */
    private function loadValues(array $productIds, int $storeId, array $attributeCodes): array
    {
        $codes = array_values(array_unique(array_merge(self::NEWS_ATTRIBUTES, $attributeCodes)));
        $collection = $this->productCollectionFactory->create()
            ->setStoreId($storeId)
            ->addIdFilter($productIds)
            ->addAttributeToSelect($codes);
        $values = [];
        foreach ($collection as $product) {
            $row = ['created_at' => (string) $product->getCreatedAt()];
            foreach ($codes as $code) {
                $value = $this->textValue($product, $code);
                if ($value !== '') {
                    $row[$code] = $value;
                }
            }
            $values[(int) $product->getId()] = $row;
        }
        return $values;
    }

    /**
     * Value of an attribute as shown to customers ("Red", not the option id).
     *
     * @param Product $product
     * @param string $code
     * @return string
     */
    private function textValue(Product $product, string $code): string
    {
        $attribute = $product->getResource()->getAttribute($code);
        $value = $attribute && $attribute->usesSource() ? $product->getAttributeText($code) : $product->getData($code);
        if (is_array($value)) {
            $value = implode(', ', $value);
        }
        return $value === null || $value === false ? '' : (string) $value;
    }
}
