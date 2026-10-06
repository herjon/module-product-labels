<?php
declare(strict_types=1);

namespace Majistar\ProductLabels\Test\Unit\Model\Index;

use Majistar\ProductLabels\Model\Index\ParentProvider;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\DB\Adapter\AdapterInterface;
use Magento\Framework\DB\Select;
use PHPUnit\Framework\TestCase;

class ParentProviderTest extends TestCase
{
    /**
     * Configurables 100 (10, 11) and 101 (11), grouped 200 (10); 300 only has 10 as a related product.
     *
     * @var array<string,array<int,array<string,int>>>
     */
    private const TABLES = [
        'catalog_product_super_link' => [
            ['product_id' => 10, 'parent_id' => 100],
            ['product_id' => 11, 'parent_id' => 100],
            ['product_id' => 11, 'parent_id' => 101],
        ],
        'catalog_product_link' => [
            ['product_id' => 200, 'linked_product_id' => 10, 'link_type_id' => 3],
            ['product_id' => 300, 'linked_product_id' => 10, 'link_type_id' => 1],
        ],
    ];

    /** @var array<int,array{table:string,where:array}> select object id => table and conditions */
    private array $selects = [];

    /** @var int */
    private int $queries = 0;

    /**
     * Connection that answers from TABLES; it understands "column IN (?)" and "column = ?" conditions.
     */
    private function provider(): ParentProvider
    {
        $connection = $this->createStub(AdapterInterface::class);
        $connection->method('select')->willReturnCallback(function (): Select {
            $select = $this->createStub(Select::class);
            $id = spl_object_id($select);
            $this->selects[$id] = ['table' => '', 'where' => []];
            $select->method('from')->willReturnCallback(function ($table) use ($select, $id): Select {
                $this->selects[$id]['table'] = $table;
                return $select;
            });
            $select->method('where')->willReturnCallback(
                function (string $condition, $value) use ($select, $id): Select {
                    $this->selects[$id]['where'][] = [$condition, $value];
                    return $select;
                }
            );
            return $select;
        });
        $connection->method('fetchAll')->willReturnCallback(function (Select $select): array {
            $this->queries++;
            $query = $this->selects[spl_object_id($select)];
            $rows = self::TABLES[$query['table']];
            foreach ($query['where'] as [$condition, $value]) {
                [$column] = explode(' ', $condition);
                $rows = array_filter(
                    $rows,
                    static fn(array $row): bool => in_array($row[$column], (array) $value, false)
                );
            }
            return array_values($rows);
        });
        $resource = $this->createStub(ResourceConnection::class);
        $resource->method('getConnection')->willReturn($connection);
        $resource->method('getTableName')->willReturnArgument(0);

        return new ParentProvider($resource);
    }

    public function testParentsOfEachChild(): void
    {
        self::assertSame(
            [10 => [100, 200], 11 => [100, 101]],
            $this->provider()->getParentsByChild([10, 11, 12, 10])
        );
    }

    public function testChildrenOfEachParent(): void
    {
        self::assertSame(
            [100 => [10, 11], 101 => [11], 200 => [10]],
            $this->provider()->getChildrenByParent([100, 101, 200, 300])
        );
    }

    public function testFewQueriesForManyProducts(): void
    {
        $parents = $this->provider()->getParentsByChild(range(1, 2001));

        self::assertSame([100, 200], $parents[10]);
        self::assertSame(6, $this->queries, 'three batches of ids, one query per table each');
    }

    public function testNoIdsNoQueries(): void
    {
        self::assertSame([], $this->provider()->getParentsByChild([]));
        self::assertSame(0, $this->queries);
    }
}
