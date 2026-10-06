<?php
declare(strict_types=1);

namespace Majistar\ProductLabels\Model\View;

use Magento\CatalogInventory\Api\StockConfigurationInterface;
use Magento\Framework\App\ResourceConnection;
use Magento\InventoryIndexer\Model\StockIndexTableNameResolverInterface;
use Magento\InventorySalesApi\Model\StockByWebsiteIdResolverInterface;
use Magento\Store\Model\StoreManagerInterface;

/**
 * Salable quantity (null without "Manage Stock") and status from the MSI stock index of the website.
 */
class StockProvider
{
    /**
     * @param StoreManagerInterface $storeManager
     * @param StockByWebsiteIdResolverInterface $stockByWebsiteId
     * @param StockIndexTableNameResolverInterface $stockIndexTableName
     * @param ResourceConnection $resourceConnection
     * @param StockConfigurationInterface $stockConfiguration
     */
    public function __construct(
        private readonly StoreManagerInterface $storeManager,
        private readonly StockByWebsiteIdResolverInterface $stockByWebsiteId,
        private readonly StockIndexTableNameResolverInterface $stockIndexTableName,
        private readonly ResourceConnection $resourceConnection,
        private readonly StockConfigurationInterface $stockConfiguration
    ) {
    }

    /**
     * Stock of some SKUs (SKUs missing from the index are left out).
     *
     * @param string[] $skus
     * @param int $storeId
     * @return array<string,array{qty:float|null,is_salable:bool}>
     */
    public function getStock(array $skus, int $storeId): array
    {
        if (!$skus) {
            return [];
        }
        $websiteId = (int) $this->storeManager->getStore($storeId)->getWebsiteId();
        $stockId = (int) $this->stockByWebsiteId->execute($websiteId)->getStockId();
        $connection = $this->resourceConnection->getConnection();
        // Same join as the MSI indexer: the legacy stock item holds "Manage Stock".
        $select = $connection->select()
            ->from(['stock' => $this->stockIndexTableName->execute($stockId)], ['sku', 'quantity', 'is_salable'])
            ->joinLeft(
                ['product' => $this->resourceConnection->getTableName('catalog_product_entity')],
                'product.sku = stock.sku',
                []
            )
            ->joinLeft(
                ['stock_item' => $this->resourceConnection->getTableName('cataloginventory_stock_item')],
                'stock_item.product_id = product.entity_id',
                ['manage_stock', 'use_config_manage_stock']
            )
            ->where('stock.sku IN (?)', $skus);
        $manageStockByDefault = (bool) $this->stockConfiguration->getManageStock($storeId);
        $stock = [];
        foreach ($connection->fetchAll($select) as $row) {
            $managesStock = $row['use_config_manage_stock'] === null || (int) $row['use_config_manage_stock']
                ? $manageStockByDefault
                : (bool) $row['manage_stock'];
            $stock[(string) $row['sku']] = [
                'qty' => $managesStock ? (float) $row['quantity'] : null,
                'is_salable' => (bool) $row['is_salable'],
            ];
        }
        return $stock;
    }
}
