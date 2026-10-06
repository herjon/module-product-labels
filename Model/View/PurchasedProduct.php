<?php
declare(strict_types=1);

namespace Majistar\ProductLabels\Model\View;

use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Quote\Model\Quote\Item;

/**
 * The product actually bought by a cart item: the chosen child of a configurable, otherwise the item product.
 */
class PurchasedProduct
{
    /**
     * Product whose labels the cart item shows.
     *
     * @param Item $item
     * @return ProductInterface
     */
    public function get(Item $item): ProductInterface
    {
        $option = $item->getOptionByCode('simple_product');
        $child = $option ? $option->getProduct() : null;
        return $child instanceof ProductInterface ? $child : $item->getProduct();
    }
}
