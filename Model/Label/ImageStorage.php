<?php
declare(strict_types=1);

namespace Majistar\ProductLabels\Model\Label;

use Majistar\ProductLabels\Api\Data\AppearanceInterface;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\File\Name;
use Magento\Framework\Filesystem;
use Magento\Framework\UrlInterface;
use Magento\MediaStorage\Helper\File\Storage\Database;
use Magento\MediaStorage\Model\File\UploaderFactory;
use Magento\Store\Model\StoreManagerInterface;

/**
 * Stores label images; not the core ImageUploader, whose Media Gallery plugin fails on WebP/AVIF.
 */
class ImageStorage
{
    public const TMP_PATH = ImageInfo::BASE_PATH . '/tmp';

    private const MIME_TYPES = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp', 'image/avif'];

    /**
     * @param UploaderFactory $uploaderFactory
     * @param Filesystem $filesystem
     * @param Name $fileName
     * @param Database $fileStorageDatabase
     * @param StoreManagerInterface $storeManager
     */
    public function __construct(
        private readonly UploaderFactory $uploaderFactory,
        private readonly Filesystem $filesystem,
        private readonly Name $fileName,
        private readonly Database $fileStorageDatabase,
        private readonly StoreManagerInterface $storeManager
    ) {
    }

    /**
     * Saves the uploaded file of the request in the tmp folder.
     *
     * @param string $fileId request field name, for example "product_appearance[image]"
     * @param string $originalName name of the file on the admin's computer
     * @return array{name: string, file: string, size: int, type: string, tmp_name: string, url: string}
     * @throws LocalizedException
     */
    public function saveToTmp(string $fileId, string $originalName): array
    {
        $uploader = $this->uploaderFactory->create(['fileId' => $fileId]);
        $uploader->setAllowedExtensions(AppearanceInterface::IMAGE_EXTENSIONS);
        $uploader->setAllowRenameFiles(true);
        if (!$uploader->checkMimeType(self::MIME_TYPES)) {
            throw new LocalizedException(__('File validation failed.'));
        }
        $media = $this->filesystem->getDirectoryWrite(DirectoryList::MEDIA);
        $result = $uploader->save($media->getAbsolutePath(self::TMP_PATH), $this->safeFileName($originalName));
        if (!$result || empty($result['file'])) {
            throw new LocalizedException(__('File can not be saved to the destination folder.'));
        }
        $file = ltrim((string) $result['file'], '/');
        $this->fileStorageDatabase->saveFile(self::TMP_PATH . '/' . $file);

        $mediaUrl = $this->storeManager->getStore()->getBaseUrl(UrlInterface::URL_TYPE_MEDIA);
        return [
            'name' => $file,
            'file' => $file,
            'size' => (int) ($result['size'] ?? 0),
            'type' => (string) ($result['type'] ?? ''),
            'tmp_name' => str_replace('\\', '/', (string) ($result['tmp_name'] ?? '')),
            'url' => rtrim($mediaUrl, '/') . '/' . self::TMP_PATH . '/' . $file,
        ];
    }

    /**
     * File name the label validator accepts: ASCII letters, digits and "._-", accents transliterated.
     *
     * @param string $originalName
     * @return string
     */
    public function safeFileName(string $originalName): string
    {
        $dot = strrpos($originalName, '.');
        $base = $dot === false ? $originalName : substr($originalName, 0, $dot);
        $extension = $dot === false ? '' : strtolower(substr($originalName, $dot + 1));
        if (function_exists('transliterator_transliterate')) {
            $base = (string) transliterator_transliterate('Latin-ASCII', $base);
        }
        $base = ltrim((string) preg_replace('/[^A-Za-z0-9._-]+/', '_', $base), '._-');
        return ($base === '' ? 'label' : $base) . '.' . $extension;
    }

    /**
     * Moves an uploaded file out of tmp, renaming it when the name is taken.
     *
     * @param string $name file name inside the tmp folder (already validated)
     * @return string stored file name
     * @throws LocalizedException
     */
    public function moveFromTmp(string $name): string
    {
        $media = $this->filesystem->getDirectoryWrite(DirectoryList::MEDIA);
        $tmpPath = self::TMP_PATH . '/' . $name;
        if (!$media->isFile($tmpPath)) {
            throw new LocalizedException(
                __('The uploaded image "%1" was not found. Please upload it again.', $name)
            );
        }
        $target = $this->fileName->getNewFileName($media->getAbsolutePath(ImageInfo::BASE_PATH . '/' . $name));
        $targetPath = ImageInfo::BASE_PATH . '/' . $target;
        $this->fileStorageDatabase->renameFile($tmpPath, $targetPath);
        $media->renameFile($tmpPath, $targetPath);
        return $target;
    }
}
