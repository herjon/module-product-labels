<?php
declare(strict_types=1);

namespace Majistar\ProductLabels\Test\Unit\Model;

use Majistar\ProductLabels\Model\ProductLabelAssignment;
use Majistar\ProductLabels\Model\ResourceModel\LabelProduct;
use Magento\Framework\Event\ManagerInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use PHPUnit\Framework\TestCase;

class ProductLabelAssignmentTest extends TestCase
{
    public function testReplacesWithUniqueIntegersAndDispatchesEvent(): void
    {
        $resource = $this->createMock(LabelProduct::class);
        $resource->method('productExists')->with(10)->willReturn(true);
        $resource->method('filterExistingLabelIds')->with([2, 3])->willReturn([2, 3]);
        $resource->expects(self::once())->method('replaceLabelIds')->with(10, [2, 3]);

        $events = $this->createMock(ManagerInterface::class);
        $events->expects(self::once())->method('dispatch')->with(
            ProductLabelAssignment::EVENT_SAVE_AFTER,
            ['product_id' => 10, 'label_ids' => [2, 3]]
        );

        self::assertTrue((new ProductLabelAssignment($resource, $events))->setLabelIds(10, ['2', 3, 2]));
    }

    public function testEmptyListRemovesAllAssignments(): void
    {
        $resource = $this->createMock(LabelProduct::class);
        $resource->method('productExists')->willReturn(true);
        $resource->method('filterExistingLabelIds')->willReturn([]);
        $resource->expects(self::once())->method('replaceLabelIds')->with(10, []);

        (new ProductLabelAssignment($resource, $this->createStub(ManagerInterface::class)))->setLabelIds(10, []);
    }

    public function testUnknownLabelIsRejected(): void
    {
        $resource = $this->createMock(LabelProduct::class);
        $resource->method('productExists')->willReturn(true);
        $resource->method('filterExistingLabelIds')->willReturn([2]);
        $resource->expects(self::never())->method('replaceLabelIds');

        $this->expectException(NoSuchEntityException::class);
        $this->expectExceptionMessage('99');
        (new ProductLabelAssignment($resource, $this->createStub(ManagerInterface::class)))->setLabelIds(10, [2, 99]);
    }

    public function testUnknownProductIsRejected(): void
    {
        $resource = $this->createMock(LabelProduct::class);
        $resource->method('productExists')->willReturn(false);
        $resource->expects(self::never())->method('replaceLabelIds');

        $this->expectException(NoSuchEntityException::class);
        (new ProductLabelAssignment($resource, $this->createStub(ManagerInterface::class)))->setLabelIds(404, [1]);
    }
}
