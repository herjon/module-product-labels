<?php
declare(strict_types=1);

namespace Majistar\ProductLabels\Test\Unit\Model;

use Majistar\ProductLabels\Model\Config;
use Magento\Framework\App\Config\ScopeConfigInterface;
use PHPUnit\Framework\TestCase;

class ConfigTest extends TestCase
{
    /**
     * @param array<string, string> $values
     */
    private function config(array $values): Config
    {
        $scopeConfig = $this->createStub(ScopeConfigInterface::class);
        $scopeConfig->method('getValue')->willReturnCallback(
            static fn(string $path) => $values[$path] ?? null
        );
        $scopeConfig->method('isSetFlag')->willReturnCallback(
            static fn(string $path): bool => (bool) ($values[$path] ?? false)
        );
        return new Config($scopeConfig);
    }

    public function testFlags(): void
    {
        $config = $this->config([Config::XML_PATH_ENABLED => '1', Config::XML_PATH_OUT_OF_STOCK_ONLY => '0']);

        self::assertTrue($config->isEnabled());
        self::assertFalse($config->isOutOfStockOnly());
    }

    public function testMaxLabelsFallsBackToTwoWhenNotAPositiveNumber(): void
    {
        self::assertSame(3, $this->config([Config::XML_PATH_MAX_LABELS => '3'])->getMaxLabels());
        self::assertSame(2, $this->config([Config::XML_PATH_MAX_LABELS => '0'])->getMaxLabels());
        self::assertSame(2, $this->config([Config::XML_PATH_MAX_LABELS => 'abc'])->getMaxLabels());
        self::assertSame(2, $this->config([])->getMaxLabels());
    }

    public function testNewSourceAcceptsOnlyKnownValues(): void
    {
        self::assertSame(
            Config::NEW_SOURCE_CREATED_DAYS,
            $this->config([Config::XML_PATH_NEW_SOURCE => 'created_days'])->getNewSource()
        );
        self::assertSame(
            Config::NEW_SOURCE_NEWS_DATES,
            $this->config([Config::XML_PATH_NEW_SOURCE => 'something'])->getNewSource()
        );
    }

    public function testNewDaysFallsBackToThirty(): void
    {
        self::assertSame(14, $this->config([Config::XML_PATH_NEW_DAYS => '14'])->getNewDays());
        self::assertSame(30, $this->config([Config::XML_PATH_NEW_DAYS => '-5'])->getNewDays());
    }
}
