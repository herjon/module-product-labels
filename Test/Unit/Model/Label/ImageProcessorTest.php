<?php
declare(strict_types=1);

namespace Majistar\ProductLabels\Test\Unit\Model\Label;

use Majistar\ProductLabels\Model\Label\ImageProcessor;
use Majistar\ProductLabels\Model\Label\ImageStorage;
use Magento\Framework\Exception\LocalizedException;
use PHPUnit\Framework\TestCase;

class ImageProcessorTest extends TestCase
{
    public function testEmptyValueMeansNoImage(): void
    {
        $uploader = $this->createMock(ImageStorage::class);
        $uploader->expects(self::never())->method('moveFromTmp');
        $processor = new ImageProcessor($uploader);

        self::assertNull($processor->process(null));
        self::assertNull($processor->process([]));
        self::assertNull($processor->process(''));
        self::assertNull($processor->process([['url' => 'x']]));
    }

    public function testExistingImageKeepsItsName(): void
    {
        $uploader = $this->createMock(ImageStorage::class);
        $uploader->expects(self::never())->method('moveFromTmp');

        self::assertSame('sale.png', (new ImageProcessor($uploader))->process([['name' => 'sale.png', 'url' => 'u']]));
    }

    public function testFreshUploadIsMovedAndMayBeRenamed(): void
    {
        $uploader = $this->createMock(ImageStorage::class);
        $uploader->expects(self::once())->method('moveFromTmp')
            ->with('sale_1.webp')
            ->willReturn('sale_2.webp');

        self::assertSame(
            'sale_2.webp',
            (new ImageProcessor($uploader))->process([
                ['name' => 'sale_1.webp', 'file' => 'sale_1.webp', 'tmp_name' => '/tmp/php123', 'url' => 'u'],
            ])
        );
    }

    public function testUnsafeNamesAreRejectedAndNeverMoved(): void
    {
        $uploader = $this->createMock(ImageStorage::class);
        $uploader->expects(self::never())->method('moveFromTmp');
        $processor = new ImageProcessor($uploader);

        foreach (['../../app/etc/env.php', 'x.svg', 'shell.php', '.htaccess'] as $name) {
            try {
                $processor->process([['name' => $name, 'tmp_name' => '/tmp/php123']]);
                self::fail($name . ' accepted');
            } catch (LocalizedException) {
                $this->addToAssertionCount(1);
            }
        }
    }
}
