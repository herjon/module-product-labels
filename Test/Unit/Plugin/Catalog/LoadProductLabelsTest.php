<?php
declare(strict_types=1);

namespace Majistar\ProductLabels\Test\Unit\Plugin\Catalog;

use Majistar\ProductLabels\Model\ResourceModel\LabelProduct;
use Majistar\ProductLabels\Plugin\Catalog\LoadProductLabels;
use Magento\Catalog\Api\Data\ProductExtensionInterface;
use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Api\Data\ProductSearchResultsInterface;
use Magento\Catalog\Api\ProductRepositoryInterface;
use PHPUnit\Framework\TestCase;

class LoadProductLabelsTest extends TestCase
{
    /**
     * @param int $id
     * @param int[]|null $expectedLabels labels that must be set on the extension attributes
     */
    private function product(int $id, ?array $expectedLabels): ProductInterface
    {
        $extension = $this->createMock(ProductExtensionInterface::class);
        $extension->expects($expectedLabels === null ? self::never() : self::once())
            ->method('setMajistarProductLabels')->with($expectedLabels ?? []);
        $product = $this->createStub(ProductInterface::class);
        $product->method('getId')->willReturn($id);
        $product->method('getExtensionAttributes')->willReturn($extension);
        return $product;
    }

    public function testProductReadThroughTheApiCarriesItsAssignedLabels(): void
    {
        $resource = $this->createMock(LabelProduct::class);
        $resource->expects(self::once())->method('getLabelIdsByProducts')->with([10])->willReturn([10 => [3, 5]]);
        $product = $this->product(10, [3, 5]);
        $repository = $this->createStub(ProductRepositoryInterface::class);

        self::assertSame($product, (new LoadProductLabels($resource))->afterGet($repository, $product));
    }

    public function testAListIsLoadedWithOneQuery(): void
    {
        $resource = $this->createMock(LabelProduct::class);
        $resource->expects(self::once())->method('getLabelIdsByProducts')->with([10, 11])->willReturn([10 => [3]]);
        $results = $this->createStub(ProductSearchResultsInterface::class);
        $results->method('getItems')->willReturn([$this->product(10, [3]), $this->product(11, [])]);

        (new LoadProductLabels($resource))
            ->afterGetList($this->createStub(ProductRepositoryInterface::class), $results);
    }
}
