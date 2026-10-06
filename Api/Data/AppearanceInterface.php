<?php
declare(strict_types=1);

namespace Majistar\ProductLabels\Api\Data;

use Magento\Framework\Api\Data\ImageContentInterface;

/**
 * @api
 */
interface AppearanceInterface
{
    public const TYPE = 'type';
    public const TEXT = 'text';
    public const BACKGROUND_COLOR = 'background_color';
    public const TEXT_COLOR = 'text_color';
    public const SHAPE = 'shape';
    public const TEXT_SIZE = 'text_size';
    public const IMAGE = 'image';
    public const IMAGE_ALT = 'image_alt';
    public const IMAGE_CONTENT = 'image_content';
    public const WIDTH_PERCENT = 'width_percent';
    public const CORNER_RADIUS = 'corner_radius';
    public const POSITION = 'position';

    public const TYPE_TEXT = 'text';
    public const TYPE_IMAGE = 'image';
    public const TYPES = [self::TYPE_TEXT, self::TYPE_IMAGE];

    public const SHAPE_RECTANGLE = 'rectangle';
    public const SHAPE_PILL = 'pill';
    public const SHAPE_CIRCLE = 'circle';
    public const SHAPES = [self::SHAPE_RECTANGLE, self::SHAPE_PILL, self::SHAPE_CIRCLE];

    public const TEXT_SIZE_SMALL = 's';
    public const TEXT_SIZE_MEDIUM = 'm';
    public const TEXT_SIZE_LARGE = 'l';
    public const TEXT_SIZES = [self::TEXT_SIZE_SMALL, self::TEXT_SIZE_MEDIUM, self::TEXT_SIZE_LARGE];

    public const POSITION_TOP_LEFT = 'top-left';
    public const POSITION_TOP_CENTER = 'top-center';
    public const POSITION_TOP_RIGHT = 'top-right';
    public const POSITION_MIDDLE_LEFT = 'middle-left';
    public const POSITION_CENTER = 'center';
    public const POSITION_MIDDLE_RIGHT = 'middle-right';
    public const POSITION_BOTTOM_LEFT = 'bottom-left';
    public const POSITION_BOTTOM_CENTER = 'bottom-center';
    public const POSITION_BOTTOM_RIGHT = 'bottom-right';
    public const POSITIONS = [
        self::POSITION_TOP_LEFT,
        self::POSITION_TOP_CENTER,
        self::POSITION_TOP_RIGHT,
        self::POSITION_MIDDLE_LEFT,
        self::POSITION_CENTER,
        self::POSITION_MIDDLE_RIGHT,
        self::POSITION_BOTTOM_LEFT,
        self::POSITION_BOTTOM_CENTER,
        self::POSITION_BOTTOM_RIGHT,
    ];

    public const IMAGE_EXTENSIONS = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'avif'];

    public const DEFAULT_WIDTH_PERCENT = 25;
    public const MIN_WIDTH_PERCENT = 5;
    public const MAX_WIDTH_PERCENT = 100;

    public const DEFAULT_CORNER_RADIUS = 4;
    public const MIN_CORNER_RADIUS = 0;
    public const MAX_CORNER_RADIUS = 50;

    /**
     * Label type: "text" or "image".
     *
     * @return string
     */
    public function getType(): string;

    /**
     * Set type.
     *
     * @param string $type
     * @return \Majistar\ProductLabels\Api\Data\AppearanceInterface
     */
    public function setType(string $type): AppearanceInterface;

    /**
     * Text with optional variables such as {SAVE_PERCENT}.
     *
     * @return string|null
     */
    public function getText(): ?string;

    /**
     * Set text.
     *
     * @param string|null $text
     * @return \Majistar\ProductLabels\Api\Data\AppearanceInterface
     */
    public function setText(?string $text): AppearanceInterface;

    /**
     * Hex color, for example #e11d48.
     *
     * @return string|null
     */
    public function getBackgroundColor(): ?string;

    /**
     * Set background color.
     *
     * @param string|null $color
     * @return \Majistar\ProductLabels\Api\Data\AppearanceInterface
     */
    public function setBackgroundColor(?string $color): AppearanceInterface;

    /**
     * Hex color, for example #ffffff.
     *
     * @return string|null
     */
    public function getTextColor(): ?string;

    /**
     * Set text color.
     *
     * @param string|null $color
     * @return \Majistar\ProductLabels\Api\Data\AppearanceInterface
     */
    public function setTextColor(?string $color): AppearanceInterface;

    /**
     * Shape of a text label: rectangle, pill or circle.
     *
     * @return string
     */
    public function getShape(): string;

    /**
     * Set shape.
     *
     * @param string $shape
     * @return \Majistar\ProductLabels\Api\Data\AppearanceInterface
     */
    public function setShape(string $shape): AppearanceInterface;

    /**
     * Text size: s, m or l.
     *
     * @return string
     */
    public function getTextSize(): string;

    /**
     * Set text size.
     *
     * @param string $textSize
     * @return \Majistar\ProductLabels\Api\Data\AppearanceInterface
     */
    public function setTextSize(string $textSize): AppearanceInterface;

    /**
     * Image file name inside pub/media/majistar_product_labels.
     *
     * @return string|null
     */
    public function getImage(): ?string;

    /**
     * Set image.
     *
     * @param string|null $image
     * @return \Majistar\ProductLabels\Api\Data\AppearanceInterface
     */
    public function setImage(?string $image): AppearanceInterface;

    /**
     * Alternative text of the image.
     *
     * @return string|null
     */
    public function getImageAlt(): ?string;

    /**
     * Set image alt.
     *
     * @param string|null $imageAlt
     * @return \Majistar\ProductLabels\Api\Data\AppearanceInterface
     */
    public function setImageAlt(?string $imageAlt): AppearanceInterface;

    /**
     * New image as base64 (web API, write only); on save its file name becomes "image".
     *
     * @return \Magento\Framework\Api\Data\ImageContentInterface|null
     */
    public function getImageContent(): ?ImageContentInterface;

    /**
     * Set image content.
     *
     * @param \Magento\Framework\Api\Data\ImageContentInterface|null $imageContent
     * @return \Majistar\ProductLabels\Api\Data\AppearanceInterface
     */
    public function setImageContent(?ImageContentInterface $imageContent): AppearanceInterface;

    /**
     * Width of an image label, as a percentage of the product image width (5-100).
     *
     * @return int
     */
    public function getWidthPercent(): int;

    /**
     * Set width percent.
     *
     * @param int $widthPercent
     * @return \Majistar\ProductLabels\Api\Data\AppearanceInterface
     */
    public function setWidthPercent(int $widthPercent): AppearanceInterface;

    /**
     * Corner radius in pixels (0-50) of rectangle and image labels.
     *
     * @return int
     */
    public function getCornerRadius(): int;

    /**
     * Set corner radius.
     *
     * @param int $cornerRadius
     * @return \Majistar\ProductLabels\Api\Data\AppearanceInterface
     */
    public function setCornerRadius(int $cornerRadius): AppearanceInterface;

    /**
     * Position on the 3x3 grid, for example top-left.
     *
     * @return string
     */
    public function getPosition(): string;

    /**
     * Set position.
     *
     * @param string $position
     * @return \Majistar\ProductLabels\Api\Data\AppearanceInterface
     */
    public function setPosition(string $position): AppearanceInterface;
}
