<?php
declare(strict_types=1);

namespace Majistar\ProductLabels\Test\Unit\Model\Label;

use Majistar\ProductLabels\Model\Label\ImageFileValidator;
use Magento\Framework\Exception\LocalizedException;
use PHPUnit\Framework\TestCase;

class ImageFileValidatorTest extends TestCase
{
    /**
     * @var string
     */
    private string $dir;

    protected function setUp(): void
    {
        $this->dir = sys_get_temp_dir() . '/majistar_labels_' . uniqid('', true);
        mkdir($this->dir);
    }

    protected function tearDown(): void
    {
        array_map('unlink', glob($this->dir . '/*') ?: []);
        rmdir($this->dir);
    }

    private function image(string $format): string
    {
        $path = $this->dir . '/source.' . $format;
        $image = imagecreatetruecolor(4, 4);
        match ($format) {
            'png' => imagepng($image, $path),
            'jpg' => imagejpeg($image, $path),
            'gif' => imagegif($image, $path),
            'webp' => imagewebp($image, $path),
        };
        return $path;
    }

    /**
     * @return array{name: string, tmp_name: string, size: int, error: int}
     */
    private function upload(string $name, string $path, ?int $size = null): array
    {
        return ['name' => $name, 'tmp_name' => $path, 'size' => $size ?? (int) filesize($path), 'error' => 0];
    }

    public function testAcceptsCommonFormats(): void
    {
        $validator = new ImageFileValidator();
        foreach (['png', 'jpg', 'gif', 'webp'] as $format) {
            $validator->validate($this->upload('label.' . strtoupper($format), $this->image($format)));
        }
        $validator->validate($this->upload('label.jpeg', $this->image('jpg')));
        $this->addToAssertionCount(1);
    }

    public function testRejectsFilesLargerThanTwoMegabytes(): void
    {
        $this->expectException(LocalizedException::class);
        $this->expectExceptionMessage('2 MB');
        (new ImageFileValidator())->validate($this->upload('big.png', $this->image('png'), 2097153));
    }

    public function testRejectsSvgAndOtherExtensions(): void
    {
        foreach (['label.svg', 'label.php', 'label'] as $name) {
            try {
                (new ImageFileValidator())->validate($this->upload($name, $this->image('png')));
                self::fail($name . ' accepted');
            } catch (LocalizedException $e) {
                self::assertStringContainsString('JPG, PNG, GIF, WebP or AVIF', $e->getMessage());
            }
        }
    }

    public function testRejectsAFakeImage(): void
    {
        $path = $this->dir . '/fake.png';
        file_put_contents($path, '<?php echo "not an image";');

        $this->expectException(LocalizedException::class);
        $this->expectExceptionMessage('not a valid image');
        (new ImageFileValidator())->validate($this->upload('fake.png', $path));
    }

    public function testRejectsFailedUploads(): void
    {
        $this->expectException(LocalizedException::class);
        (new ImageFileValidator())->validate(['name' => 'a.png', 'tmp_name' => '', 'size' => 0, 'error' => 4]);
    }
}
