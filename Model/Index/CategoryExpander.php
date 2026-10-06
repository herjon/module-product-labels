<?php
declare(strict_types=1);

namespace Majistar\ProductLabels\Model\Index;

use Magento\Catalog\Model\Category;
use Magento\Eav\Model\Config as EavConfig;
use Magento\Framework\App\ResourceConnection;
use Magento\Store\Model\Store;

/**
 * Expands anchor categories of the "Categories" filter to their descendants, as the storefront does.
 */
class CategoryExpander
{
    private const CATEGORY_TABLE = 'catalog_category_entity';

    /**
     * @param ResourceConnection $resourceConnection
     * @param EavConfig $eavConfig
     */
    public function __construct(
        private readonly ResourceConnection $resourceConnection,
        private readonly EavConfig $eavConfig
    ) {
    }

    /**
     * The categories plus all the descendants of the anchor ones ("Is Anchor" is a global attribute).
     *
     * @param int[] $categoryIds
     * @return int[] sorted, without duplicates
     */
    public function expand(array $categoryIds): array
    {
        $categoryIds = array_values(array_unique(array_map('intval', $categoryIds)));
        if (!$categoryIds) {
            return [];
        }
        $connection = $this->resourceConnection->getConnection();
        $categoryTable = $this->resourceConnection->getTableName(self::CATEGORY_TABLE);
        $isAnchorId = (int) $this->eavConfig->getAttribute(Category::ENTITY, 'is_anchor')->getId();
        $anchorPaths = $connection->fetchCol(
            $connection->select()
                ->from(['category' => $categoryTable], ['path'])
                ->join(
                    ['anchor' => $this->resourceConnection->getTableName(self::CATEGORY_TABLE . '_int')],
                    'anchor.entity_id = category.entity_id',
                    []
                )
                ->where('category.entity_id IN (?)', $categoryIds)
                ->where('anchor.attribute_id = ?', $isAnchorId)
                ->where('anchor.store_id = ?', Store::DEFAULT_STORE_ID)
                ->where('anchor.value = ?', 1)
        );
        $ids = $categoryIds;
        if ($anchorPaths) {
            $select = $connection->select()->from($categoryTable, ['entity_id']);
            foreach ($anchorPaths as $path) {
                $select->orWhere('path LIKE ?', $path . '/%');
            }
            foreach ($connection->fetchCol($select) as $id) {
                $ids[] = (int) $id;
            }
        }
        $ids = array_values(array_unique($ids));
        sort($ids);
        return $ids;
    }
}
