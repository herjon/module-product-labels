<?php
declare(strict_types=1);

namespace Majistar\ProductLabels\Test\Unit\Plugin\Store;

use Majistar\ProductLabels\Model\Indexer\LabelIndexer;
use Majistar\ProductLabels\Plugin\Store\InvalidateLabelIndex;
use Magento\Framework\Indexer\IndexerInterface;
use Magento\Framework\Indexer\IndexerRegistry;
use Magento\Store\Model\ResourceModel\Store as StoreResource;
use Magento\Store\Model\Store;
use PHPUnit\Framework\TestCase;

class InvalidateLabelIndexTest extends TestCase
{
    /**
     * @param bool $expected whether the label index is invalidated
     * @param bool $isNew
     * @param string[] $changedFields
     */
    private function assertInvalidates(bool $expected, bool $isNew, array $changedFields): void
    {
        $indexer = $this->createMock(IndexerInterface::class);
        $indexer->expects($expected ? self::once() : self::never())->method('invalidate');
        $registry = $this->createStub(IndexerRegistry::class);
        $registry->method('get')->willReturnMap([[LabelIndexer::INDEXER_ID, $indexer]]);
        $store = $this->createStub(Store::class);
        $store->method('isObjectNew')->willReturn($isNew);
        $store->method('dataHasChangedFor')->willReturnCallback(
            static fn(string $field): bool => in_array($field, $changedFields, true)
        );
        $resource = $this->createStub(StoreResource::class);

        self::assertSame($resource, (new InvalidateLabelIndex($registry))->afterSave($resource, $resource, $store));
    }

    public function testNewStoreView(): void
    {
        $this->assertInvalidates(true, true, []);
    }

    public function testStoreViewMovedToAnotherStore(): void
    {
        $this->assertInvalidates(true, false, ['group_id']);
    }

    public function testStoreViewMovedToAnotherWebsite(): void
    {
        $this->assertInvalidates(true, false, ['website_id']);
    }

    public function testOtherChanges(): void
    {
        $this->assertInvalidates(false, false, ['name', 'sort_order']);
    }
}
