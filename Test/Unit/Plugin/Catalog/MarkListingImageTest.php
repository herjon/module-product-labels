<?php
declare(strict_types=1);

namespace Majistar\ProductLabels\Test\Unit\Plugin\Catalog;

use Majistar\ProductLabels\Plugin\Catalog\AddBadgesToImage;
use Majistar\ProductLabels\Plugin\Catalog\MarkListingImage;
use Magento\Catalog\Block\Product\AbstractProduct;
use Magento\Catalog\Block\Product\Image;
use Magento\Catalog\Model\Product;
use PHPUnit\Framework\TestCase;

class MarkListingImageTest extends TestCase
{
    private function image(): Image
    {
        return (new \ReflectionClass(Image::class))->newInstanceWithoutConstructor();
    }

    private function listBlock(?string $area): AbstractProduct
    {
        $block = (new \ReflectionClass(AbstractProduct::class))->newInstanceWithoutConstructor();
        $block->setData(MarkListingImage::AREA, $area);
        return $block;
    }

    public function testMarksTheImageOfACardWithItsProduct(): void
    {
        $product = $this->createStub(Product::class);
        $image = $this->image();

        (new MarkListingImage())->afterGetImage($this->listBlock('listing'), $image, $product, 'category_page_grid');

        self::assertSame(
            ['product' => $product, 'area' => 'listing'],
            $image->getData(AddBadgesToImage::TARGET)
        );
    }

    public function testLeavesImagesOfOtherBlocksAlone(): void
    {
        $image = $this->image();

        (new MarkListingImage())->afterGetImage(
            $this->listBlock(null),
            $image,
            $this->createStub(Product::class),
            'product_base_image'
        );

        self::assertNull($image->getData(AddBadgesToImage::TARGET));
    }
}
