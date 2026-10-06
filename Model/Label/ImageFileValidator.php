<?php
declare(strict_types=1);

namespace Majistar\ProductLabels\Model\Label;

use Majistar\ProductLabels\Api\Data\AppearanceInterface;
use Magento\Framework\Exception\LocalizedException;

/**
 * Checks an uploaded label image: size, extension and real content (no SVG: it can contain scripts).
 */
class ImageFileValidator
{
    public const MAX_SIZE = 2097152;

    private const IMAGE_TYPES = [IMAGETYPE_JPEG, IMAGETYPE_PNG, IMAGETYPE_GIF, IMAGETYPE_WEBP, IMAGETYPE_AVIF];

    /**
     * Validates one entry of $_FILES.
     *
     * @param array $file keys name, tmp_name, size, error
     * @return void
     * @throws LocalizedException
     */
    public function validate(array $file): void
    {
        if ((int) ($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            throw new LocalizedException(__('The image could not be uploaded. Please try again.'));
        }
        if ((int) ($file['size'] ?? 0) > self::MAX_SIZE) {
            throw new LocalizedException(__('The image is larger than 2 MB.'));
        }
        $name = (string) ($file['name'] ?? '');
        $extension = str_contains($name, '.') ? strtolower(substr($name, strrpos($name, '.') + 1)) : '';
        if (!in_array($extension, AppearanceInterface::IMAGE_EXTENSIONS, true)) {
            throw new LocalizedException(__('Upload a JPG, PNG, GIF, WebP or AVIF image.'));
        }
        if (!in_array($this->imageType((string) ($file['tmp_name'] ?? '')), self::IMAGE_TYPES, true)) {
            throw new LocalizedException(__('The file is not a valid image.'));
        }
    }

    /**
     * IMAGETYPE_* constant of a file, or null when it is not an image.
     *
     * @param string $path
     * @return int|null
     */
    private function imageType(string $path): ?int
    {
        // phpcs:ignore Magento2.Functions.DiscouragedFunction -- PHP upload temp file, outside Magento directories
        if ($path === '' || !is_file($path)) {
            return null;
        }
        try {
            // phpcs:ignore Magento2.Functions.DiscouragedFunction -- reads the real image type of the upload
            $info = getimagesize($path);
        } catch (\Throwable) {
            return null;
        }
        return $info === false ? null : (int) $info[2];
    }
}
