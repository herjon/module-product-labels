<?php
declare(strict_types=1);

namespace Majistar\ProductLabels\Plugin\Checkout;

use Majistar\ProductLabels\Api\Data\LabelInterface;
use Majistar\ProductLabels\Model\View\PurchasedProduct;
use Majistar\ProductLabels\Plugin\Catalog\AddBadgesToImage;
use Magento\Catalog\Block\Product\Image;
use Magento\Checkout\Block\Cart\Item\Renderer;
use Magento\Quote\Model\Quote\Item;

/**
 * Cart page: marks the row thumbnail with the purchased product (the variant of a configurable).
 */
class MarkCartImage
{
    private const CART_THUMBNAIL = 'cart_page_product_thumbnail';

    /**
     * @param PurchasedProduct $purchasedProduct
     */
    public function __construct(private readonly PurchasedProduct $purchasedProduct)
    {
    }

    /**
     * Marks the cart page thumbnail of a quote item.
     *
     * @param Renderer $subject
     * @param Image|mixed $result
     * @param mixed $product
     * @param string|mixed $imageId
     * @return Image|mixed
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function afterGetImage(Renderer $subject, $result, $product, $imageId)
    {
        $item = $subject->getItem();
        if ($imageId === self::CART_THUMBNAIL && $result instanceof Image && $item instanceof Item) {
            $result->setData(AddBadgesToImage::TARGET, [
                'product' => $this->purchasedProduct->get($item),
                'area' => LabelInterface::SHOW_ON_CART,
            ]);
        }
        return $result;
    }
}
