<?php
declare(strict_types=1);

namespace Majistar\ProductLabels\Model\Index;

use Magento\Framework\App\ResourceConnection;
use Magento\GroupedProduct\Model\ResourceModel\Product\Link;

/**
 * Configurable and grouped parent/child links for "Use for Parent", read in batches from the link tables.
 */
class ParentProvider
{
    private const BATCH_SIZE = 1000;

    private const CONFIGURABLE_LINKS = 'catalog_product_super_link';

    private const PRODUCT_LINKS = 'catalog_product_link';

    /**
     * @param ResourceConnection $resourceConnection
     */
    public function __construct(
        private readonly ResourceConnection $resourceConnection
    ) {
    }

    /**
     * Parents of each child (children without parents are left out).
     *
     * @param int[] $childIds
     * @return array<int,int[]> child id => parent ids
     */
    public function getParentsByChild(array $childIds): array
    {
        return $this->merge(
            $this->fetchMap(self::CONFIGURABLE_LINKS, 'product_id', 'parent_id', $childIds),
            $this->fetchMap(self::PRODUCT_LINKS, 'linked_product_id', 'product_id', $childIds)
        );
    }

    /**
     * Children of each parent (parents without children are left out).
     *
     * @param int[] $parentIds
     * @return array<int,int[]> parent id => child ids
     */
    public function getChildrenByParent(array $parentIds): array
    {
        return $this->merge(
            $this->fetchMap(self::CONFIGURABLE_LINKS, 'parent_id', 'product_id', $parentIds),
            $this->fetchMap(self::PRODUCT_LINKS, 'product_id', 'linked_product_id', $parentIds)
        );
    }

    /**
     * Values of one column for the given values of another, from a link table (grouped links only).
     *
     * @param string $table
     * @param string $keyColumn
     * @param string $valueColumn
     * @param int[] $ids values of $keyColumn
     * @return array<int,int[]>
     */
    private function fetchMap(string $table, string $keyColumn, string $valueColumn, array $ids): array
    {
        $connection = $this->resourceConnection->getConnection();
        $map = [];
        foreach (array_chunk(array_values(array_unique(array_map('intval', $ids))), self::BATCH_SIZE) as $batch) {
            $select = $connection->select()
                ->from($this->resourceConnection->getTableName($table), [$keyColumn, $valueColumn])
                ->where($keyColumn . ' IN (?)', $batch);
            if ($table === self::PRODUCT_LINKS) {
                $select->where('link_type_id = ?', Link::LINK_TYPE_GROUPED);
            }
            foreach ($connection->fetchAll($select) as $row) {
                $map[(int) $row[$keyColumn]][] = (int) $row[$valueColumn];
            }
        }
        return $map;
    }

    /**
     * Union of two maps of lists, without duplicates, sorted.
     *
     * @param array<int,int[]> $first
     * @param array<int,int[]> $second
     * @return array<int,int[]>
     */
    private function merge(array $first, array $second): array
    {
        $result = $first;
        foreach ($second as $key => $values) {
            foreach ($values as $value) {
                $result[$key][] = $value;
            }
        }
        foreach ($result as $key => $values) {
            $values = array_values(array_unique($values));
            sort($values);
            $result[$key] = $values;
        }
        ksort($result);
        return $result;
    }
}
