<?php
declare(strict_types=1);

namespace Majistar\ProductLabels\Model;

use Majistar\ProductLabels\Api\Data\AppearanceInterface;
use Majistar\ProductLabels\Api\Data\AppearanceInterfaceFactory;
use Majistar\ProductLabels\Api\Data\LabelInterface;
use Majistar\ProductLabels\Model\ResourceModel\Label as LabelResource;
use Magento\CatalogRule\Model\Rule\Action\CollectionFactory as ActionCollectionFactory;
use Magento\CatalogRule\Model\Rule\Condition\CombineFactory;
use Magento\Framework\Api\AttributeValueFactory;
use Magento\Framework\Api\ExtensionAttributesFactory;
use Magento\Framework\Data\Collection\AbstractDb;
use Magento\Framework\Data\FormFactory;
use Magento\Framework\DataObject\IdentityInterface;
use Magento\Framework\Model\Context;
use Magento\Framework\Model\ResourceModel\AbstractResource;
use Magento\Framework\Registry;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Magento\Rule\Model\AbstractModel;

/**
 * Label on the rule model, so "Advanced conditions" use the Catalog Price Rules condition builder.
 *
 * @SuppressWarnings(PHPMD.ExcessivePublicCount)
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class Label extends AbstractModel implements LabelInterface, IdentityInterface
{
    public const CACHE_TAG = 'majistar_product_label';

    /**
     * @var string
     */
    protected $_eventPrefix = 'majistar_product_label';

    /**
     * @var string
     */
    protected $_eventObject = 'label';

    /**
     * @var string
     */
    protected $_cacheTag = self::CACHE_TAG;

    /**
     * @param Context $context
     * @param Registry $registry
     * @param FormFactory $formFactory
     * @param TimezoneInterface $localeDate
     * @param CombineFactory $combineFactory
     * @param ActionCollectionFactory $actionCollectionFactory
     * @param AppearanceInterfaceFactory $appearanceFactory
     * @param Json $json
     * @param AbstractResource|null $resource
     * @param AbstractDb|null $resourceCollection
     * @param array $data
     * @param ExtensionAttributesFactory|null $extensionFactory
     * @param AttributeValueFactory|null $customAttributeFactory
     * @SuppressWarnings(PHPMD.ExcessiveParameterList)
     */
    public function __construct(
        Context $context,
        Registry $registry,
        FormFactory $formFactory,
        TimezoneInterface $localeDate,
        private readonly CombineFactory $combineFactory,
        private readonly ActionCollectionFactory $actionCollectionFactory,
        private readonly AppearanceInterfaceFactory $appearanceFactory,
        private readonly Json $json,
        ?AbstractResource $resource = null,
        ?AbstractDb $resourceCollection = null,
        array $data = [],
        ?ExtensionAttributesFactory $extensionFactory = null,
        ?AttributeValueFactory $customAttributeFactory = null
    ) {
        parent::__construct(
            $context,
            $registry,
            $formFactory,
            $localeDate,
            $resource,
            $resourceCollection,
            $data,
            $extensionFactory,
            $customAttributeFactory,
            $json
        );
    }

    /**
     * @inheritdoc
     */
    protected function _construct(): void
    {
        $this->_init(LabelResource::class);
    }

    /**
     * Root of the "Advanced conditions": the product condition combine of Catalog Price Rules.
     *
     * @return \Magento\CatalogRule\Model\Rule\Condition\Combine
     */
    public function getConditionsInstance()
    {
        return $this->combineFactory->create();
    }

    /**
     * Labels have no actions; the rule model requires an instance anyway.
     *
     * @return \Magento\CatalogRule\Model\Rule\Action\Collection
     */
    public function getActionsInstance()
    {
        return $this->actionCollectionFactory->create();
    }

    /**
     * Id of the HTML fieldset of the condition builder (same convention as Catalog Price Rules).
     *
     * @param string $formName
     * @return string
     */
    public function getConditionsFieldSetId(string $formName = ''): string
    {
        return $formName . 'rule_conditions_fieldset_' . $this->getId();
    }

    /**
     * @inheritdoc
     */
    public function getIdentities(): array
    {
        return [self::CACHE_TAG, self::CACHE_TAG . '_' . $this->getId()];
    }

    /**
     * @inheritdoc
     */
    public function getLabelId(): ?int
    {
        return $this->nullableInt(self::LABEL_ID);
    }

    /**
     * @inheritdoc
     */
    public function setLabelId(?int $labelId): LabelInterface
    {
        return $this->setData(self::LABEL_ID, $labelId);
    }

    /**
     * @inheritdoc
     */
    public function getName(): string
    {
        return (string) $this->getData(self::NAME);
    }

    /**
     * @inheritdoc
     */
    public function setName(string $name): LabelInterface
    {
        return $this->setData(self::NAME, $name);
    }

    /**
     * @inheritdoc
     */
    public function getIsActive(): bool
    {
        $value = $this->getData(self::IS_ACTIVE);
        return $value === null || (bool) $value;
    }

    /**
     * @inheritdoc
     */
    public function setIsActive(bool $isActive): LabelInterface
    {
        return $this->setData(self::IS_ACTIVE, $isActive ? 1 : 0);
    }

    /**
     * @inheritdoc
     */
    public function getPriority(): int
    {
        return (int) $this->getData(self::PRIORITY);
    }

    /**
     * @inheritdoc
     */
    public function setPriority(int $priority): LabelInterface
    {
        return $this->setData(self::PRIORITY, $priority);
    }

    /**
     * @inheritdoc
     */
    public function getStopProcessing(): bool
    {
        return (bool) $this->getData(self::STOP_PROCESSING);
    }

    /**
     * @inheritdoc
     */
    public function setStopProcessing(bool $stopProcessing): LabelInterface
    {
        return $this->setData(self::STOP_PROCESSING, $stopProcessing ? 1 : 0);
    }

    /**
     * @inheritdoc
     */
    public function getActiveFrom(): ?string
    {
        return $this->nullableString(self::ACTIVE_FROM);
    }

    /**
     * @inheritdoc
     */
    public function setActiveFrom(?string $activeFrom): LabelInterface
    {
        return $this->setData(self::ACTIVE_FROM, $activeFrom);
    }

    /**
     * @inheritdoc
     */
    public function getActiveTo(): ?string
    {
        return $this->nullableString(self::ACTIVE_TO);
    }

    /**
     * @inheritdoc
     */
    public function setActiveTo(?string $activeTo): LabelInterface
    {
        return $this->setData(self::ACTIVE_TO, $activeTo);
    }

    /**
     * @inheritdoc
     */
    public function getShowOn(): array
    {
        $value = $this->getData(self::SHOW_ON);
        if ($value === null) {
            return [self::SHOW_ON_PRODUCT, self::SHOW_ON_LISTING];
        }
        return $this->stringList($value);
    }

    /**
     * @inheritdoc
     */
    public function setShowOn(array $showOn): LabelInterface
    {
        return $this->setData(self::SHOW_ON, $this->stringList($showOn));
    }

    /**
     * @inheritdoc
     */
    public function getStoreIds(): array
    {
        return $this->intList($this->getData(self::STORE_IDS));
    }

    /**
     * @inheritdoc
     */
    public function setStoreIds(array $storeIds): LabelInterface
    {
        return $this->setData(self::STORE_IDS, $this->intList($storeIds));
    }

    /**
     * @inheritdoc
     */
    public function getCustomerGroupIds(): array
    {
        return $this->intList($this->getData(self::CUSTOMER_GROUP_IDS));
    }

    /**
     * @inheritdoc
     */
    public function setCustomerGroupIds(array $customerGroupIds): LabelInterface
    {
        return $this->setData(self::CUSTOMER_GROUP_IDS, $this->intList($customerGroupIds));
    }

    /**
     * @inheritdoc
     */
    public function getIsNew(): bool
    {
        return (bool) $this->getData(self::IS_NEW);
    }

    /**
     * @inheritdoc
     */
    public function setIsNew(bool $isNew): LabelInterface
    {
        return $this->setData(self::IS_NEW, $isNew ? 1 : 0);
    }

    /**
     * @inheritdoc
     */
    public function getIsOnSale(): bool
    {
        return (bool) $this->getData(self::IS_ON_SALE);
    }

    /**
     * @inheritdoc
     */
    public function setIsOnSale(bool $isOnSale): LabelInterface
    {
        return $this->setData(self::IS_ON_SALE, $isOnSale ? 1 : 0);
    }

    /**
     * @inheritdoc
     */
    public function getMinDiscountPercent(): ?int
    {
        return $this->nullableInt(self::MIN_DISCOUNT_PERCENT);
    }

    /**
     * @inheritdoc
     */
    public function setMinDiscountPercent(?int $minDiscountPercent): LabelInterface
    {
        return $this->setData(self::MIN_DISCOUNT_PERCENT, $minDiscountPercent);
    }

    /**
     * @inheritdoc
     */
    public function getStockStatus(): string
    {
        return $this->nullableString(self::STOCK_STATUS) ?? self::STOCK_ANY;
    }

    /**
     * @inheritdoc
     */
    public function setStockStatus(string $stockStatus): LabelInterface
    {
        return $this->setData(self::STOCK_STATUS, $stockStatus);
    }

    /**
     * @inheritdoc
     */
    public function getLowStockQty(): ?float
    {
        return $this->nullableFloat(self::LOW_STOCK_QTY);
    }

    /**
     * @inheritdoc
     */
    public function setLowStockQty(?float $lowStockQty): LabelInterface
    {
        return $this->setData(self::LOW_STOCK_QTY, $lowStockQty);
    }

    /**
     * @inheritdoc
     */
    public function getCategoryIds(): array
    {
        return $this->intList($this->getData(self::CATEGORY_IDS));
    }

    /**
     * @inheritdoc
     */
    public function setCategoryIds(array $categoryIds): LabelInterface
    {
        return $this->setData(self::CATEGORY_IDS, $this->intList($categoryIds));
    }

    /**
     * @inheritdoc
     */
    public function getPriceFrom(): ?float
    {
        return $this->nullableFloat(self::PRICE_FROM);
    }

    /**
     * @inheritdoc
     */
    public function setPriceFrom(?float $priceFrom): LabelInterface
    {
        return $this->setData(self::PRICE_FROM, $priceFrom);
    }

    /**
     * @inheritdoc
     */
    public function getPriceTo(): ?float
    {
        return $this->nullableFloat(self::PRICE_TO);
    }

    /**
     * @inheritdoc
     */
    public function setPriceTo(?float $priceTo): LabelInterface
    {
        return $this->setData(self::PRICE_TO, $priceTo);
    }

    /**
     * @inheritdoc
     */
    public function getUseForParent(): bool
    {
        return (bool) $this->getData(self::USE_FOR_PARENT);
    }

    /**
     * @inheritdoc
     */
    public function setUseForParent(bool $useForParent): LabelInterface
    {
        return $this->setData(self::USE_FOR_PARENT, $useForParent ? 1 : 0);
    }

    /**
     * Advanced conditions as JSON.
     *
     * @return string|null
     */
    public function getConditionsSerialized(): ?string
    {
        $value = $this->nullableString(self::CONDITIONS_SERIALIZED);
        // The rule model unsets conditions_serialized once the conditions are loaded as objects.
        if ($value === null && $this->_conditions !== null) {
            return $this->json->serialize($this->_conditions->asArray());
        }
        return $value;
    }

    /**
     * @inheritdoc
     */
    public function setConditionsSerialized(?string $conditionsSerialized): LabelInterface
    {
        $this->_conditions = null;
        return $this->setData(self::CONDITIONS_SERIALIZED, $conditionsSerialized);
    }

    /**
     * @inheritdoc
     */
    public function getProductAppearance(): AppearanceInterface
    {
        $appearance = $this->getData(self::PRODUCT_APPEARANCE);
        if (!$appearance instanceof AppearanceInterface) {
            $appearance = $this->appearanceFactory->create();
            $this->setData(self::PRODUCT_APPEARANCE, $appearance);
        }
        return $appearance;
    }

    /**
     * @inheritdoc
     */
    public function setProductAppearance(AppearanceInterface $appearance): LabelInterface
    {
        return $this->setData(self::PRODUCT_APPEARANCE, $appearance);
    }

    /**
     * @inheritdoc
     */
    public function getListingAppearance(): ?AppearanceInterface
    {
        $appearance = $this->getData(self::LISTING_APPEARANCE);
        return $appearance instanceof AppearanceInterface ? $appearance : null;
    }

    /**
     * @inheritdoc
     */
    public function setListingAppearance(?AppearanceInterface $appearance): LabelInterface
    {
        return $this->setData(self::LISTING_APPEARANCE, $appearance);
    }

    /**
     * @inheritdoc
     */
    public function getCartAppearance(): ?AppearanceInterface
    {
        $appearance = $this->getData(self::CART_APPEARANCE);
        return $appearance instanceof AppearanceInterface ? $appearance : null;
    }

    /**
     * @inheritdoc
     */
    public function setCartAppearance(?AppearanceInterface $appearance): LabelInterface
    {
        return $this->setData(self::CART_APPEARANCE, $appearance);
    }

    /**
     * @inheritdoc
     */
    public function getCreatedAt(): ?string
    {
        return $this->nullableString(self::CREATED_AT);
    }

    /**
     * @inheritdoc
     */
    public function getUpdatedAt(): ?string
    {
        return $this->nullableString(self::UPDATED_AT);
    }

    /**
     * @inheritdoc
     */
    public function setCreatedAt(?string $createdAt): LabelInterface
    {
        return $this->setData(self::CREATED_AT, $createdAt);
    }

    /**
     * @inheritdoc
     */
    public function setUpdatedAt(?string $updatedAt): LabelInterface
    {
        return $this->setData(self::UPDATED_AT, $updatedAt);
    }

    /**
     * Integer value, or null when missing or empty.
     *
     * @param string $key
     * @return int|null
     */
    private function nullableInt(string $key): ?int
    {
        $value = $this->getData($key);
        return is_numeric($value) ? (int) $value : null;
    }

    /**
     * Float value, or null when missing or empty.
     *
     * @param string $key
     * @return float|null
     */
    private function nullableFloat(string $key): ?float
    {
        $value = $this->getData($key);
        return is_numeric($value) ? (float) $value : null;
    }

    /**
     * String value, or null when missing or empty.
     *
     * @param string $key
     * @return string|null
     */
    private function nullableString(string $key): ?string
    {
        $value = $this->getData($key);
        return $value === null || $value === '' ? null : (string) $value;
    }

    /**
     * Unique integers from an array or a comma-separated string; non-numbers are dropped.
     *
     * @param mixed $value
     * @return int[]
     */
    private function intList(mixed $value): array
    {
        $items = is_array($value) ? $value : explode(',', (string) $value);
        $result = [];
        foreach ($items as $item) {
            if (is_numeric($item)) {
                $result[] = (int) $item;
            }
        }
        return array_values(array_unique($result));
    }

    /**
     * Unique non-empty strings from an array or a comma-separated string.
     *
     * @param mixed $value
     * @return string[]
     */
    private function stringList(mixed $value): array
    {
        $items = is_array($value) ? $value : explode(',', (string) $value);
        $result = [];
        foreach ($items as $item) {
            $item = trim((string) $item);
            if ($item !== '') {
                $result[] = $item;
            }
        }
        return array_values(array_unique($result));
    }
}
