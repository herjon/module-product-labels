<?php
declare(strict_types=1);

namespace Majistar\ProductLabels\Test\Unit\Model\View;

use ArrayIterator;
use Majistar\ProductLabels\Model\View\FactsProvider;
use Majistar\ProductLabels\Model\View\StockProvider;
use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\ResourceModel\Product\Collection;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory;
use Magento\Eav\Model\Config as EavConfig;
use Magento\Framework\Pricing\Amount\AmountInterface;
use Magento\Framework\Pricing\Price\PriceInterface;
use Magento\Framework\Pricing\PriceInfo\Base as PriceInfo;
use Magento\InventoryConfigurationApi\Model\IsSourceItemManagementAllowedForProductTypeInterface;
use PHPUnit\Framework\TestCase;

class FactsProviderTest extends TestCase
{
    /**
     * Stock index of the tests: what MSI stores for a configurable (salable, quantity 0) and a simple product.
     */
    private function provider(): FactsProvider
    {
        $stock = $this->createStub(StockProvider::class);
        $stock->method('getStock')->willReturn([
            'MH01' => ['qty' => 0.0, 'is_salable' => true],
            'MH01-XS-Black' => ['qty' => 3.0, 'is_salable' => true],
        ]);
        $collection = $this->createStub(Collection::class);
        $collection->method('setStoreId')->willReturnSelf();
        $collection->method('addIdFilter')->willReturnSelf();
        $collection->method('addAttributeToSelect')->willReturnSelf();
        $collection->method('getIterator')->willReturn(new ArrayIterator([]));
        $collectionFactory = $this->createStub(CollectionFactory::class);
        $collectionFactory->method('create')->willReturn($collection);
        $productTypes = $this->createStub(IsSourceItemManagementAllowedForProductTypeInterface::class);
        $productTypes->method('execute')->willReturnCallback(
            static fn(string $type): bool => in_array($type, ['simple', 'virtual', 'downloadable'], true)
        );

        return new FactsProvider($stock, $collectionFactory, $this->createStub(EavConfig::class), $productTypes);
    }

    private function product(string $sku, string $typeId): Product
    {
        $amount = $this->createStub(AmountInterface::class);
        $amount->method('getValue')->willReturn(50.0);
        $price = $this->createStub(PriceInterface::class);
        $price->method('getAmount')->willReturn($amount);
        $price->method('getValue')->willReturn(false);
        $priceInfo = $this->createStub(PriceInfo::class);
        $priceInfo->method('getPrice')->willReturn($price);
        $product = $this->createStub(Product::class);
        $product->method('getSku')->willReturn($sku);
        $product->method('getTypeId')->willReturn($typeId);
        $product->method('getPriceInfo')->willReturn($priceInfo);
        return $product;
    }

    public function testCompositeProductsHaveNoStockQuantity(): void
    {
        $facts = $this->provider()->collect(
            [1 => $this->product('MH01', 'configurable'), 2 => $this->product('MH01-XS-Black', 'simple')],
            1,
            []
        );

        self::assertNull($facts[1]->qty);
        self::assertTrue($facts[1]->isSalable);
        self::assertSame(3.0, $facts[2]->qty);
    }
}
