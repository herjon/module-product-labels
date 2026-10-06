<?php
declare(strict_types=1);

namespace Majistar\ProductLabels\Test\Unit\Model\Label;

use Majistar\ProductLabels\Model\Label\UploadedFileLocator;
use PHPUnit\Framework\TestCase;

class UploadedFileLocatorTest extends TestCase
{
    private const FILE = ['name' => 'a.png', 'tmp_name' => '/tmp/x', 'size' => 1, 'error' => 0, 'type' => 'image/png'];

    public function testFlatFieldName(): void
    {
        self::assertSame(self::FILE, (new UploadedFileLocator())->locate(['image' => self::FILE], 'image'));
    }

    public function testBracketedFieldNameOfTheLabelForm(): void
    {
        $files = ['listing_appearance' => ['image' => self::FILE]];

        self::assertSame(self::FILE, (new UploadedFileLocator())->locate($files, 'listing_appearance[image]'));
    }

    public function testMissingFileGivesAnEmptyArray(): void
    {
        $locator = new UploadedFileLocator();

        self::assertSame([], $locator->locate([], 'image'));
        self::assertSame([], $locator->locate(['product_appearance' => []], 'product_appearance[image]'));
        self::assertSame([], $locator->locate(['image' => 'not an array'], 'image'));
    }
}
