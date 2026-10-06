<?php
declare(strict_types=1);

namespace Majistar\ProductLabels\Test\Unit\Model\Label;

use Majistar\ProductLabels\Model\Label\ImageStorage;
use Majistar\ProductLabels\Model\Label\Validator;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\File\Name;
use Magento\Framework\Filesystem;
use Magento\Framework\Filesystem\Directory\WriteInterface;
use Magento\MediaStorage\Helper\File\Storage\Database;
use Magento\MediaStorage\Model\File\UploaderFactory;
use Magento\Store\Model\StoreManagerInterface;
use PHPUnit\Framework\TestCase;

class ImageStorageTest extends TestCase
{
    private function storage(WriteInterface $media, Name $fileName): ImageStorage
    {
        $filesystem = $this->createStub(Filesystem::class);
        $filesystem->method('getDirectoryWrite')->willReturn($media);
        return new ImageStorage(
            $this->createStub(UploaderFactory::class),
            $filesystem,
            $fileName,
            $this->createStub(Database::class),
            $this->createStub(StoreManagerInterface::class)
        );
    }

    public function testMovesTheUploadOutOfTmpUnderAUniqueName(): void
    {
        $media = $this->createMock(WriteInterface::class);
        $media->method('isFile')->with('majistar_product_labels/tmp/sale.png')->willReturn(true);
        $media->method('getAbsolutePath')->willReturnCallback(static fn(string $path): string => '/media/' . $path);
        $media->expects(self::once())->method('renameFile')
            ->with('majistar_product_labels/tmp/sale.png', 'majistar_product_labels/sale_1.png');
        $fileName = $this->createStub(Name::class);
        $fileName->method('getNewFileName')->willReturnMap([['/media/majistar_product_labels/sale.png', 'sale_1.png']]);

        self::assertSame('sale_1.png', $this->storage($media, $fileName)->moveFromTmp('sale.png'));
    }

    public function testMissingTmpFileIsReported(): void
    {
        $media = $this->createMock(WriteInterface::class);
        $media->method('isFile')->willReturn(false);
        $media->expects(self::never())->method('renameFile');

        $this->expectException(LocalizedException::class);
        $this->expectExceptionMessage('upload it again');
        $this->storage($media, $this->createStub(Name::class))->moveFromTmp('gone.png');
    }

    public function testUploadNamesAreMadeSafeForSaving(): void
    {
        $storage = $this->storage($this->createStub(WriteInterface::class), $this->createStub(Name::class));
        $cases = [
            'Été.png' => 'Ete.png',
            '-badge.png' => 'badge.png',
            '_badge.PNG' => 'badge.png',
            'Étiquette Noël.webp' => 'Etiquette_Noel.webp',
            'my badge.jpeg' => 'my_badge.jpeg',
            '....gif' => 'label.gif',
            '日本.avif' => 'label.avif',
        ];
        foreach ($cases as $original => $expected) {
            $safe = $storage->safeFileName($original);
            self::assertSame($expected, $safe, $original);
            self::assertMatchesRegularExpression(Validator::IMAGE_NAME_PATTERN, $safe, $original);
        }
    }
}
