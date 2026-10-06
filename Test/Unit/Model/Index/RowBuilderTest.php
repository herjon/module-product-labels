<?php
declare(strict_types=1);

namespace Majistar\ProductLabels\Test\Unit\Model\Index;

use Majistar\ProductLabels\Model\Index\RowBuilder;
use PHPUnit\Framework\TestCase;

class RowBuilderTest extends TestCase
{
    /**
     * @param array<int,array{label_id:int,product_id:int,store_id:int,manual:int}> $rows
     * @return string[] "store:product:manual"
     */
    private function compact(array $rows): array
    {
        return array_map(
            static fn(array $r): string => $r['store_id'] . ':' . $r['product_id'] . ':' . $r['manual'],
            $rows
        );
    }

    public function testMatchingProductsBecomeRowsPerStore(): void
    {
        $rows = (new RowBuilder())->build(5, [1, 2], [1 => [10, 11], 2 => [11]], [], [], false);

        self::assertSame(['1:10:0', '1:11:0', '2:11:0'], $this->compact($rows));
        self::assertSame(5, $rows[0]['label_id']);
    }

    public function testManualProductsAreRowsInEveryStoreAndWinOverMatching(): void
    {
        $rows = (new RowBuilder())->build(5, [1, 2], [1 => [10]], [10, 20], [], false);

        self::assertSame(['1:10:1', '1:20:1', '2:10:1', '2:20:1'], $this->compact($rows));
    }

    public function testParentsAreAddedOnlyWithUseForParent(): void
    {
        $parents = [10 => [100], 11 => [100, 101]];

        $withoutParents = (new RowBuilder())->build(5, [1], [1 => [10, 11]], [], $parents, false);
        self::assertSame(['1:10:0', '1:11:0'], $this->compact($withoutParents));
        self::assertSame(
            ['1:10:0', '1:11:0', '1:100:0', '1:101:0'],
            $this->compact((new RowBuilder())->build(5, [1], [1 => [10, 11]], [], $parents, true))
        );
    }

    public function testParentTakesTheManualFlagWhenAnyChildIsManual(): void
    {
        $rows = (new RowBuilder())->build(5, [1], [1 => [10]], [11], [10 => [100], 11 => [100]], true);

        self::assertSame(['1:10:0', '1:11:1', '1:100:1'], $this->compact($rows));
    }

    public function testNothingToIndex(): void
    {
        self::assertSame([], (new RowBuilder())->build(5, [1], [], [], [], true));
    }
}
