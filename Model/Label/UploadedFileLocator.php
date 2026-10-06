<?php
declare(strict_types=1);

namespace Majistar\ProductLabels\Model\Label;

/**
 * Finds an uploaded file posted under a nested field name such as "product_appearance[image]".
 */
class UploadedFileLocator
{
    /**
     * Entry of the uploaded file (keys name, tmp_name, size, error, type), or [] when missing.
     *
     * @param array $files request files
     * @param string $fieldName "image" or "scope[image]"
     * @return array
     */
    public function locate(array $files, string $fieldName): array
    {
        $file = preg_match('/^(.+?)\[(.+?)\]$/', $fieldName, $parts)
            ? ($files[$parts[1]][$parts[2]] ?? null)
            : ($files[$fieldName] ?? null);
        return is_array($file) ? $file : [];
    }
}
