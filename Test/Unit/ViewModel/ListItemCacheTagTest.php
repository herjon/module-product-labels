<?php
declare(strict_types=1);

namespace Majistar\ProductLabels\Test\Unit\ViewModel;

use Majistar\ProductLabels\ViewModel\ListItemCacheTag;
use Magento\Catalog\Model\Product;
use Magento\Framework\View\Element\AbstractBlock;
use Magento\Framework\View\Element\Template;
use PHPUnit\Framework\TestCase;

class ListItemCacheTagTest extends TestCase
{
    public function testAddsTheLabelTagOnce(): void
    {
        // A block without its constructor: setData/getData come from DataObject.
        $block = new class extends Template {
            public function __construct()
            {
            }
        };
        $block->setData('cache_tags', ['cat_p_5']);
        $processor = new ListItemCacheTag();

        $processor->beforeListItemToHtml($block, $this->createStub(Product::class));
        $processor->beforeListItemToHtml($block, $this->createStub(Product::class));

        self::assertSame(['cat_p_5', 'majistar_product_label'], $block->getData('cache_tags'));
        self::assertInstanceOf(AbstractBlock::class, $block);
    }
}
