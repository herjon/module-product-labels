<?php
declare(strict_types=1);

namespace Majistar\ProductLabels\Model;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;

/**
 * Settings in Stores > Configuration > Catalog > Product Labels.
 */
class Config
{
    public const XML_PATH_ENABLED = 'catalog/majistar_product_labels/enabled';
    public const XML_PATH_MAX_LABELS = 'catalog/majistar_product_labels/max_labels';
    public const XML_PATH_NEW_SOURCE = 'catalog/majistar_product_labels/new_source';
    public const XML_PATH_NEW_DAYS = 'catalog/majistar_product_labels/new_days';
    public const XML_PATH_OUT_OF_STOCK_ONLY = 'catalog/majistar_product_labels/out_of_stock_only';

    public const NEW_SOURCE_NEWS_DATES = 'news_dates';
    public const NEW_SOURCE_CREATED_DAYS = 'created_days';

    private const DEFAULT_MAX_LABELS = 2;
    private const DEFAULT_NEW_DAYS = 30;

    /**
     * @param ScopeConfigInterface $scopeConfig
     */
    public function __construct(
        private readonly ScopeConfigInterface $scopeConfig
    ) {
    }

    /**
     * Whether labels are shown at all.
     *
     * @param int|null $storeId
     * @return bool
     */
    public function isEnabled(?int $storeId = null): bool
    {
        return $this->scopeConfig->isSetFlag(self::XML_PATH_ENABLED, ScopeInterface::SCOPE_STORE, $storeId);
    }

    /**
     * Maximum number of labels shown on one product.
     *
     * @param int|null $storeId
     * @return int
     */
    public function getMaxLabels(?int $storeId = null): int
    {
        return $this->positiveInt(self::XML_PATH_MAX_LABELS, self::DEFAULT_MAX_LABELS, $storeId);
    }

    /**
     * What "new product" means: the core news_from/news_to dates or the days since creation.
     *
     * @param int|null $storeId
     * @return string one of the NEW_SOURCE_* constants
     */
    public function getNewSource(?int $storeId = null): string
    {
        $value = (string) $this->scopeConfig->getValue(
            self::XML_PATH_NEW_SOURCE,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
        return $value === self::NEW_SOURCE_CREATED_DAYS ? self::NEW_SOURCE_CREATED_DAYS : self::NEW_SOURCE_NEWS_DATES;
    }

    /**
     * Days a product stays "new" after its creation (used with NEW_SOURCE_CREATED_DAYS).
     *
     * @param int|null $storeId
     * @return int
     */
    public function getNewDays(?int $storeId = null): int
    {
        return $this->positiveInt(self::XML_PATH_NEW_DAYS, self::DEFAULT_NEW_DAYS, $storeId);
    }

    /**
     * Whether out-of-stock products show only the labels whose stock filter is "out of stock".
     *
     * @param int|null $storeId
     * @return bool
     */
    public function isOutOfStockOnly(?int $storeId = null): bool
    {
        return $this->scopeConfig->isSetFlag(
            self::XML_PATH_OUT_OF_STOCK_ONLY,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    /**
     * Reads an integer setting that must be at least 1.
     *
     * @param string $path
     * @param int $default
     * @param int|null $storeId
     * @return int
     */
    private function positiveInt(string $path, int $default, ?int $storeId): int
    {
        $value = $this->scopeConfig->getValue($path, ScopeInterface::SCOPE_STORE, $storeId);
        if (!is_numeric($value) || (int) $value < 1) {
            return $default;
        }
        return (int) $value;
    }
}
