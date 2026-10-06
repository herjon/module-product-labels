<?php
declare(strict_types=1);

namespace Majistar\ProductLabels\Model\ResourceModel;

use Magento\Framework\App\ResourceConnection;

/**
 * Manual label assignments; no foreign key to products, as entity_id is not unique on Adobe Commerce.
 */
class LabelProduct
{
    public const TABLE = 'majistar_product_label_product';

    /**
     * @param ResourceConnection $resourceConnection
     */
    public function __construct(
        private readonly ResourceConnection $resourceConnection
    ) {
    }

    /**
     * Assigned label ids of several products, with one query.
     *
     * @param int[] $productIds
     * @return array<int, int[]> product id => label ids (products without labels are left out)
     */
    public function getLabelIdsByProducts(array $productIds): array
    {
        if (!$productIds) {
            return [];
        }
        $connection = $this->resourceConnection->getConnection();
        $select = $connection->select()
            ->from($this->resourceConnection->getTableName(self::TABLE), ['product_id', 'label_id'])
            ->where('product_id IN (?)', array_map('intval', $productIds))
            ->order(['product_id ASC', 'label_id ASC']);
        $result = [];
        foreach ($connection->fetchAll($select) as $row) {
            $result[(int) $row['product_id']][] = (int) $row['label_id'];
        }
        return $result;
    }

    /**
     * Labels assigned to a product.
     *
     * @param int $productId
     * @return int[]
     */
    public function getLabelIds(int $productId): array
    {
        $connection = $this->resourceConnection->getConnection();
        $select = $connection->select()
            ->from($this->resourceConnection->getTableName(self::TABLE), ['label_id'])
            ->where('product_id = ?', $productId)
            ->order('label_id ASC');
        return array_map('intval', $connection->fetchCol($select));
    }

    /**
     * Products a label is assigned to.
     *
     * @param int $labelId
     * @param int $limit 0 for all
     * @return int[]
     */
    public function getProductIds(int $labelId, int $limit = 0): array
    {
        $connection = $this->resourceConnection->getConnection();
        $select = $connection->select()
            ->from($this->resourceConnection->getTableName(self::TABLE), ['product_id'])
            ->where('label_id = ?', $labelId)
            ->order('product_id ASC');
        if ($limit > 0) {
            $select->limit($limit);
        }
        return array_map('intval', $connection->fetchCol($select));
    }

    /**
     * Number of products a label is assigned to.
     *
     * @param int $labelId
     * @return int
     */
    public function countProducts(int $labelId): int
    {
        $connection = $this->resourceConnection->getConnection();
        $select = $connection->select()
            ->from($this->resourceConnection->getTableName(self::TABLE), ['total' => 'COUNT(*)'])
            ->where('label_id = ?', $labelId);
        return (int) $connection->fetchOne($select);
    }

    /**
     * Replaces the labels of a product in one transaction.
     *
     * @param int $productId
     * @param int[] $labelIds
     * @return void
     * @throws \Throwable
     */
    public function replaceLabelIds(int $productId, array $labelIds): void
    {
        $connection = $this->resourceConnection->getConnection();
        $table = $this->resourceConnection->getTableName(self::TABLE);
        $connection->beginTransaction();
        try {
            $connection->delete($table, ['product_id = ?' => $productId]);
            if ($labelIds) {
                $connection->insertMultiple($table, array_map(
                    static fn(int $labelId): array => ['label_id' => $labelId, 'product_id' => $productId],
                    $labelIds
                ));
            }
            $connection->commit();
        } catch (\Throwable $e) {
            $connection->rollBack();
            throw $e;
        }
    }

    /**
     * Removes every assignment of a product.
     *
     * @param int $productId
     * @return void
     */
    public function deleteByProductId(int $productId): void
    {
        $this->resourceConnection->getConnection()->delete(
            $this->resourceConnection->getTableName(self::TABLE),
            ['product_id = ?' => $productId]
        );
    }

    /**
     * The ids among $labelIds that exist.
     *
     * @param int[] $labelIds
     * @return int[]
     */
    public function filterExistingLabelIds(array $labelIds): array
    {
        if (!$labelIds) {
            return [];
        }
        $connection = $this->resourceConnection->getConnection();
        $select = $connection->select()
            ->from($this->resourceConnection->getTableName(Label::MAIN_TABLE), ['label_id'])
            ->where('label_id IN (?)', $labelIds);
        return array_map('intval', $connection->fetchCol($select));
    }

    /**
     * Whether a product with this entity id exists.
     *
     * @param int $productId
     * @return bool
     */
    public function productExists(int $productId): bool
    {
        $connection = $this->resourceConnection->getConnection();
        $select = $connection->select()
            ->from($this->resourceConnection->getTableName('catalog_product_entity'), ['entity_id'])
            ->where('entity_id = ?', $productId)
            ->limit(1);
        return (bool) $connection->fetchOne($select);
    }
}
