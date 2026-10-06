<?php
declare(strict_types=1);

namespace Majistar\ProductLabels\Test\Unit\Model\Label;

use Majistar\ProductLabels\Model\Label\ImageContentStorage;
use Majistar\ProductLabels\Model\Label\ImageStorage;
use Magento\Framework\Api\ImageContent;
use Magento\Framework\File\Name;
use Magento\Framework\Filesystem;
use Magento\Framework\Filesystem\Directory\WriteInterface;
use Magento\MediaStorage\Helper\File\Storage\Database;
use Magento\MediaStorage\Model\File\UploaderFactory;
use Magento\Store\Model\StoreManagerInterface;
use PHPUnit\Framework\TestCase;

class ImageContentStorageTest extends TestCase
{
    private const PNG = 'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNkYAAAAAYAAjCB0C8AAAAASUVORK5CYII=';
    private const GIF = 'R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7';

    private function content(string $base64, string $name = 'badge.png'): ImageContent
    {
        return new ImageContent([
            ImageContent::BASE64_ENCODED_DATA => $base64,
            ImageContent::TYPE => 'image/png',
            ImageContent::NAME => $name,
        ]);
    }

    private function storage(?WriteInterface $media = null, ?Name $fileName = null): ImageContentStorage
    {
        $filesystem = $this->createStub(Filesystem::class);
        $filesystem->method('getDirectoryWrite')->willReturn($media ?? $this->createStub(WriteInterface::class));
        $imageStorage = new ImageStorage(
            $this->createStub(UploaderFactory::class),
            $filesystem,
            $fileName ?? $this->createStub(Name::class),
            $this->createStub(Database::class),
            $this->createStub(StoreManagerInterface::class)
        );
        return new ImageContentStorage(
            $filesystem,
            $fileName ?? $this->createStub(Name::class),
            $this->createStub(Database::class),
            $imageStorage
        );
    }

    /**
     * @return string[]
     */
    private function errors(ImageContent $content): array
    {
        return array_map('strval', $this->storage()->validate($content));
    }

    public function testRealImagesAreAccepted(): void
    {
        self::assertSame([], $this->errors($this->content(self::PNG)));
        self::assertSame([], $this->errors($this->content(self::GIF, 'badge.gif')));
    }

    public function testDataThatIsNotBase64IsRejected(): void
    {
        self::assertStringContainsString('base64', $this->errors($this->content('%%% not base64 %%%'))[0]);
        self::assertStringContainsString('base64', $this->errors($this->content(''))[0]);
    }

    public function testFilesThatAreNotAllowedImagesAreRejected(): void
    {
        $svg = base64_encode('<svg xmlns="http://www.w3.org/2000/svg"><script>alert(1)</script></svg>');
        self::assertStringContainsString(
            'JPG, PNG, GIF, WebP or AVIF',
            $this->errors($this->content($svg, 'x.svg'))[0]
        );
        $text = base64_encode('just text');
        self::assertStringContainsString('JPG, PNG, GIF, WebP or AVIF', $this->errors($this->content($text))[0]);
    }

    public function testImagesOverTwoMegabytesAreRejected(): void
    {
        $tooBig = base64_encode(base64_decode(self::PNG) . str_repeat("\0", 2 * 1024 * 1024));

        self::assertStringContainsString('2 MB', $this->errors($this->content($tooBig))[0]);
    }

    public function testSavedFileGetsASafeUniqueNameWithTheExtensionOfItsRealType(): void
    {
        $media = $this->createMock(WriteInterface::class);
        $media->method('getAbsolutePath')
            ->willReturnCallback(static fn(string $path = ''): string => '/media/' . $path);
        $media->expects(self::once())->method('writeFile')
            ->with('majistar_product_labels/Cafe_Sale_1.png', base64_decode(self::PNG));
        $fileName = $this->createMock(Name::class);
        $fileName->expects(self::once())->method('getNewFileName')
            ->with('/media/majistar_product_labels/Cafe_Sale.png')
            ->willReturn('Cafe_Sale_1.png');

        $stored = $this->storage($media, $fileName)->save($this->content(self::PNG, 'Café Sale.jpeg'));

        self::assertSame('Cafe_Sale_1.png', $stored);
    }
}
