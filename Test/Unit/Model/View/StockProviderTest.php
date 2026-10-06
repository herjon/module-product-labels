<?php
declare(strict_types=1);

namespace Majistar\ProductLabels\Test\Unit\Model\View;

use Majistar\ProductLabels\Model\View\StockProvider;
use Magento\CatalogInventory\Api\StockConfigurationInterface;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\DB\Adapter\AdapterInterface;
use Magento\Framework\DB\Select;
use Magento\InventoryApi\Api\Data\StockInterface;
use Magento\InventoryIndexer\Model\StockIndexTableNameResolverInterface;
use Magento\InventorySalesApi\Model\StockByWebsiteIdResolverInterface;
use Magento\Store\Model\Store;
use Magento\Store\Model\StoreManagerInterface;
use PHPUnit\Framework\TestCase;

class StockProviderTest extends TestCase
{
    /**
     * @param array<int,array<string,string|null>> $rows rows of the stock index joined with the legacy stock item
     * @param bool $manageStock "Manage Stock" in the configuration
     */
    private function provider(array $rows, bool $manageStock = true): StockProvider
    {
        $store = $this->createStub(Store::class);
        $store->method('getWebsiteId')->willReturn(1);
        $storeManager = $this->createStub(StoreManagerInterface::class);
        $storeManager->method('getStore')->willReturn($store);
        $stock = $this->createStub(StockInterface::class);
        $stock->method('getStockId')->willReturn(1);
        $stockByWebsite = $this->createStub(StockByWebsiteIdResolverInterface::class);
        $stockByWebsite->method('execute')->willReturn($stock);
        $tableName = $this->createStub(StockIndexTableNameResolverInterface::class);
        $tableName->method('execute')->willReturn('inventory_stock_1');

        $select = $this->createStub(Select::class);
        $select->method('from')->willReturnSelf();
        $select->method('joinLeft')->willReturnSelf();
        $select->method('where')->willReturnSelf();
        $connection = $this->createStub(AdapterInterface::class);
        $connection->method('select')->willReturn($select);
        $connection->method('fetchAll')->willReturn($rows);
        $resource = $this->createStub(ResourceConnection::class);
        $resource->method('getConnection')->willReturn($connection);
        $resource->method('getTableName')->willReturnArgument(0);
        $configuration = $this->createStub(StockConfigurationInterface::class);
        $configuration->method('getManageStock')->willReturn($manageStock);

        return new StockProvider($storeManager, $stockByWebsite, $tableName, $resource, $configuration);
    }

    private function row(string $sku, ?string $manageStock, ?string $useConfig): array
    {
        return [
            'sku' => $sku,
            'quantity' => '3.0000',
            'is_salable' => '1',
            'manage_stock' => $manageStock,
            'use_config_manage_stock' => $useConfig,
        ];
    }

    public function testQuantityIsUnknownWhenTheProductDoesNotManageStock(): void
    {
        $stock = $this->provider([
            $this->row('BY-CONFIG', '0', '1'),
            $this->row('NOT-MANAGED', '0', '0'),
            $this->row('NO-STOCK-ITEM', null, null),
        ])->getStock(['BY-CONFIG', 'NOT-MANAGED', 'NO-STOCK-ITEM'], 1);

        self::assertSame(['qty' => 3.0, 'is_salable' => true], $stock['BY-CONFIG']);
        self::assertSame(['qty' => null, 'is_salable' => true], $stock['NOT-MANAGED']);
        self::assertSame(3.0, $stock['NO-STOCK-ITEM']['qty']);
    }

    public function testQuantityIsUnknownWhenStockIsNotManagedInTheConfiguration(): void
    {
        $stock = $this->provider([
            $this->row('BY-CONFIG', '1', '1'),
            $this->row('MANAGED', '1', '0'),
        ], false)->getStock(['BY-CONFIG', 'MANAGED'], 1);

        self::assertNull($stock['BY-CONFIG']['qty']);
        self::assertSame(3.0, $stock['MANAGED']['qty']);
    }
}
