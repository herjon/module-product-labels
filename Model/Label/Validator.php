<?php
declare(strict_types=1);

namespace Majistar\ProductLabels\Model\Label;

use DateTimeImmutable;
use DateTimeZone;
use Majistar\ProductLabels\Api\Data\AppearanceInterface;
use Majistar\ProductLabels\Api\Data\LabelInterface;
use Magento\Framework\Phrase;

/**
 * Label business rules for the admin form and the API; reports every problem at once.
 */
class Validator
{
    public const DATE_FORMAT = 'Y-m-d H:i:s';
    public const IMAGE_NAME_PATTERN = '/^[A-Za-z0-9][A-Za-z0-9._-]*\.(jpe?g|png|gif|webp|avif)$/i';

    private const HEX_COLOR_PATTERN = '/^#([0-9a-f]{3}|[0-9a-f]{6}|[0-9a-f]{8})$/i';
    private const MAX_TEXT_LENGTH = 255;

    /**
     * Validates a label.
     *
     * @param LabelInterface $label
     * @return Phrase[] empty when the label is valid
     */
    public function validate(LabelInterface $label): array
    {
        $errors = [];
        $name = trim($label->getName());
        if ($name === '') {
            $errors[] = __('Name is required.');
        } elseif (mb_strlen($name) > self::MAX_TEXT_LENGTH) {
            $errors[] = __('Name cannot be longer than %1 characters.', self::MAX_TEXT_LENGTH);
        }
        if ($label->getPriority() < 0) {
            $errors[] = __('Priority cannot be negative.');
        }
        $showOn = $label->getShowOn();
        if (!$showOn) {
            $errors[] = __('Choose where the label is shown.');
        } elseif (array_diff($showOn, LabelInterface::SHOW_ON_VALUES)) {
            $errors[] = __(
                '"Show on" accepts only these values: %1.',
                implode(', ', LabelInterface::SHOW_ON_VALUES)
            );
        }
        if (!$label->getStoreIds()) {
            $errors[] = __('Select at least one store view.');
        }
        if (!$label->getCustomerGroupIds()) {
            $errors[] = __('Select at least one customer group.');
        }
        $conditions = $label->getConditionsSerialized();
        if ($conditions !== null && !is_array(json_decode($conditions, true))) {
            $errors[] = __('Advanced conditions are not valid JSON.');
        }

        $errors = array_merge(
            $errors,
            $this->validateDates($label),
            $this->validateFilters($label),
            $this->validateAppearance($label->getProductAppearance(), __('Product page'))
        );
        $listing = $label->getListingAppearance();
        if ($listing !== null) {
            $errors = array_merge($errors, $this->validateAppearance($listing, __('Product listings')));
        }
        $cart = $label->getCartAppearance();
        if ($cart !== null) {
            $errors = array_merge($errors, $this->validateAppearance($cart, __('Cart')));
        }
        return $errors;
    }

    /**
     * Active from/to: valid UTC date-times, "to" after "from".
     *
     * @param LabelInterface $label
     * @return Phrase[]
     */
    private function validateDates(LabelInterface $label): array
    {
        $errors = [];
        $from = $this->parseDate($label->getActiveFrom());
        $to = $this->parseDate($label->getActiveTo());
        if ($from === false) {
            $errors[] = __('"Active from" is not a valid date.');
        }
        if ($to === false) {
            $errors[] = __('"Active to" is not a valid date.');
        }
        if ($from instanceof DateTimeImmutable && $to instanceof DateTimeImmutable && $to <= $from) {
            $errors[] = __('"Active to" must be later than "Active from".');
        }
        return $errors;
    }

    /**
     * Parses a stored date-time strictly.
     *
     * @param string|null $value
     * @return DateTimeImmutable|false|null null when empty, false when invalid
     */
    private function parseDate(?string $value): DateTimeImmutable|false|null
    {
        if ($value === null) {
            return null;
        }
        $date = DateTimeImmutable::createFromFormat('!' . self::DATE_FORMAT, $value, new DateTimeZone('UTC'));
        return $date !== false && $date->format(self::DATE_FORMAT) === $value ? $date : false;
    }

    /**
     * Quick filters.
     *
     * @param LabelInterface $label
     * @return Phrase[]
     */
    private function validateFilters(LabelInterface $label): array
    {
        $errors = [];
        $discount = $label->getMinDiscountPercent();
        if ($discount !== null && ($discount < 0 || $discount > 100)) {
            $errors[] = __('Minimum discount must be between 0 and 100.');
        }
        $stock = $label->getStockStatus();
        if (!in_array($stock, LabelInterface::STOCK_STATUSES, true)) {
            $errors[] = __('Stock filter "%1" is not valid.', $stock);
        }
        $lowStockQty = $label->getLowStockQty();
        if ($stock === LabelInterface::STOCK_LOW && ($lowStockQty === null || $lowStockQty < 0)) {
            $errors[] = __('Enter the quantity below which stock is low.');
        }
        $priceFrom = $label->getPriceFrom();
        $priceTo = $label->getPriceTo();
        if (($priceFrom !== null && $priceFrom < 0) || ($priceTo !== null && $priceTo < 0)) {
            $errors[] = __('Prices cannot be negative.');
        }
        if ($priceFrom !== null && $priceTo !== null && $priceTo < $priceFrom) {
            $errors[] = __('"Price to" must be greater than or equal to "Price from".');
        }
        return $errors;
    }

    /**
     * One appearance (product page or listings).
     *
     * @param AppearanceInterface $appearance
     * @param Phrase $context
     * @return Phrase[]
     */
    private function validateAppearance(AppearanceInterface $appearance, Phrase $context): array
    {
        $type = $appearance->getType();
        if (!in_array($type, AppearanceInterface::TYPES, true)) {
            return [__('%1: type "%2" is not valid.', $context, $type)];
        }
        $errors = [];
        $text = (string) $appearance->getText();
        if ($type === AppearanceInterface::TYPE_TEXT && trim($text) === '') {
            $errors[] = __('%1: enter the label text.', $context);
        }
        if (mb_strlen($text) > self::MAX_TEXT_LENGTH) {
            $errors[] = __('%1: the text cannot be longer than %2 characters.', $context, self::MAX_TEXT_LENGTH);
        }
        $colors = [
            [__('background color'), $appearance->getBackgroundColor()],
            [__('text color'), $appearance->getTextColor()],
        ];
        foreach ($colors as [$colorName, $value]) {
            if ($value !== null && !preg_match(self::HEX_COLOR_PATTERN, $value)) {
                $errors[] = __('%1: %2 must be a hex color such as #ff0000.', $context, $colorName);
            }
        }
        if (!in_array($appearance->getShape(), AppearanceInterface::SHAPES, true)) {
            $errors[] = __('%1: shape is not valid.', $context);
        }
        if (!in_array($appearance->getTextSize(), AppearanceInterface::TEXT_SIZES, true)) {
            $errors[] = __('%1: text size is not valid.', $context);
        }
        $image = $appearance->getImage();
        $sentAsContent = $appearance->getImageContent() !== null;
        if ($type === AppearanceInterface::TYPE_IMAGE && $image === null && !$sentAsContent) {
            $errors[] = __('%1: upload an image.', $context);
        }
        if ($image !== null && !preg_match(self::IMAGE_NAME_PATTERN, $image)) {
            $errors[] = __('%1: the image must be a JPG, PNG, GIF, WebP or AVIF file.', $context);
        }
        $width = $appearance->getWidthPercent();
        if ($width < AppearanceInterface::MIN_WIDTH_PERCENT || $width > AppearanceInterface::MAX_WIDTH_PERCENT) {
            $errors[] = __(
                '%1: width must be between %2 and %3 percent.',
                $context,
                AppearanceInterface::MIN_WIDTH_PERCENT,
                AppearanceInterface::MAX_WIDTH_PERCENT
            );
        }
        $radius = $appearance->getCornerRadius();
        if ($radius < AppearanceInterface::MIN_CORNER_RADIUS || $radius > AppearanceInterface::MAX_CORNER_RADIUS) {
            $errors[] = __(
                '%1: corner radius must be between %2 and %3 px.',
                $context,
                AppearanceInterface::MIN_CORNER_RADIUS,
                AppearanceInterface::MAX_CORNER_RADIUS
            );
        }
        if (!in_array($appearance->getPosition(), AppearanceInterface::POSITIONS, true)) {
            $errors[] = __('%1: position is not valid.', $context);
        }
        return $errors;
    }
}
