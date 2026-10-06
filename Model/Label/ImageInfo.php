<?php
declare(strict_types=1);

namespace Majistar\ProductLabels\Model\Label;

use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\File\Mime;
use Magento\Framework\Filesystem;
use Magento\Framework\UrlInterface;
use Magento\Store\Model\StoreManagerInterface;

/**
 * URL and file details of label images stored in pub/media/majistar_product_labels.
 */
class ImageInfo
{
    public const BASE_PATH = 'majistar_product_labels';

    /**
     * @param StoreManagerInterface $storeManager
     * @param Filesystem $filesystem
     * @param Mime $mime
     */
    public function __construct(
        private readonly StoreManagerInterface $storeManager,
        private readonly Filesystem $filesystem,
        private readonly Mime $mime
    ) {
    }

    /**
     * Public URL of a label image.
     *
     * @param string $file
     * @return string
     */
    public function getUrl(string $file): string
    {
        $mediaUrl = $this->storeManager->getStore()->getBaseUrl(UrlInterface::URL_TYPE_MEDIA);
        return rtrim($mediaUrl, '/') . '/' . self::BASE_PATH . '/' . ltrim($file, '/');
    }

    /**
     * Value of the image uploader field of the admin form.
     *
     * @param string $file
     * @return array{name: string, url: string, size?: int, type?: string}
     */
    public function toFormValue(string $file): array
    {
        $value = ['name' => $file, 'url' => $this->getUrl($file)];
        $media = $this->filesystem->getDirectoryRead(DirectoryList::MEDIA);
        $path = self::BASE_PATH . '/' . $file;
        if ($media->isFile($path)) {
            $value['size'] = (int) ($media->stat($path)['size'] ?? 0);
            $value['type'] = $this->mime->getMimeType($media->getAbsolutePath($path));
        }
        return $value;
    }
}
