<?php
declare(strict_types=1);

namespace Majistar\ProductLabels\Test\Unit\Observer;

use Majistar\ProductLabels\Observer\PrefetchListingBadges;
use Majistar\ProductLabels\ViewModel\Badges;
use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\ResourceModel\Product\Collection;
use Magento\Framework\Event;
use Magento\Framework\Event\Observer;
use PHPUnit\Framework\TestCase;

class PrefetchListingBadgesTest extends TestCase
{
    public function testAListResolvesItsProductsAndTheirVariantsUpFront(): void
    {
        $products = [5 => $this->createStub(Product::class), 9 => $this->createStub(Product::class)];
        $collection = $this->createStub(Collection::class);
        $collection->method('getItems')->willReturn($products);
        $badges = $this->createMock(Badges::class);
        $badges->expects(self::once())->method('prefetch')->with(array_values($products), 'listing');
        $badges->expects(self::once())->method('prefetchVariants')->with(array_values($products), 'listing');

        (new PrefetchListingBadges($badges))->execute(
            new Observer(['event' => new Event(['collection' => $collection])])
        );
    }
}
