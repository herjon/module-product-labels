<?php
declare(strict_types=1);

namespace Majistar\ProductLabels\Test\Unit\Plugin\Checkout;

use Majistar\ProductLabels\Model\View\PurchasedProduct;
use Majistar\ProductLabels\Plugin\Catalog\AddBadgesToImage;
use Majistar\ProductLabels\Plugin\Checkout\MarkCartImage;
use Magento\Catalog\Block\Product\Image;
use Magento\Catalog\Model\Product;
use Magento\Checkout\Block\Cart\Item\Renderer;
use Magento\Quote\Model\Quote\Item;
use Magento\Quote\Model\Quote\Item\Option;
use PHPUnit\Framework\TestCase;

class MarkCartImageTest extends TestCase
{
    private function image(): Image
    {
        return (new \ReflectionClass(Image::class))->newInstanceWithoutConstructor();
    }

    private function renderer(Item $item): Renderer
    {
        $renderer = $this->createStub(Renderer::class);
        $renderer->method('getItem')->willReturn($item);
        return $renderer;
    }

    public function testMarksTheThumbnailWithThePurchasedVariant(): void
    {
        $parent = $this->createStub(Product::class);
        $child = $this->createStub(Product::class);
        $option = $this->createStub(Option::class);
        $option->method('getProduct')->willReturn($child);
        $item = $this->createStub(Item::class);
        $item->method('getProduct')->willReturn($parent);
        $item->method('getOptionByCode')->willReturnMap([['simple_product', $option]]);
        $image = $this->image();

        (new MarkCartImage(new PurchasedProduct()))
            ->afterGetImage($this->renderer($item), $image, $parent, 'cart_page_product_thumbnail');

        self::assertSame(['product' => $child, 'area' => 'cart'], $image->getData(AddBadgesToImage::TARGET));
    }

    public function testOtherImagesOfTheRendererAreNotMarked(): void
    {
        $item = $this->createStub(Item::class);
        $item->method('getProduct')->willReturn($this->createStub(Product::class));
        $image = $this->image();

        (new MarkCartImage(new PurchasedProduct()))
            ->afterGetImage(
                $this->renderer($item),
                $image,
                $this->createStub(Product::class),
                'mini_cart_product_thumbnail'
            );

        self::assertNull($image->getData(AddBadgesToImage::TARGET));
    }
}
