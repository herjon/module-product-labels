<?php
declare(strict_types=1);

namespace Majistar\ProductLabels\Model\View;

use Majistar\ProductLabels\Api\Data\AppearanceInterface;
use Majistar\ProductLabels\Api\Data\LabelViewInterface;

class LabelView implements LabelViewInterface
{
    /**
     * @param int $labelId
     * @param int $priority
     * @param string $type
     * @param string|null $text
     * @param string|null $imageUrl
     * @param string|null $imageAlt
     * @param string $shape
     * @param string $textSize
     * @param string|null $backgroundColor
     * @param string|null $textColor
     * @param int $widthPercent
     * @param string $position
     * @param int $cornerRadius
     * @SuppressWarnings(PHPMD.ExcessiveParameterList)
     */
    public function __construct(
        private readonly int $labelId,
        private readonly int $priority,
        private readonly string $type,
        private readonly ?string $text,
        private readonly ?string $imageUrl,
        private readonly ?string $imageAlt,
        private readonly string $shape,
        private readonly string $textSize,
        private readonly ?string $backgroundColor,
        private readonly ?string $textColor,
        private readonly int $widthPercent,
        private readonly string $position,
        private readonly int $cornerRadius = AppearanceInterface::DEFAULT_CORNER_RADIUS
    ) {
    }

    /**
     * @inheritdoc
     */
    public function getLabelId(): int
    {
        return $this->labelId;
    }

    /**
     * @inheritdoc
     */
    public function getPriority(): int
    {
        return $this->priority;
    }

    /**
     * @inheritdoc
     */
    public function getType(): string
    {
        return $this->type;
    }

    /**
     * @inheritdoc
     */
    public function getText(): ?string
    {
        return $this->text;
    }

    /**
     * @inheritdoc
     */
    public function getImageUrl(): ?string
    {
        return $this->imageUrl;
    }

    /**
     * @inheritdoc
     */
    public function getImageAlt(): ?string
    {
        return $this->imageAlt;
    }

    /**
     * @inheritdoc
     */
    public function getShape(): string
    {
        return $this->shape;
    }

    /**
     * @inheritdoc
     */
    public function getTextSize(): string
    {
        return $this->textSize;
    }

    /**
     * @inheritdoc
     */
    public function getBackgroundColor(): ?string
    {
        return $this->backgroundColor;
    }

    /**
     * @inheritdoc
     */
    public function getTextColor(): ?string
    {
        return $this->textColor;
    }

    /**
     * @inheritdoc
     */
    public function getWidthPercent(): int
    {
        return $this->widthPercent;
    }

    /**
     * @inheritdoc
     */
    public function getCornerRadius(): int
    {
        return $this->cornerRadius;
    }

    /**
     * @inheritdoc
     */
    public function getPosition(): string
    {
        return $this->position;
    }
}
