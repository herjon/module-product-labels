<?php
declare(strict_types=1);

namespace Majistar\ProductLabels\Model\Label;

use Magento\Framework\Exception\LocalizedException;

/**
 * Turns an image field value of the label form into the stored file name, moving fresh uploads out of tmp.
 */
class ImageProcessor
{
    /**
     * @param ImageStorage $imageStorage
     */
    public function __construct(
        private readonly ImageStorage $imageStorage
    ) {
    }

    /**
     * Stored file name for the field value.
     *
     * @param mixed $value e.g. [['name' => 'sale.png', 'tmp_name' => '...', 'url' => '...']] or []
     * @return string|null null when the field is empty
     * @throws LocalizedException
     */
    public function process(mixed $value): ?string
    {
        if (!is_array($value) || !isset($value[0]) || !is_array($value[0])) {
            return null;
        }
        $first = $value[0];
        $name = (string) ($first['file'] ?? $first['name'] ?? '');
        if ($name === '') {
            return null;
        }
        if (!preg_match(Validator::IMAGE_NAME_PATTERN, $name)) {
            throw new LocalizedException(__('The image file name "%1" is not allowed.', $name));
        }
        if (!empty($first['tmp_name'])) {
            return $this->imageStorage->moveFromTmp($name);
        }
        return $name;
    }
}
