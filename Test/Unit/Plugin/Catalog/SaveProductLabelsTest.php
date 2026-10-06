<?php
declare(strict_types=1);

namespace Majistar\ProductLabels\Test\Unit\Plugin\Catalog;

use Majistar\ProductLabels\Api\ProductLabelAssignmentInterface;
use Majistar\ProductLabels\Plugin\Catalog\SaveProductLabels;
use Magento\Catalog\Api\Data\ProductExtensionInterface;
use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Api\ProductRepositoryInterface;
use PHPUnit\Framework\TestCase;

class SaveProductLabelsTest extends TestCase
{
    private function sentProduct(?array $labelIds): ProductInterface
    {
        $extension = $this->createStub(ProductExtensionInterface::class);
        $extension->method('getMajistarProductLabels')->willReturn($labelIds);
        $product = $this->createStub(ProductInterface::class);
        $product->method('getExtensionAttributes')->willReturn($extension);
        return $product;
    }

    public function testSentLabelsReplaceTheAssignedOnesAfterTheSave(): void
    {
        $assignment = $this->createMock(ProductLabelAssignmentInterface::class);
        $assignment->expects(self::once())->method('setLabelIds')->with(10, [3, 5]);
        $savedExtension = $this->createMock(ProductExtensionInterface::class);
        $savedExtension->expects(self::once())->method('setMajistarProductLabels')->with([3, 5]);
        $saved = $this->createStub(ProductInterface::class);
        $saved->method('getId')->willReturn(10);
        $saved->method('getExtensionAttributes')->willReturn($savedExtension);

        $result = (new SaveProductLabels($assignment))->afterSave(
            $this->createStub(ProductRepositoryInterface::class),
            $saved,
            $this->sentProduct([3, 5])
        );

        self::assertSame($saved, $result);
    }

    public function testAProductSavedWithoutTheFieldKeepsItsLabels(): void
    {
        $assignment = $this->createMock(ProductLabelAssignmentInterface::class);
        $assignment->expects(self::never())->method('setLabelIds');
        $saved = $this->createStub(ProductInterface::class);
        $saved->method('getId')->willReturn(10);

        (new SaveProductLabels($assignment))->afterSave(
            $this->createStub(ProductRepositoryInterface::class),
            $saved,
            $this->sentProduct(null)
        );
    }
}
