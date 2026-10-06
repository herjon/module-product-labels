<?php
declare(strict_types=1);

namespace Majistar\ProductLabels\Test\Unit\Observer;

use Majistar\ProductLabels\Model\Index\IndexBuilder;
use Majistar\ProductLabels\Observer\ReindexLabel;
use Majistar\ProductLabels\Test\Unit\Fixture\CreatesLabels;
use Magento\Framework\App\CacheInterface;
use Magento\Framework\Event;
use Magento\Framework\Event\ManagerInterface;
use Magento\Framework\Event\Observer;
use PHPUnit\Framework\TestCase;

class ReindexLabelTest extends TestCase
{
    use CreatesLabels;

    public function testCachesAreCleanedAfterTheRowsAreRebuilt(): void
    {
        $calls = [];
        $label = $this->createLabel(['label_id' => 5]);
        $indexBuilder = $this->createStub(IndexBuilder::class);
        $indexBuilder->method('reindexLabel')->willReturnCallback(function (int $labelId) use (&$calls): void {
            $calls[] = 'reindex ' . $labelId;
        });
        $cache = $this->createStub(CacheInterface::class);
        $cache->method('clean')->willReturnCallback(function (array $tags) use (&$calls): bool {
            $calls[] = 'clean ' . implode(' ', $tags);
            return true;
        });
        $eventManager = $this->createStub(ManagerInterface::class);
        $eventManager->method('dispatch')->willReturnCallback(
            function (string $name, array $data) use (&$calls, $label): void {
                $calls[] = $name . ($data['object'] === $label ? ' of the label' : '');
            }
        );

        (new ReindexLabel($indexBuilder, $cache, $eventManager))->execute(
            new Observer(['event' => new Event(['label' => $label])])
        );

        self::assertSame(
            ['reindex 5', 'clean majistar_product_label majistar_product_label_5', 'clean_cache_by_tags of the label'],
            $calls
        );
    }
}
