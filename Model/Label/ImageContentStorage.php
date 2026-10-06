<?php
declare(strict_types=1);

namespace Majistar\ProductLabels\Model\Label;

use Magento\Framework\Api\Data\ImageContentInterface;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\File\Name;
use Magento\Framework\Filesystem;
use Magento\Framework\Phrase;
use Magento\MediaStorage\Helper\File\Storage\Database;

/**
 * Label images sent as base64 through the web API; the type is read from the bytes, not from the client.
 */
class ImageContentStorage
{
    public const MAX_BYTES = 2 * 1024 * 1024;

    private const EXTENSIONS = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/gif' => 'gif',
        'image/webp' => 'webp',
        'image/avif' => 'avif',
    ];

    /**
     * @param Filesystem $filesystem
     * @param Name $fileName
     * @param Database $fileStorageDatabase
     * @param ImageStorage $imageStorage
     */
    public function __construct(
        private readonly Filesystem $filesystem,
        private readonly Name $fileName,
        private readonly Database $fileStorageDatabase,
        private readonly ImageStorage $imageStorage
    ) {
    }

    /**
     * Problems with the sent image; empty when it can be saved.
     *
     * @param ImageContentInterface $content
     * @return Phrase[]
     */
    public function validate(ImageContentInterface $content): array
    {
        $bytes = $this->decode($content);
        if ($bytes === null) {
            return [__('The image is not valid base64 data.')];
        }
        if (strlen($bytes) > self::MAX_BYTES) {
            return [__('The image cannot be larger than 2 MB.')];
        }
        if ($this->extension($bytes) === null) {
            return [__('The image must be a JPG, PNG, GIF, WebP or AVIF file.')];
        }
        return [];
    }

    /**
     * Saves a validated image in the label media folder.
     *
     * @param ImageContentInterface $content
     * @return string stored file name (unique, safe characters, extension of the real type)
     */
    public function save(ImageContentInterface $content): string
    {
        $bytes = (string) $this->decode($content);
        $name = $this->imageStorage->safeFileName((string) $content->getName());
        $dot = strrpos($name, '.');
        $name = ($dot === false ? $name : substr($name, 0, $dot)) . '.' . $this->extension($bytes);

        $media = $this->filesystem->getDirectoryWrite(DirectoryList::MEDIA);
        $target = $this->fileName->getNewFileName($media->getAbsolutePath(ImageInfo::BASE_PATH . '/' . $name));
        $path = ImageInfo::BASE_PATH . '/' . $target;
        $media->writeFile($path, $bytes);
        $this->fileStorageDatabase->saveFile($path);
        return $target;
    }

    /**
     * Decoded bytes, or null when the data is empty or not strict base64.
     *
     * @param ImageContentInterface $content
     * @return string|null
     */
    private function decode(ImageContentInterface $content): ?string
    {
        // phpcs:ignore Magento2.Functions.DiscouragedFunction -- the web API sends the image as base64
        $bytes = base64_decode((string) $content->getBase64EncodedData(), true);
        return $bytes === false || $bytes === '' ? null : $bytes;
    }

    /**
     * Extension of an allowed image type, read from the bytes.
     *
     * @param string $bytes
     * @return string|null
     */
    private function extension(string $bytes): ?string
    {
        $mimeType = (new \finfo(FILEINFO_MIME_TYPE))->buffer($bytes);
        return self::EXTENSIONS[(string) $mimeType] ?? null;
    }
}
