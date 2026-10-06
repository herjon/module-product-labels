<?php
declare(strict_types=1);

namespace Majistar\ProductLabels\Test\Unit\Model\Index;

use Majistar\ProductLabels\Model\Index\CategoryExpander;
use Magento\Eav\Model\Config as EavConfig;
use Magento\Eav\Model\Entity\Attribute\AbstractAttribute;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\DB\Adapter\AdapterInterface;
use Magento\Framework\DB\Select;
use PHPUnit\Framework\TestCase;

class CategoryExpanderTest extends TestCase
{
    /** @var string[] patterns of the descendants query */
    private array $likes = [];

    /** @var int */
    private int $queries = 0;

    /**
     * Category 12 (path 1/2/11/12) is an anchor with children 14 and 15; 3 is not an anchor.
     *
     * @param string[] $anchorPaths paths returned by the anchor query
     */
    private function expander(array $anchorPaths = ['1/2/11/12']): CategoryExpander
    {
        $select = $this->createStub(Select::class);
        $select->method('from')->willReturnSelf();
        $select->method('join')->willReturnSelf();
        $select->method('where')->willReturnSelf();
        $select->method('orWhere')->willReturnCallback(function (string $condition, $value) use ($select): Select {
            $this->likes[] = $value;
            return $select;
        });
        $connection = $this->createStub(AdapterInterface::class);
        $connection->method('select')->willReturn($select);
        // First query: paths of the anchor categories; second: the categories below them.
        $connection->method('fetchCol')->willReturnCallback(function () use ($anchorPaths): array {
            return ++$this->queries === 1 ? $anchorPaths : ['14', '15'];
        });
        $resource = $this->createStub(ResourceConnection::class);
        $resource->method('getConnection')->willReturn($connection);
        $resource->method('getTableName')->willReturnArgument(0);
        $attribute = $this->createStub(AbstractAttribute::class);
        $attribute->method('getId')->willReturn(54);
        $eavConfig = $this->createStub(EavConfig::class);
        $eavConfig->method('getAttribute')->willReturn($attribute);

        return new CategoryExpander($resource, $eavConfig);
    }

    public function testAnchorCategoriesBringTheirDescendants(): void
    {
        self::assertSame([3, 12, 14, 15], $this->expander()->expand([12, 3, 12]));
        self::assertSame(['1/2/11/12/%'], $this->likes);
    }

    public function testOtherCategoriesStayAsTheyAre(): void
    {
        self::assertSame([3], $this->expander([])->expand([3]));
        self::assertSame(1, $this->queries);
    }

    public function testNoCategoriesNoQueries(): void
    {
        self::assertSame([], $this->expander()->expand([]));
        self::assertSame(0, $this->queries);
    }
}
