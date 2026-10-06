<?php
declare(strict_types=1);

namespace Majistar\ProductLabels\Model\ResourceModel;

use Majistar\ProductLabels\Api\Data\AppearanceInterface;
use Majistar\ProductLabels\Api\Data\AppearanceInterfaceFactory;
use Majistar\ProductLabels\Api\Data\LabelInterface;
use Majistar\ProductLabels\Model\Label as LabelModel;
use Magento\Framework\Model\AbstractModel;
use Magento\Framework\Model\ResourceModel\Db\AbstractDb;
use Magento\Framework\Model\ResourceModel\Db\Context;

/**
 * Label table plus its store views, customer groups and appearances.
 */
class Label extends AbstractDb
{
    public const MAIN_TABLE = 'majistar_product_label';
    public const APPEARANCE_TABLE = 'majistar_product_label_appearance';
    public const STORE_TABLE = 'majistar_product_label_store';
    public const CUSTOMER_GROUP_TABLE = 'majistar_product_label_customer_group';

    public const CONTEXT_PRODUCT = 'product';
    public const CONTEXT_LISTING = 'listing';
    public const CONTEXT_CART = 'cart';

    private const APPEARANCE_FIELDS = [
        AppearanceInterface::TYPE,
        AppearanceInterface::TEXT,
        AppearanceInterface::BACKGROUND_COLOR,
        AppearanceInterface::TEXT_COLOR,
        AppearanceInterface::SHAPE,
        AppearanceInterface::TEXT_SIZE,
        AppearanceInterface::IMAGE,
        AppearanceInterface::IMAGE_ALT,
        AppearanceInterface::WIDTH_PERCENT,
        AppearanceInterface::CORNER_RADIUS,
        AppearanceInterface::POSITION,
    ];

    /**
     * @param Context $context
     * @param AppearanceInterfaceFactory $appearanceFactory
     * @param string|null $connectionName
     */
    public function __construct(
        Context $context,
        private readonly AppearanceInterfaceFactory $appearanceFactory,
        ?string $connectionName = null
    ) {
        parent::__construct($context, $connectionName);
    }

    /**
     * @inheritdoc
     */
    protected function _construct(): void
    {
        $this->_init(self::MAIN_TABLE, LabelInterface::LABEL_ID);
    }

    /**
     * Always save: a change made only to an appearance (own table) must still reach _afterSave.
     *
     * @param AbstractModel $object
     * @return bool
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    protected function isModified(AbstractModel $object): bool
    {
        return true;
    }

    /**
     * Lists are stored as comma-separated strings.
     *
     * @param AbstractModel $object
     * @return $this
     */
    protected function _beforeSave(AbstractModel $object)
    {
        /** @var LabelModel $object */
        $object->setData(LabelInterface::SHOW_ON, implode(',', $object->getShowOn()));
        $categoryIds = $object->getCategoryIds();
        $object->setData(LabelInterface::CATEGORY_IDS, $categoryIds ? implode(',', $categoryIds) : null);
        return parent::_beforeSave($object);
    }

    /**
     * Saves store views, customer groups and appearances (inside the save transaction).
     *
     * @param AbstractModel $object
     * @return $this
     */
    protected function _afterSave(AbstractModel $object)
    {
        /** @var LabelModel $object */
        $labelId = (int) $object->getId();
        $this->replaceLinks(self::STORE_TABLE, 'store_id', $labelId, $object->getStoreIds());
        $this->replaceLinks(
            self::CUSTOMER_GROUP_TABLE,
            'customer_group_id',
            $labelId,
            $object->getCustomerGroupIds()
        );
        $this->saveAppearance($labelId, self::CONTEXT_PRODUCT, $object->getProductAppearance());
        $this->saveAppearance($labelId, self::CONTEXT_LISTING, $object->getListingAppearance());
        $this->saveAppearance($labelId, self::CONTEXT_CART, $object->getCartAppearance());
        return parent::_afterSave($object);
    }

    /**
     * @inheritdoc
     */
    protected function _afterLoad(AbstractModel $object)
    {
        if ($object->getId()) {
            $this->attachRelations([(int) $object->getId() => $object]);
        }
        return parent::_afterLoad($object);
    }

    /**
     * Loads store views, customer groups and appearances of many labels with three queries.
     *
     * @param LabelModel[] $labels keyed by label id
     * @return void
     */
    public function attachRelations(array $labels): void
    {
        if (!$labels) {
            return;
        }
        $ids = array_map('intval', array_keys($labels));
        $stores = $this->fetchLinks(self::STORE_TABLE, 'store_id', $ids);
        $groups = $this->fetchLinks(self::CUSTOMER_GROUP_TABLE, 'customer_group_id', $ids);

        $connection = $this->getConnection();
        $select = $connection->select()
            ->from($this->getTable(self::APPEARANCE_TABLE))
            ->where('label_id IN (?)', $ids);
        $appearances = [];
        foreach ($connection->fetchAll($select) as $row) {
            $appearances[(int) $row['label_id']][$row['context']] = $this->appearanceFactory->create([
                'data' => array_intersect_key($row, array_flip(self::APPEARANCE_FIELDS)),
            ]);
        }

        foreach ($labels as $id => $label) {
            $label->setStoreIds($stores[$id] ?? []);
            $label->setCustomerGroupIds($groups[$id] ?? []);
            $label->setProductAppearance(
                $appearances[$id][self::CONTEXT_PRODUCT] ?? $this->appearanceFactory->create()
            );
            $label->setListingAppearance($appearances[$id][self::CONTEXT_LISTING] ?? null);
            $label->setCartAppearance($appearances[$id][self::CONTEXT_CART] ?? null);
        }
    }

    /**
     * Rows for option lists (id, name, status) by priority and name, without loading relations.
     *
     * @return array<int, array{label_id: string, name: string, is_active: string}>
     */
    public function getOptionRows(): array
    {
        $connection = $this->getConnection();
        return $connection->fetchAll(
            $connection->select()
                ->from($this->getMainTable(), ['label_id', 'name', 'is_active'])
                ->order(['priority ASC', 'name ASC'])
        );
    }

    /**
     * Link rows grouped by label id.
     *
     * @param string $table
     * @param string $column
     * @param int[] $labelIds
     * @return array<int, int[]>
     */
    private function fetchLinks(string $table, string $column, array $labelIds): array
    {
        $connection = $this->getConnection();
        $select = $connection->select()
            ->from($this->getTable($table), ['label_id', $column])
            ->where('label_id IN (?)', $labelIds);
        $links = [];
        foreach ($connection->fetchAll($select) as $row) {
            $links[(int) $row['label_id']][] = (int) $row[$column];
        }
        return $links;
    }

    /**
     * Replaces the link rows of one label.
     *
     * @param string $table
     * @param string $column
     * @param int $labelId
     * @param int[] $ids
     * @return void
     */
    private function replaceLinks(string $table, string $column, int $labelId, array $ids): void
    {
        $connection = $this->getConnection();
        $tableName = $this->getTable($table);
        $connection->delete($tableName, ['label_id = ?' => $labelId]);
        if ($ids) {
            $connection->insertMultiple($tableName, array_map(
                static fn(int $id): array => ['label_id' => $labelId, $column => $id],
                array_values(array_unique($ids))
            ));
        }
    }

    /**
     * Inserts or updates one appearance row; null deletes it.
     *
     * @param int $labelId
     * @param string $context
     * @param AppearanceInterface|null $appearance
     * @return void
     */
    private function saveAppearance(int $labelId, string $context, ?AppearanceInterface $appearance): void
    {
        $connection = $this->getConnection();
        $table = $this->getTable(self::APPEARANCE_TABLE);
        if ($appearance === null) {
            $connection->delete($table, ['label_id = ?' => $labelId, 'context = ?' => $context]);
            return;
        }
        $values = [
            AppearanceInterface::TYPE => $appearance->getType(),
            AppearanceInterface::TEXT => $appearance->getText(),
            AppearanceInterface::BACKGROUND_COLOR => $appearance->getBackgroundColor(),
            AppearanceInterface::TEXT_COLOR => $appearance->getTextColor(),
            AppearanceInterface::SHAPE => $appearance->getShape(),
            AppearanceInterface::TEXT_SIZE => $appearance->getTextSize(),
            AppearanceInterface::IMAGE => $appearance->getImage(),
            AppearanceInterface::IMAGE_ALT => $appearance->getImageAlt(),
            AppearanceInterface::WIDTH_PERCENT => $appearance->getWidthPercent(),
            AppearanceInterface::CORNER_RADIUS => $appearance->getCornerRadius(),
            AppearanceInterface::POSITION => $appearance->getPosition(),
        ];
        $connection->insertOnDuplicate(
            $table,
            ['label_id' => $labelId, 'context' => $context] + $values,
            array_keys($values)
        );
    }
}
