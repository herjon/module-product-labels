<?php
declare(strict_types=1);

namespace Majistar\ProductLabels\Model\ResourceModel;

use Magento\Framework\App\ResourceConnection;
use Magento\Framework\DB\Adapter\AdapterInterface;

/**
 * Reads and writes majistar_product_label_index.
 */
class Index
{
    public const TABLE = 'majistar_product_label_index';

    private const BATCH_SIZE = 1000;

    /**
     * @param ResourceConnection $resourceConnection
     */
    public function __construct(
        private readonly ResourceConnection $resourceConnection
    ) {
    }

    /**
     * Writes rows (in batches).
     *
     * @param array<int,array{label_id:int,product_id:int,store_id:int,manual:int}> $rows
     * @return void
     */
    public function insertRows(array $rows): void
    {
        foreach (array_chunk($rows, self::BATCH_SIZE) as $batch) {
            $this->connection()->insertOnDuplicate($this->table(), $batch, ['manual']);
        }
    }

    /**
     * Replaces the rows of one label in one store view, in one transaction so readers never see none.
     *
     * @param int $labelId
     * @param int $storeId
     * @param array<int,array{label_id:int,product_id:int,store_id:int,manual:int}> $rows
     * @return void
     */
    public function replaceRows(int $labelId, int $storeId, array $rows): void
    {
        $connection = $this->connection();
        $connection->beginTransaction();
        try {
            $connection->delete($this->table(), ['label_id = ?' => $labelId, 'store_id = ?' => $storeId]);
            $this->insertRows($rows);
            $connection->commit();
        } catch (\Throwable $e) {
            $connection->rollBack();
            throw $e;
        }
    }

    /**
     * Removes the rows of one label, except those of some store views.
     *
     * @param int $labelId
     * @param int[] $keepStoreIds
     * @return void
     */
    public function deleteByLabel(int $labelId, array $keepStoreIds = []): void
    {
        $where = ['label_id = ?' => $labelId];
        if ($keepStoreIds) {
            $where['store_id NOT IN (?)'] = $keepStoreIds;
        }
        $this->connection()->delete($this->table(), $where);
    }

    /**
     * Removes the rows of every label except some (all rows when none is given).
     *
     * @param int[] $keepLabelIds
     * @return void
     */
    public function deleteOtherLabels(array $keepLabelIds): void
    {
        $this->connection()->delete(
            $this->table(),
            $keepLabelIds ? ['label_id NOT IN (?)' => $keepLabelIds] : ''
        );
    }

    /**
     * Removes the rows of some products.
     *
     * @param int[] $productIds
     * @return void
     */
    public function deleteByProducts(array $productIds): void
    {
        if ($productIds) {
            $this->connection()->delete($this->table(), ['product_id IN (?)' => $productIds]);
        }
    }

    /**
     * Labels of some products in one store view.
     *
     * @param int[] $productIds
     * @param int $storeId
     * @return array<int,array<int,int>> product id => [label id => manual]
     */
    public function getRows(array $productIds, int $storeId): array
    {
        if (!$productIds) {
            return [];
        }
        $select = $this->connection()->select()
            ->from($this->table(), ['product_id', 'label_id', 'manual'])
            ->where('product_id IN (?)', $productIds)
            ->where('store_id = ?', $storeId);
        $rows = [];
        foreach ($this->connection()->fetchAll($select) as $row) {
            $rows[(int) $row['product_id']][(int) $row['label_id']] = (int) $row['manual'];
        }
        return $rows;
    }

    /**
     * Database connection.
     *
     * @return AdapterInterface
     */
    private function connection(): AdapterInterface
    {
        return $this->resourceConnection->getConnection();
    }

    /**
     * Index table name.
     *
     * @return string
     */
    private function table(): string
    {
        return $this->resourceConnection->getTableName(self::TABLE);
    }
}
