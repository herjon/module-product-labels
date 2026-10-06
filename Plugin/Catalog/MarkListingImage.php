<?php
declare(strict_types=1);

namespace Majistar\ProductLabels\Plugin\Catalog;

use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Block\Product\AbstractProduct;
use Magento\Catalog\Block\Product\Image;

/**
 * Marks product card images, so AddBadgesToImage draws the badges over them.
 */
class MarkListingImage
{
    public const AREA = 'majistar_product_labels_area';

    /**
     * Marks the image of a card rendered by a block with the "majistar_product_labels_area" argument.
     *
     * @param AbstractProduct $subject
     * @param Image|mixed $result
     * @param ProductInterface|mixed $product
     * @return Image|mixed
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function afterGetImage(AbstractProduct $subject, $result, $product)
    {
        $area = (string) $subject->getData(self::AREA);
        if ($area !== '' && $result instanceof Image && $product instanceof ProductInterface) {
            $result->setData(AddBadgesToImage::TARGET, ['product' => $product, 'area' => $area]);
        }
        return $result;
    }
}
