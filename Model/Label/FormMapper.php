<?php
declare(strict_types=1);

namespace Majistar\ProductLabels\Model\Label;

use DateTimeImmutable;
use DateTimeZone;
use Majistar\ProductLabels\Api\Data\AppearanceInterface;
use Majistar\ProductLabels\Api\Data\AppearanceInterfaceFactory;
use Majistar\ProductLabels\Api\Data\LabelInterface;
use Majistar\ProductLabels\Model\Label;

/**
 * Converts between a label and the data of the admin label form.
 */
class FormMapper
{
    public const SCOPE_PRODUCT = 'product_appearance';
    public const SCOPE_LISTING = 'listing_appearance';
    public const SCOPE_CART = 'cart_appearance';
    public const USE_PRODUCT = 'use_product';
    public const USE_LISTING = 'use_listing';
    public const PERSISTOR_KEY = 'majistar_product_label';

    private const DEFAULT_BACKGROUND_COLOR = '#e11d48';
    private const DEFAULT_TEXT_COLOR = '#ffffff';

    /**
     * @param AppearanceInterfaceFactory $appearanceFactory
     * @param ImageInfo $imageInfo
     */
    public function __construct(
        private readonly AppearanceInterfaceFactory $appearanceFactory,
        private readonly ImageInfo $imageInfo
    ) {
    }

    /**
     * Form data of a new label.
     *
     * @param int[] $customerGroupIds all customer groups (selected by default)
     * @return array
     */
    public function defaults(array $customerGroupIds): array
    {
        $appearance = $this->appearanceDefaults();
        return [
            LabelInterface::IS_ACTIVE => '1',
            LabelInterface::PRIORITY => '0',
            LabelInterface::STOP_PROCESSING => '0',
            LabelInterface::STORE_IDS => ['0'],
            LabelInterface::CUSTOMER_GROUP_IDS => array_map('strval', $customerGroupIds),
            LabelInterface::SHOW_ON => [LabelInterface::SHOW_ON_PRODUCT, LabelInterface::SHOW_ON_LISTING],
            LabelInterface::IS_NEW => '0',
            LabelInterface::IS_ON_SALE => '0',
            LabelInterface::STOCK_STATUS => LabelInterface::STOCK_ANY,
            LabelInterface::USE_FOR_PARENT => '0',
            self::SCOPE_PRODUCT => $appearance,
            self::SCOPE_LISTING => [self::USE_PRODUCT => '1'] + $appearance,
            self::SCOPE_CART => [self::USE_LISTING => '1'] + $appearance,
        ];
    }

    /**
     * Form data of a saved label.
     *
     * @param Label $label
     * @return array
     */
    public function toFormData(Label $label): array
    {
        $product = $this->appearanceToForm($label->getProductAppearance());
        $listing = $label->getListingAppearance();
        $listingForm = $listing === null ? $product : $this->appearanceToForm($listing);
        $cart = $label->getCartAppearance();
        return [
            LabelInterface::LABEL_ID => (string) $label->getLabelId(),
            LabelInterface::NAME => $label->getName(),
            LabelInterface::IS_ACTIVE => $this->flagToForm($label->getIsActive()),
            LabelInterface::PRIORITY => (string) $label->getPriority(),
            LabelInterface::STOP_PROCESSING => $this->flagToForm($label->getStopProcessing()),
            LabelInterface::ACTIVE_FROM => $label->getActiveFrom(),
            LabelInterface::ACTIVE_TO => $label->getActiveTo(),
            LabelInterface::SHOW_ON => $label->getShowOn(),
            LabelInterface::STORE_IDS => array_map('strval', $label->getStoreIds()),
            LabelInterface::CUSTOMER_GROUP_IDS => array_map('strval', $label->getCustomerGroupIds()),
            LabelInterface::IS_NEW => $this->flagToForm($label->getIsNew()),
            LabelInterface::IS_ON_SALE => $this->flagToForm($label->getIsOnSale()),
            LabelInterface::MIN_DISCOUNT_PERCENT => $this->numberToForm($label->getMinDiscountPercent()),
            LabelInterface::STOCK_STATUS => $label->getStockStatus(),
            LabelInterface::LOW_STOCK_QTY => $this->numberToForm($label->getLowStockQty()),
            LabelInterface::CATEGORY_IDS => array_map('strval', $label->getCategoryIds()),
            LabelInterface::PRICE_FROM => $this->numberToForm($label->getPriceFrom()),
            LabelInterface::PRICE_TO => $this->numberToForm($label->getPriceTo()),
            LabelInterface::USE_FOR_PARENT => $this->flagToForm($label->getUseForParent()),
            self::SCOPE_PRODUCT => $product,
            self::SCOPE_LISTING => [self::USE_PRODUCT => $listing === null ? '1' : '0'] + $listingForm,
            self::SCOPE_CART => $cart === null
                ? [self::USE_LISTING => '1'] + $listingForm
                : [self::USE_LISTING => '0'] + $this->appearanceToForm($cart),
        ];
    }

    /**
     * Applies the posted form onto a label.
     *
     * @param array $data POST of the form, image fields already reduced to a file name (or null)
     * @param Label $label
     * @return Label
     */
    public function fromFormData(array $data, Label $label): Label
    {
        $label->setName((string) $this->text($data[LabelInterface::NAME] ?? null))
            ->setIsActive($this->flag($data[LabelInterface::IS_ACTIVE] ?? '0'))
            ->setPriority((int) ($data[LabelInterface::PRIORITY] ?? 0))
            ->setStopProcessing($this->flag($data[LabelInterface::STOP_PROCESSING] ?? '0'))
            ->setActiveFrom($this->date($data[LabelInterface::ACTIVE_FROM] ?? null))
            ->setActiveTo($this->date($data[LabelInterface::ACTIVE_TO] ?? null))
            ->setShowOn($this->list($data[LabelInterface::SHOW_ON] ?? []))
            ->setStoreIds($this->list($data[LabelInterface::STORE_IDS] ?? []))
            ->setCustomerGroupIds($this->list($data[LabelInterface::CUSTOMER_GROUP_IDS] ?? []))
            ->setIsNew($this->flag($data[LabelInterface::IS_NEW] ?? '0'))
            ->setIsOnSale($this->flag($data[LabelInterface::IS_ON_SALE] ?? '0'))
            ->setMinDiscountPercent($this->nullableInt($data[LabelInterface::MIN_DISCOUNT_PERCENT] ?? null))
            ->setStockStatus((string) ($data[LabelInterface::STOCK_STATUS] ?? LabelInterface::STOCK_ANY))
            ->setLowStockQty($this->nullableFloat($data[LabelInterface::LOW_STOCK_QTY] ?? null))
            ->setCategoryIds($this->list($data[LabelInterface::CATEGORY_IDS] ?? []))
            ->setPriceFrom($this->nullableFloat($data[LabelInterface::PRICE_FROM] ?? null))
            ->setPriceTo($this->nullableFloat($data[LabelInterface::PRICE_TO] ?? null))
            ->setUseForParent($this->flag($data[LabelInterface::USE_FOR_PARENT] ?? '0'))
            ->setProductAppearance($this->appearanceFromForm((array) ($data[self::SCOPE_PRODUCT] ?? [])));

        $listing = (array) ($data[self::SCOPE_LISTING] ?? []);
        $label->setListingAppearance(
            $this->flag($listing[self::USE_PRODUCT] ?? '1') ? null : $this->appearanceFromForm($listing)
        );
        $cart = (array) ($data[self::SCOPE_CART] ?? []);
        $label->setCartAppearance(
            $this->flag($cart[self::USE_LISTING] ?? '1') ? null : $this->appearanceFromForm($cart)
        );

        $conditions = $data['rule']['conditions'] ?? null;
        if (is_array($conditions)) {
            $label->loadPost(['conditions' => $conditions]);
        }
        return $label;
    }

    /**
     * Default appearance fields of a new label.
     *
     * @return array
     */
    private function appearanceDefaults(): array
    {
        return [
            AppearanceInterface::TYPE => AppearanceInterface::TYPE_TEXT,
            AppearanceInterface::TEXT => '',
            AppearanceInterface::BACKGROUND_COLOR => self::DEFAULT_BACKGROUND_COLOR,
            AppearanceInterface::TEXT_COLOR => self::DEFAULT_TEXT_COLOR,
            AppearanceInterface::SHAPE => AppearanceInterface::SHAPE_RECTANGLE,
            AppearanceInterface::TEXT_SIZE => AppearanceInterface::TEXT_SIZE_MEDIUM,
            AppearanceInterface::IMAGE => [],
            AppearanceInterface::IMAGE_ALT => '',
            AppearanceInterface::WIDTH_PERCENT => (string) AppearanceInterface::DEFAULT_WIDTH_PERCENT,
            AppearanceInterface::CORNER_RADIUS => (string) AppearanceInterface::DEFAULT_CORNER_RADIUS,
            AppearanceInterface::POSITION => AppearanceInterface::POSITION_TOP_LEFT,
        ];
    }

    /**
     * Appearance fields for the form.
     *
     * @param AppearanceInterface $appearance
     * @return array
     */
    private function appearanceToForm(AppearanceInterface $appearance): array
    {
        $image = $appearance->getImage();
        return [
            AppearanceInterface::TYPE => $appearance->getType(),
            AppearanceInterface::TEXT => (string) $appearance->getText(),
            AppearanceInterface::BACKGROUND_COLOR => (string) $appearance->getBackgroundColor(),
            AppearanceInterface::TEXT_COLOR => (string) $appearance->getTextColor(),
            AppearanceInterface::SHAPE => $appearance->getShape(),
            AppearanceInterface::TEXT_SIZE => $appearance->getTextSize(),
            AppearanceInterface::IMAGE => $image === null ? [] : [$this->imageInfo->toFormValue($image)],
            AppearanceInterface::IMAGE_ALT => (string) $appearance->getImageAlt(),
            AppearanceInterface::WIDTH_PERCENT => (string) $appearance->getWidthPercent(),
            AppearanceInterface::CORNER_RADIUS => (string) $appearance->getCornerRadius(),
            AppearanceInterface::POSITION => $appearance->getPosition(),
        ];
    }

    /**
     * Appearance from the posted fields.
     *
     * @param array $data
     * @return AppearanceInterface
     */
    private function appearanceFromForm(array $data): AppearanceInterface
    {
        $image = $data[AppearanceInterface::IMAGE] ?? null;
        $width = $this->nullableInt($data[AppearanceInterface::WIDTH_PERCENT] ?? null);
        $radius = $this->nullableInt($data[AppearanceInterface::CORNER_RADIUS] ?? null);

        $appearance = $this->appearanceFactory->create();
        $appearance->setType((string) ($data[AppearanceInterface::TYPE] ?? AppearanceInterface::TYPE_TEXT))
            ->setText($this->text($data[AppearanceInterface::TEXT] ?? null))
            ->setBackgroundColor($this->color($data[AppearanceInterface::BACKGROUND_COLOR] ?? null))
            ->setTextColor($this->color($data[AppearanceInterface::TEXT_COLOR] ?? null))
            ->setShape((string) ($data[AppearanceInterface::SHAPE] ?? AppearanceInterface::SHAPE_RECTANGLE))
            ->setTextSize((string) ($data[AppearanceInterface::TEXT_SIZE] ?? AppearanceInterface::TEXT_SIZE_MEDIUM))
            ->setImage(is_string($image) && $image !== '' ? $image : null)
            ->setImageAlt($this->text($data[AppearanceInterface::IMAGE_ALT] ?? null))
            ->setWidthPercent($width ?? AppearanceInterface::DEFAULT_WIDTH_PERCENT)
            ->setCornerRadius($radius ?? AppearanceInterface::DEFAULT_CORNER_RADIUS)
            ->setPosition((string) ($data[AppearanceInterface::POSITION] ?? AppearanceInterface::POSITION_TOP_LEFT));
        return $appearance;
    }

    /**
     * Posted checkbox value.
     *
     * @param mixed $value
     * @return bool
     */
    private function flag(mixed $value): bool
    {
        return in_array($value, [true, 1, '1', 'true'], true);
    }

    /**
     * Checkbox value for the form.
     *
     * @param bool $value
     * @return string
     */
    private function flagToForm(bool $value): string
    {
        return $value ? '1' : '0';
    }

    /**
     * Number for the form ('' when empty).
     *
     * @param int|float|null $value
     * @return string
     */
    private function numberToForm(int|float|null $value): string
    {
        return $value === null ? '' : (string) $value;
    }

    /**
     * Trimmed text, null when empty.
     *
     * @param mixed $value
     * @return string|null
     */
    private function text(mixed $value): ?string
    {
        if (!is_scalar($value)) {
            return null;
        }
        $value = trim((string) $value);
        return $value === '' ? null : $value;
    }

    /**
     * Lower-case hex color, null when empty (the validator checks the format).
     *
     * @param mixed $value
     * @return string|null
     */
    private function color(mixed $value): ?string
    {
        $value = $this->text($value);
        return $value === null ? null : strtolower($value);
    }

    /**
     * Posted list (multiselect, checkboxset); a missing field is an empty list.
     *
     * @param mixed $value
     * @return array
     */
    private function list(mixed $value): array
    {
        return is_array($value) ? array_values($value) : [];
    }

    /**
     * Integer, null when empty.
     *
     * @param mixed $value
     * @return int|null
     */
    private function nullableInt(mixed $value): ?int
    {
        return is_numeric($value) ? (int) $value : null;
    }

    /**
     * Float, null when empty.
     *
     * @param mixed $value
     * @return float|null
     */
    private function nullableFloat(mixed $value): ?float
    {
        return is_numeric($value) ? (float) $value : null;
    }

    /**
     * UTC date-time "Y-m-d H:i:s"; an unparsable value is kept as is so that the validator reports it.
     *
     * @param mixed $value
     * @return string|null
     */
    private function date(mixed $value): ?string
    {
        $value = $this->text($value);
        if ($value === null) {
            return null;
        }
        $utc = new DateTimeZone('UTC');
        try {
            $date = new DateTimeImmutable($value, $utc);
        } catch (\Exception) {
            return $value;
        }
        return $date->setTimezone($utc)->format(Validator::DATE_FORMAT);
    }
}
