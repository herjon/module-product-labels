<?php
declare(strict_types=1);

namespace Majistar\ProductLabels\Test\Unit\Observer;

use Majistar\ProductLabels\Model\Indexer\LabelIndexer;
use Majistar\ProductLabels\Observer\InvalidateIndexOnCategoryTreeChange;
use Magento\Catalog\Model\Category;
use Magento\Framework\Event;
use Magento\Framework\Event\Observer;
use Magento\Framework\Indexer\IndexerInterface;
use Magento\Framework\Indexer\IndexerRegistry;
use PHPUnit\Framework\TestCase;

class InvalidateIndexOnCategoryTreeChangeTest extends TestCase
{
    private function assertInvalidates(bool $expected, string $eventName, bool $isNew, bool $anchorChanged): void
    {
        $indexer = $this->createMock(IndexerInterface::class);
        $indexer->expects($expected ? self::once() : self::never())->method('invalidate');
        $registry = $this->createMock(IndexerRegistry::class);
        $registry->method('get')->with(LabelIndexer::INDEXER_ID)->willReturn($indexer);
        $category = $this->createStub(Category::class);
        $category->method('isObjectNew')->willReturn($isNew);
        $category->method('dataHasChangedFor')->willReturnCallback(
            static fn(string $field): bool => $field === 'is_anchor' && $anchorChanged
        );

        (new InvalidateIndexOnCategoryTreeChange($registry))->execute(
            new Observer(['event' => new Event(['name' => $eventName, 'category' => $category])])
        );
    }

    public function testMovedCategory(): void
    {
        $this->assertInvalidates(true, 'catalog_category_move_after', false, false);
    }

    public function testAnchorFlagChanged(): void
    {
        $this->assertInvalidates(true, 'catalog_category_save_after', false, true);
    }

    public function testOtherCategoryChanges(): void
    {
        $this->assertInvalidates(false, 'catalog_category_save_after', false, false);
    }

    public function testNewCategory(): void
    {
        $this->assertInvalidates(false, 'catalog_category_save_after', true, true);
    }
}
