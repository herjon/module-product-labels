<?php
declare(strict_types=1);

namespace Majistar\ProductLabels\Model\Label;

use Majistar\ProductLabels\Api\Data\AppearanceInterface;
use Magento\Framework\Api\AbstractSimpleObject;
use Magento\Framework\Api\Data\ImageContentInterface;

/**
 * Appearance DTO; persisted by the label resource model in majistar_product_label_appearance.
 */
class Appearance extends AbstractSimpleObject implements AppearanceInterface
{
    /**
     * @inheritdoc
     */
    public function getType(): string
    {
        return $this->stringOr(self::TYPE, self::TYPE_TEXT);
    }

    /**
     * @inheritdoc
     */
    public function setType(string $type): AppearanceInterface
    {
        return $this->setData(self::TYPE, $type);
    }

    /**
     * @inheritdoc
     */
    public function getText(): ?string
    {
        return $this->nullableString(self::TEXT);
    }

    /**
     * @inheritdoc
     */
    public function setText(?string $text): AppearanceInterface
    {
        return $this->setData(self::TEXT, $text);
    }

    /**
     * @inheritdoc
     */
    public function getBackgroundColor(): ?string
    {
        return $this->nullableString(self::BACKGROUND_COLOR);
    }

    /**
     * @inheritdoc
     */
    public function setBackgroundColor(?string $color): AppearanceInterface
    {
        return $this->setData(self::BACKGROUND_COLOR, $color);
    }

    /**
     * @inheritdoc
     */
    public function getTextColor(): ?string
    {
        return $this->nullableString(self::TEXT_COLOR);
    }

    /**
     * @inheritdoc
     */
    public function setTextColor(?string $color): AppearanceInterface
    {
        return $this->setData(self::TEXT_COLOR, $color);
    }

    /**
     * @inheritdoc
     */
    public function getShape(): string
    {
        return $this->stringOr(self::SHAPE, self::SHAPE_RECTANGLE);
    }

    /**
     * @inheritdoc
     */
    public function setShape(string $shape): AppearanceInterface
    {
        return $this->setData(self::SHAPE, $shape);
    }

    /**
     * @inheritdoc
     */
    public function getTextSize(): string
    {
        return $this->stringOr(self::TEXT_SIZE, self::TEXT_SIZE_MEDIUM);
    }

    /**
     * @inheritdoc
     */
    public function setTextSize(string $textSize): AppearanceInterface
    {
        return $this->setData(self::TEXT_SIZE, $textSize);
    }

    /**
     * @inheritdoc
     */
    public function getImage(): ?string
    {
        return $this->nullableString(self::IMAGE);
    }

    /**
     * @inheritdoc
     */
    public function setImage(?string $image): AppearanceInterface
    {
        return $this->setData(self::IMAGE, $image);
    }

    /**
     * @inheritdoc
     */
    public function getImageAlt(): ?string
    {
        return $this->nullableString(self::IMAGE_ALT);
    }

    /**
     * @inheritdoc
     */
    public function setImageAlt(?string $imageAlt): AppearanceInterface
    {
        return $this->setData(self::IMAGE_ALT, $imageAlt);
    }

    /**
     * @inheritdoc
     */
    public function getImageContent(): ?ImageContentInterface
    {
        $content = $this->_get(self::IMAGE_CONTENT);
        return $content instanceof ImageContentInterface ? $content : null;
    }

    /**
     * @inheritdoc
     */
    public function setImageContent(?ImageContentInterface $imageContent): AppearanceInterface
    {
        return $this->setData(self::IMAGE_CONTENT, $imageContent);
    }

    /**
     * @inheritdoc
     */
    public function getWidthPercent(): int
    {
        $value = $this->_get(self::WIDTH_PERCENT);
        return is_numeric($value) ? (int) $value : self::DEFAULT_WIDTH_PERCENT;
    }

    /**
     * @inheritdoc
     */
    public function setWidthPercent(int $widthPercent): AppearanceInterface
    {
        return $this->setData(self::WIDTH_PERCENT, $widthPercent);
    }

    /**
     * @inheritdoc
     */
    public function getCornerRadius(): int
    {
        $value = $this->_get(self::CORNER_RADIUS);
        return is_numeric($value) ? (int) $value : self::DEFAULT_CORNER_RADIUS;
    }

    /**
     * @inheritdoc
     */
    public function setCornerRadius(int $cornerRadius): AppearanceInterface
    {
        return $this->setData(self::CORNER_RADIUS, $cornerRadius);
    }

    /**
     * @inheritdoc
     */
    public function getPosition(): string
    {
        return $this->stringOr(self::POSITION, self::POSITION_TOP_LEFT);
    }

    /**
     * @inheritdoc
     */
    public function setPosition(string $position): AppearanceInterface
    {
        return $this->setData(self::POSITION, $position);
    }

    /**
     * String value, or null when missing or empty.
     *
     * @param string $key
     * @return string|null
     */
    private function nullableString(string $key): ?string
    {
        $value = $this->_get($key);
        return $value === null || $value === '' ? null : (string) $value;
    }

    /**
     * String value, or the default when missing or empty.
     *
     * @param string $key
     * @param string $default
     * @return string
     */
    private function stringOr(string $key, string $default): string
    {
        return $this->nullableString($key) ?? $default;
    }
}
