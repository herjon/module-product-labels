<?php
declare(strict_types=1);

namespace Majistar\ProductLabels\Api;

/**
 * Labels to show on products, ready to render (storefront).
 *
 * @api
 */
interface LabelResolverInterface
{
    /**
     * Labels of each product in one area, highest priority first.
     *
     * @param \Magento\Catalog\Api\Data\ProductInterface[] $products
     * @param string $area one of LabelInterface::SHOW_ON_* (product, listing, cart, minicart, checkout)
     * @param int|null $storeId current store when null
     * @param int|null $customerGroupId current customer group when null
     * @return array<int, \Majistar\ProductLabels\Api\Data\LabelViewInterface[]> keyed by product id
     */
    public function resolve(array $products, string $area, ?int $storeId = null, ?int $customerGroupId = null): array;
}
