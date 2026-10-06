<?php
declare(strict_types=1);

namespace Majistar\ProductLabels\Api\Data;

/**
 * @api
 */
interface LabelInterface
{
    public const LABEL_ID = 'label_id';
    public const NAME = 'name';
    public const IS_ACTIVE = 'is_active';
    public const PRIORITY = 'priority';
    public const STOP_PROCESSING = 'stop_processing';
    public const ACTIVE_FROM = 'active_from';
    public const ACTIVE_TO = 'active_to';
    public const SHOW_ON = 'show_on';
    public const STORE_IDS = 'store_ids';
    public const CUSTOMER_GROUP_IDS = 'customer_group_ids';
    public const IS_NEW = 'is_new';
    public const IS_ON_SALE = 'is_on_sale';
    public const MIN_DISCOUNT_PERCENT = 'min_discount_percent';
    public const STOCK_STATUS = 'stock_status';
    public const LOW_STOCK_QTY = 'low_stock_qty';
    public const CATEGORY_IDS = 'category_ids';
    public const PRICE_FROM = 'price_from';
    public const PRICE_TO = 'price_to';
    public const USE_FOR_PARENT = 'use_for_parent';
    public const CONDITIONS_SERIALIZED = 'conditions_serialized';
    public const PRODUCT_APPEARANCE = 'product_appearance';
    public const LISTING_APPEARANCE = 'listing_appearance';
    public const CART_APPEARANCE = 'cart_appearance';
    public const CREATED_AT = 'created_at';
    public const UPDATED_AT = 'updated_at';

    public const SHOW_ON_PRODUCT = 'product';
    public const SHOW_ON_LISTING = 'listing';
    public const SHOW_ON_CART = 'cart';
    public const SHOW_ON_MINICART = 'minicart';
    public const SHOW_ON_CHECKOUT = 'checkout';
    public const SHOW_ON_VALUES = [
        self::SHOW_ON_PRODUCT,
        self::SHOW_ON_LISTING,
        self::SHOW_ON_CART,
        self::SHOW_ON_MINICART,
        self::SHOW_ON_CHECKOUT,
    ];

    public const STOCK_ANY = 'any';
    public const STOCK_IN = 'in_stock';
    public const STOCK_OUT = 'out_of_stock';
    public const STOCK_LOW = 'low_stock';
    public const STOCK_STATUSES = [self::STOCK_ANY, self::STOCK_IN, self::STOCK_OUT, self::STOCK_LOW];

    /**
     * Get label id.
     *
     * @return int|null
     */
    public function getLabelId(): ?int;

    /**
     * Set label id.
     *
     * @param int|null $labelId
     * @return \Majistar\ProductLabels\Api\Data\LabelInterface
     */
    public function setLabelId(?int $labelId): LabelInterface;

    /**
     * Admin name, never shown to customers.
     *
     * @return string
     */
    public function getName(): string;

    /**
     * Set name.
     *
     * @param string $name
     * @return \Majistar\ProductLabels\Api\Data\LabelInterface
     */
    public function setName(string $name): LabelInterface;

    /**
     * Get is active.
     *
     * @return bool
     */
    public function getIsActive(): bool;

    /**
     * Set is active.
     *
     * @param bool $isActive
     * @return \Majistar\ProductLabels\Api\Data\LabelInterface
     */
    public function setIsActive(bool $isActive): LabelInterface;

    /**
     * Lower numbers are shown first.
     *
     * @return int
     */
    public function getPriority(): int;

    /**
     * Set priority.
     *
     * @param int $priority
     * @return \Majistar\ProductLabels\Api\Data\LabelInterface
     */
    public function setPriority(int $priority): LabelInterface;

    /**
     * When the label applies, labels with a higher priority number are not shown.
     *
     * @return bool
     */
    public function getStopProcessing(): bool;

    /**
     * Set stop processing.
     *
     * @param bool $stopProcessing
     * @return \Majistar\ProductLabels\Api\Data\LabelInterface
     */
    public function setStopProcessing(bool $stopProcessing): LabelInterface;

    /**
     * UTC date-time "Y-m-d H:i:s".
     *
     * @return string|null
     */
    public function getActiveFrom(): ?string;

    /**
     * Set active from.
     *
     * @param string|null $activeFrom UTC date-time "Y-m-d H:i:s"
     * @return \Majistar\ProductLabels\Api\Data\LabelInterface
     */
    public function setActiveFrom(?string $activeFrom): LabelInterface;

    /**
     * UTC date-time "Y-m-d H:i:s".
     *
     * @return string|null
     */
    public function getActiveTo(): ?string;

    /**
     * Set active to.
     *
     * @param string|null $activeTo UTC date-time "Y-m-d H:i:s"
     * @return \Majistar\ProductLabels\Api\Data\LabelInterface
     */
    public function setActiveTo(?string $activeTo): LabelInterface;

    /**
     * Where the label is shown: any of "product", "listing", "cart", "minicart", "checkout".
     *
     * @return string[]
     */
    public function getShowOn(): array;

    /**
     * Set show on.
     *
     * @param string[] $showOn
     * @return \Majistar\ProductLabels\Api\Data\LabelInterface
     */
    public function setShowOn(array $showOn): LabelInterface;

    /**
     * Store view ids; 0 means all store views.
     *
     * @return int[]
     */
    public function getStoreIds(): array;

    /**
     * Set store ids.
     *
     * @param int[] $storeIds
     * @return \Majistar\ProductLabels\Api\Data\LabelInterface
     */
    public function setStoreIds(array $storeIds): LabelInterface;

    /**
     * Get customer group ids.
     *
     * @return int[]
     */
    public function getCustomerGroupIds(): array;

    /**
     * Set customer group ids.
     *
     * @param int[] $customerGroupIds
     * @return \Majistar\ProductLabels\Api\Data\LabelInterface
     */
    public function setCustomerGroupIds(array $customerGroupIds): LabelInterface;

    /**
     * Quick filter: only new products.
     *
     * @return bool
     */
    public function getIsNew(): bool;

    /**
     * Set is new.
     *
     * @param bool $isNew
     * @return \Majistar\ProductLabels\Api\Data\LabelInterface
     */
    public function setIsNew(bool $isNew): LabelInterface;

    /**
     * Quick filter: only products on sale.
     *
     * @return bool
     */
    public function getIsOnSale(): bool;

    /**
     * Set is on sale.
     *
     * @param bool $isOnSale
     * @return \Majistar\ProductLabels\Api\Data\LabelInterface
     */
    public function setIsOnSale(bool $isOnSale): LabelInterface;

    /**
     * Quick filter: minimum discount percent (with "on sale").
     *
     * @return int|null
     */
    public function getMinDiscountPercent(): ?int;

    /**
     * Set min discount percent.
     *
     * @param int|null $minDiscountPercent
     * @return \Majistar\ProductLabels\Api\Data\LabelInterface
     */
    public function setMinDiscountPercent(?int $minDiscountPercent): LabelInterface;

    /**
     * Quick filter: any, in_stock, out_of_stock or low_stock.
     *
     * @return string
     */
    public function getStockStatus(): string;

    /**
     * Set stock status.
     *
     * @param string $stockStatus
     * @return \Majistar\ProductLabels\Api\Data\LabelInterface
     */
    public function setStockStatus(string $stockStatus): LabelInterface;

    /**
     * Quick filter: quantity below which stock is low (with low_stock).
     *
     * @return float|null
     */
    public function getLowStockQty(): ?float;

    /**
     * Set low stock qty.
     *
     * @param float|null $lowStockQty
     * @return \Majistar\ProductLabels\Api\Data\LabelInterface
     */
    public function setLowStockQty(?float $lowStockQty): LabelInterface;

    /**
     * Quick filter: the product is in at least one of these categories.
     *
     * @return int[]
     */
    public function getCategoryIds(): array;

    /**
     * Set category ids.
     *
     * @param int[] $categoryIds
     * @return \Majistar\ProductLabels\Api\Data\LabelInterface
     */
    public function setCategoryIds(array $categoryIds): LabelInterface;

    /**
     * Quick filter: minimum final price.
     *
     * @return float|null
     */
    public function getPriceFrom(): ?float;

    /**
     * Set price from.
     *
     * @param float|null $priceFrom
     * @return \Majistar\ProductLabels\Api\Data\LabelInterface
     */
    public function setPriceFrom(?float $priceFrom): LabelInterface;

    /**
     * Quick filter: maximum final price.
     *
     * @return float|null
     */
    public function getPriceTo(): ?float;

    /**
     * Set price to.
     *
     * @param float|null $priceTo
     * @return \Majistar\ProductLabels\Api\Data\LabelInterface
     */
    public function setPriceTo(?float $priceTo): LabelInterface;

    /**
     * Whether the label also goes on configurable and grouped parents of matching children.
     *
     * @return bool
     */
    public function getUseForParent(): bool;

    /**
     * Set use for parent.
     *
     * @param bool $useForParent
     * @return \Majistar\ProductLabels\Api\Data\LabelInterface
     */
    public function setUseForParent(bool $useForParent): LabelInterface;

    /**
     * Advanced conditions as JSON (Magento rule condition tree).
     *
     * @return string|null
     */
    public function getConditionsSerialized(): ?string;

    /**
     * Set conditions serialized.
     *
     * @param string|null $conditionsSerialized
     * @return \Majistar\ProductLabels\Api\Data\LabelInterface
     */
    public function setConditionsSerialized(?string $conditionsSerialized): LabelInterface;

    /**
     * Appearance on the product page; also used in listings when there is no listing appearance.
     *
     * @return \Majistar\ProductLabels\Api\Data\AppearanceInterface
     */
    public function getProductAppearance(): AppearanceInterface;

    /**
     * Set product appearance.
     *
     * @param \Majistar\ProductLabels\Api\Data\AppearanceInterface $appearance
     * @return \Majistar\ProductLabels\Api\Data\LabelInterface
     */
    public function setProductAppearance(AppearanceInterface $appearance): LabelInterface;

    /**
     * Own appearance in product listings; null means "use the product page appearance".
     *
     * @return \Majistar\ProductLabels\Api\Data\AppearanceInterface|null
     */
    public function getListingAppearance(): ?AppearanceInterface;

    /**
     * Set listing appearance.
     *
     * @param \Majistar\ProductLabels\Api\Data\AppearanceInterface|null $appearance
     * @return \Majistar\ProductLabels\Api\Data\LabelInterface
     */
    public function setListingAppearance(?AppearanceInterface $appearance): LabelInterface;

    /**
     * Own appearance in the shopping cart, mini-cart and checkout; null means "use the listing appearance".
     *
     * @return \Majistar\ProductLabels\Api\Data\AppearanceInterface|null
     */
    public function getCartAppearance(): ?AppearanceInterface;

    /**
     * Set cart appearance.
     *
     * @param \Majistar\ProductLabels\Api\Data\AppearanceInterface|null $appearance
     * @return \Majistar\ProductLabels\Api\Data\LabelInterface
     */
    public function setCartAppearance(?AppearanceInterface $appearance): LabelInterface;

    /**
     * Get created at.
     *
     * @return string|null
     */
    public function getCreatedAt(): ?string;

    /**
     * Set created at (lets a payload returned by GET be sent back unchanged).
     *
     * @param string|null $createdAt
     * @return \Majistar\ProductLabels\Api\Data\LabelInterface
     */
    public function setCreatedAt(?string $createdAt): LabelInterface;

    /**
     * Get updated at.
     *
     * @return string|null
     */
    public function getUpdatedAt(): ?string;

    /**
     * Set updated at (lets a payload returned by GET be sent back unchanged).
     *
     * @param string|null $updatedAt
     * @return \Majistar\ProductLabels\Api\Data\LabelInterface
     */
    public function setUpdatedAt(?string $updatedAt): LabelInterface;
}
