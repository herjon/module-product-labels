<?php
declare(strict_types=1);

namespace Majistar\ProductLabels\Test\Unit\Observer;

use Majistar\ProductLabels\Model\Indexer\Processor;
use Majistar\ProductLabels\Observer\ReindexProducts;
use Magento\Framework\DataObject;
use Magento\Framework\Event;
use Magento\Framework\Event\Observer;
use PHPUnit\Framework\TestCase;

class ReindexProductsTest extends TestCase
{
    private function assertReindexes(array $eventData, array $expectedIds): void
    {
        $processor = $this->createMock(Processor::class);
        if ($expectedIds) {
            $processor->expects(self::once())->method('reindexList')->with($expectedIds);
        } else {
            $processor->expects(self::never())->method('reindexList');
        }
        (new ReindexProducts($processor))->execute(new Observer(['event' => new Event($eventData)]));
    }

    public function testProductSave(): void
    {
        $this->assertReindexes(['product' => new DataObject(['id' => '42'])], [42]);
    }

    public function testCategoryProductsChange(): void
    {
        $this->assertReindexes(['product_ids' => ['3', 4, 3]], [3, 4]);
    }

    public function testManualAssignmentChange(): void
    {
        $this->assertReindexes(['product_id' => 7, 'label_ids' => [1]], [7]);
    }

    public function testNothingToDo(): void
    {
        $this->assertReindexes(['product' => new DataObject()], []);
    }
}
