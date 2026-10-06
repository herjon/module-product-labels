<?php
declare(strict_types=1);

namespace Majistar\ProductLabels\Test\Unit\Plugin\Catalog;

use Majistar\ProductLabels\Plugin\Catalog\AddBadgesToImage;
use Majistar\ProductLabels\ViewModel\Badges;
use Magento\Catalog\Block\Product\Image;
use Magento\Catalog\Model\Product;
use PHPUnit\Framework\TestCase;

class AddBadgesToImageTest extends TestCase
{
    private function image(?array $target): Image
    {
        $image = (new \ReflectionClass(Image::class))->newInstanceWithoutConstructor();
        $image->setData(AddBadgesToImage::TARGET, $target);
        return $image;
    }

    public function testWrapsAMarkedImageWithItsBadges(): void
    {
        $product = $this->createStub(Product::class);
        $badges = $this->createMock(Badges::class);
        $badges->expects(self::once())->method('renderBadges')->with($product, 'listing')
            ->willReturn("\n<div class=\"absolute\">Sale</div>\n");

        $html = (new AddBadgesToImage($badges))->afterToHtml(
            $this->image(['product' => $product, 'area' => 'listing']),
            '<img src="a.jpg">'
        );

        self::assertSame(
            "<div class=\"relative\"><img src=\"a.jpg\">\n<div class=\"absolute\">Sale</div>\n</div>",
            $html
        );
    }

    public function testImagesWithoutBadgesAreNotWrapped(): void
    {
        $badges = $this->createStub(Badges::class);
        $badges->method('renderBadges')->willReturn("  \n ");

        $html = (new AddBadgesToImage($badges))->afterToHtml(
            $this->image(['product' => $this->createStub(Product::class), 'area' => 'cart']),
            '<img src="a.jpg">'
        );

        self::assertSame('<img src="a.jpg">', $html);
    }

    public function testUnmarkedImagesAreNotTouched(): void
    {
        $badges = $this->createMock(Badges::class);
        $badges->expects(self::never())->method('renderBadges');

        self::assertSame(
            '<img src="a.jpg">',
            (new AddBadgesToImage($badges))->afterToHtml($this->image(null), '<img src="a.jpg">')
        );
    }
}
