<?php
declare(strict_types=1);

namespace Majistar\ProductLabels\Test\Unit\Model\Index;

use Majistar\ProductLabels\Model\Index\IndexBuilder;
use Majistar\ProductLabels\Model\Index\LabelMatcher;
use Majistar\ProductLabels\Model\Index\ParentProvider;
use Majistar\ProductLabels\Model\Index\RowBuilder;
use Majistar\ProductLabels\Model\Label;
use Majistar\ProductLabels\Model\ResourceModel\Index;
use Majistar\ProductLabels\Model\ResourceModel\Label\Collection;
use Majistar\ProductLabels\Model\ResourceModel\Label\CollectionFactory;
use Majistar\ProductLabels\Model\ResourceModel\LabelProduct;
use Majistar\ProductLabels\Test\Unit\Fixture\CreatesLabels;
use Magento\Framework\Indexer\CacheContext;
use Magento\Store\Api\Data\StoreInterface;
use Magento\Store\Model\StoreManagerInterface;
use PHPUnit\Framework\TestCase;

class IndexBuilderTest extends TestCase
{
    use CreatesLabels;

    /** @var array<int, array{label_id: int, product_id: int, store_id: int, manual: int}> */
    private array $inserted = [];

    /** @var array<int, int[]> store views of the rows of each insertRows() call */
    private array $storesPerInsert = [];

    /** @var int[] number of labels of each label collection load */
    private array $labelsPerLoad = [];

    /** @var string[] writes to the index, in order */
    private array $writes = [];

    /** @var CacheContext cache tags to clean after the indexer run */
    private CacheContext $cacheContext;

    /**
     * Configurable 100 (children 10, 11) and product 20; label 5 matches 10 and 20, 11 is assigned by hand.
     *
     * @param Label[] $activeLabels
     * @param int[] $disabled products disabled in every store
     */
    private function builder(array $activeLabels, Index $index, array $disabled = []): IndexBuilder
    {
        $collectionFactory = $this->createStub(CollectionFactory::class);
        $collectionFactory->method('create')->willReturnCallback(
            fn(): Collection => $this->labelCollection($activeLabels)
        );

        $matcher = $this->createStub(LabelMatcher::class);
        $matcher->method('getMatchingProductIds')->willReturnCallback(
            static fn(Label $label, int $storeId, ?array $scope): array => array_values(array_filter(
                array_diff([10, 20], $disabled),
                static fn(int $id): bool => $scope === null || in_array($id, $scope, true)
            ))
        );
        $matcher->method('getEnabledProductIds')->willReturnCallback(
            static fn(array $ids): array => array_values(array_diff($ids, $disabled))
        );
        $parents = $this->createStub(ParentProvider::class);
        $parents->method('getParentsByChild')->willReturnCallback(
            static fn(array $ids): array => array_intersect_key([10 => [100], 11 => [100]], array_flip($ids))
        );
        $parents->method('getChildrenByParent')->willReturnCallback(
            static fn(array $ids): array => array_intersect_key([100 => [10, 11]], array_flip($ids))
        );
        $labelProduct = $this->createStub(LabelProduct::class);
        $labelProduct->method('getProductIds')->willReturn([11]);
        $storeManager = $this->createStub(StoreManagerInterface::class);
        $storeManager->method('getStores')->willReturn([
            1 => $this->createStub(StoreInterface::class),
            2 => $this->createStub(StoreInterface::class),
        ]);

        $this->cacheContext = new CacheContext();

        return new IndexBuilder(
            $collectionFactory,
            $matcher,
            $parents,
            $labelProduct,
            $index,
            new RowBuilder(),
            $storeManager,
            $this->cacheContext
        );
    }

    /**
     * Label collection over the active labels that understands the label_id filter.
     *
     * @param Label[] $activeLabels
     */
    private function labelCollection(array $activeLabels): Collection
    {
        $ids = null;
        $collection = $this->createStub(Collection::class);
        $collection->method('addFieldToFilter')->willReturnCallback(
            function (string $field, $condition) use ($collection, &$ids): Collection {
                if ($field === 'label_id') {
                    $ids = array_map('intval', (array) ($condition['in'] ?? $condition));
                }
                return $collection;
            }
        );
        $selected = static function () use ($activeLabels, &$ids): array {
            return $ids === null ? $activeLabels : array_intersect_key($activeLabels, array_flip($ids));
        };
        $collection->method('getAllIds')->willReturnCallback(static fn(): array => array_keys($selected()));
        $collection->method('getItems')->willReturnCallback(function () use ($selected): array {
            $this->labelsPerLoad[] = count($selected());
            return $selected();
        });
        return $collection;
    }

    /**
     * Index that records its writes; a mock only when the test sets expectations on it.
     */
    private function index(bool $withExpectations = false): Index
    {
        $index = $withExpectations ? $this->createMock(Index::class) : $this->createStub(Index::class);
        $collect = function (array $rows): void {
            $this->inserted = array_merge($this->inserted, $rows);
            $this->storesPerInsert[] = array_values(array_unique(array_column($rows, 'store_id')));
        };
        $index->method('insertRows')->willReturnCallback(function (array $rows) use ($collect): void {
            $this->writes[] = 'insert';
            $collect($rows);
        });
        $index->method('replaceRows')->willReturnCallback(
            function (int $labelId, int $storeId, array $rows) use ($collect): void {
                $this->writes[] = 'replace ' . $labelId . ' in ' . $storeId;
                $collect($rows);
            }
        );
        $index->method('deleteByLabel')->willReturnCallback(function (int $labelId, array $keep = []): void {
            $this->writes[] = 'delete ' . $labelId . ' except stores ' . implode(',', $keep);
        });
        $index->method('deleteOtherLabels')->willReturnCallback(function (array $labelIds): void {
            $this->writes[] = 'delete labels except ' . implode(',', $labelIds);
        });
        return $index;
    }

    /**
     * @return string[] "store:product:manual"
     */
    private function inserted(): array
    {
        $rows = array_map(
            static fn(array $r): string => $r['store_id'] . ':' . $r['product_id'] . ':' . $r['manual'],
            $this->inserted
        );
        sort($rows);
        return $rows;
    }

    private function label(bool $useForParent, array $storeIds = [0], int $labelId = 5): Label
    {
        return $this->createLabel(['label_id' => $labelId, 'is_active' => 1, 'store_ids' => $storeIds])
            ->setUseForParent($useForParent);
    }

    public function testReindexLabelReplacesItsRowsInEveryStoreView(): void
    {
        $this->builder([5 => $this->label(false)], $this->index())->reindexLabel(5);

        self::assertSame(['1:10:0', '1:11:1', '1:20:0', '2:10:0', '2:11:1', '2:20:0'], $this->inserted());
        self::assertSame(['replace 5 in 1', 'replace 5 in 2', 'delete 5 except stores 1,2'], $this->writes);
    }

    public function testInactiveLabelOnlyLosesItsRows(): void
    {
        $index = $this->index(true);
        $index->expects(self::once())->method('deleteByLabel')->with(5);
        $index->expects(self::never())->method('insertRows');
        $index->expects(self::never())->method('replaceRows');

        $this->builder([], $index)->reindexLabel(5);
    }

    public function testOnlyTheStoreViewsOfTheLabel(): void
    {
        $this->builder([5 => $this->label(false, [2])], $this->index())->reindexLabel(5);

        self::assertSame(['2:10:0', '2:11:1', '2:20:0'], $this->inserted());
    }

    public function testUseForParentAddsTheParent(): void
    {
        $this->builder([5 => $this->label(true, [1])], $this->index())->reindexLabel(5);

        self::assertSame(['1:100:1', '1:10:0', '1:11:1', '1:20:0'], $this->inserted());
    }

    public function testDisabledChildrenDoNotLabelTheirParent(): void
    {
        $this->builder([5 => $this->label(true, [1])], $this->index(), [10, 11])->reindexLabel(5);

        self::assertSame(['1:20:0'], $this->inserted());
    }

    public function testReindexOfAChildRebuildsTheParentButNotTheSiblings(): void
    {
        $index = $this->index(true);
        $index->expects(self::once())->method('deleteByProducts')->with([10, 100]);

        $this->builder([5 => $this->label(true, [1])], $index)->reindexProducts([10]);

        self::assertSame(['1:100:1', '1:10:0'], $this->inserted());
    }

    public function testReindexAllReplacesOneLabelAtATimeWithoutEmptyingTheIndex(): void
    {
        $this->builder([5 => $this->label(false, [1]), 6 => $this->label(false, [1], 6)], $this->index())->reindexAll();

        self::assertSame(['1:10:0', '1:10:0', '1:11:1', '1:11:1', '1:20:0', '1:20:0'], $this->inserted());
        self::assertSame(
            [
                'replace 5 in 1',
                'delete 5 except stores 1',
                'replace 6 in 1',
                'delete 6 except stores 1',
                'delete labels except 5,6',
            ],
            $this->writes
        );
    }

    public function testRowsAreWrittenOneStoreViewAtATime(): void
    {
        $this->builder([5 => $this->label(false)], $this->index())->reindexLabel(5);

        self::assertSame([[1], [2]], $this->storesPerInsert);
    }

    public function testLabelsAreLoadedOneAtATime(): void
    {
        $builder = $this->builder([5 => $this->label(false, [1]), 6 => $this->label(false, [1], 6)], $this->index());

        $builder->reindexAll();
        $builder->reindexProducts([20]);

        self::assertSame([1], array_values(array_unique($this->labelsPerLoad)));
        self::assertSame(
            [5 => 4, 6 => 4],
            array_count_values(array_column($this->inserted, 'label_id')),
            'three rows per label from the full reindex, one from the product reindex'
        );
    }

    public function testFullReindexCleansTheCacheOfEveryLabel(): void
    {
        $this->builder([5 => $this->label(false, [1])], $this->index())->reindexAll();

        self::assertSame(['majistar_product_label'], $this->cacheContext->getIdentities());
    }

    public function testProductReindexCleansTheCacheOfTheAffectedProducts(): void
    {
        $this->builder([5 => $this->label(true, [1])], $this->index())->reindexProducts([10]);

        self::assertSame(['cat_p_10', 'cat_p_100'], $this->cacheContext->getIdentities());
    }
}
