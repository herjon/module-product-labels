<?php
declare(strict_types=1);

namespace Majistar\ProductLabels\Api\Data;

/**
 * A label ready to render on one product in one area.
 *
 * @api
 */
interface LabelViewInterface
{
    /**
     * Get label id.
     *
     * @return int
     */
    public function getLabelId(): int;

    /**
     * Get priority (lower first).
     *
     * @return int
     */
    public function getPriority(): int;

    /**
     * Get type: "text" or "image".
     *
     * @return string
     */
    public function getType(): string;

    /**
     * Text with variables replaced (text labels).
     *
     * @return string|null
     */
    public function getText(): ?string;

    /**
     * Public URL of the image (image labels).
     *
     * @return string|null
     */
    public function getImageUrl(): ?string;

    /**
     * Get image alt text.
     *
     * @return string|null
     */
    public function getImageAlt(): ?string;

    /**
     * Get shape: rectangle, pill or circle.
     *
     * @return string
     */
    public function getShape(): string;

    /**
     * Get text size: s, m or l.
     *
     * @return string
     */
    public function getTextSize(): string;

    /**
     * Get background color (hex).
     *
     * @return string|null
     */
    public function getBackgroundColor(): ?string;

    /**
     * Get text color (hex).
     *
     * @return string|null
     */
    public function getTextColor(): ?string;

    /**
     * Width of an image label in percent of the product image.
     *
     * @return int
     */
    public function getWidthPercent(): int;

    /**
     * Rounded corners in pixels (rectangle text labels and image labels).
     *
     * @return int
     */
    public function getCornerRadius(): int;

    /**
     * Position on the 3x3 grid, for example top-left.
     *
     * @return string
     */
    public function getPosition(): string;
}
