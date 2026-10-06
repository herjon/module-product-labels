<?php
declare(strict_types=1);

namespace Majistar\ProductLabels\Test\Unit\Observer;

use Majistar\ProductLabels\Model\View\PurchasedProduct;
use Majistar\ProductLabels\Observer\AddCheckoutItemLabels;
use Majistar\ProductLabels\ViewModel\Badges;
use Magento\Catalog\Model\Product;
use Magento\Framework\DataObject;
use Magento\Framework\Event;
use Magento\Framework\Event\Observer;
use Magento\Quote\Model\Quote\Item;
use PHPUnit\Framework\TestCase;

class AddCheckoutItemLabelsTest extends TestCase
{
    public function testSummaryRowGetsTheCheckoutBadgesOverItsImage(): void
    {
        $product = $this->createStub(Product::class);
        $item = $this->createStub(Item::class);
        $item->method('getProduct')->willReturn($product);
        $badges = $this->createMock(Badges::class);
        $badges->expects(self::once())->method('renderBadges')->with($product, 'checkout')
            ->willReturn('<div class="absolute">Sale</div>');
        // another module already put something over the image: it is kept
        $transport = new DataObject(['sku' => 'MB01', 'image_overlay_html' => '<span>Gift</span>']);

        (new AddCheckoutItemLabels($badges, new PurchasedProduct()))->execute(
            new Observer(['event' => new Event(['quote_item' => $item, 'transport' => $transport])])
        );

        self::assertSame(
            '<span>Gift</span><div class="absolute">Sale</div>',
            $transport->getData('image_overlay_html')
        );
        self::assertSame('MB01', $transport->getData('sku'));
    }

    public function testOtherEventsAreIgnored(): void
    {
        $badges = $this->createMock(Badges::class);
        $badges->expects(self::never())->method('renderBadges');

        (new AddCheckoutItemLabels($badges, new PurchasedProduct()))->execute(new Observer(['event' => new Event([])]));
    }
}
