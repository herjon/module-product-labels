<?php
declare(strict_types=1);

namespace Majistar\ProductLabels\Test\Unit\Plugin\CustomerData;

use Majistar\ProductLabels\Model\View\LabelView;
use Majistar\ProductLabels\Model\View\PurchasedProduct;
use Majistar\ProductLabels\Plugin\CustomerData\AddItemLabels;
use Majistar\ProductLabels\ViewModel\Badges;
use Magento\Catalog\Model\Product;
use Magento\Checkout\CustomerData\AbstractItem;
use Magento\Quote\Model\Quote\Item;
use Magento\Quote\Model\Quote\Item\Option;
use PHPUnit\Framework\TestCase;

class AddItemLabelsTest extends TestCase
{
    public function testMiniCartItemGetsTheBadgesOfThePurchasedVariant(): void
    {
        $parent = $this->createStub(Product::class);
        $child = $this->createStub(Product::class);
        $option = $this->createStub(Option::class);
        $option->method('getProduct')->willReturn($child);
        $item = $this->createStub(Item::class);
        $item->method('getProduct')->willReturn($parent);
        $item->method('getOptionByCode')->willReturnMap([['simple_product', $option]]);

        $view = new LabelView(1, 0, 'text', 'Sale', null, null, 'pill', 's', null, null, 25, 'top-left');
        $badges = $this->createMock(Badges::class);
        $badges->expects(self::once())->method('getBadges')->with($child, 'minicart')->willReturn([$view]);
        $badges->method('toGroups')->with([$view], 'minicart')->willReturn([['position' => 'top-left']]);

        $result = (new AddItemLabels($badges, new PurchasedProduct()))
            ->afterGetItemData($this->createStub(AbstractItem::class), ['product_name' => 'Tee'], $item);

        self::assertSame('Tee', $result['product_name']);
        self::assertSame([['position' => 'top-left']], $result['majistar_product_labels']);
    }

    public function testSimpleProductUsesItself(): void
    {
        $product = $this->createStub(Product::class);
        $item = $this->createStub(Item::class);
        $item->method('getProduct')->willReturn($product);
        $item->method('getOptionByCode')->willReturn(null);

        self::assertSame($product, (new PurchasedProduct())->get($item));
    }
}
