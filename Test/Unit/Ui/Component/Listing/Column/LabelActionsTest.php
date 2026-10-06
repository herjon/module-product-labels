<?php
declare(strict_types=1);

namespace Majistar\ProductLabels\Test\Unit\Ui\Component\Listing\Column;

use Majistar\ProductLabels\Ui\Component\Listing\Column\LabelActions;
use Magento\Framework\Escaper;
use Magento\Framework\UrlInterface;
use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Framework\View\Element\UiComponentFactory;
use PHPUnit\Framework\TestCase;

class LabelActionsTest extends TestCase
{
    public function testEditAndDeleteLinksWithEscapedName(): void
    {
        $url = $this->createStub(UrlInterface::class);
        $url->method('getUrl')->willReturnCallback(
            static fn(string $route, array $params): string => $route . '/id/' . $params['id']
        );
        $escaper = $this->createStub(Escaper::class);
        $escaper->method('escapeHtml')->willReturnCallback(
            static fn($value): string => htmlspecialchars((string) $value, ENT_QUOTES)
        );

        $column = new LabelActions(
            $this->createStub(ContextInterface::class),
            $this->createStub(UiComponentFactory::class),
            $url,
            $escaper,
            [],
            ['name' => 'actions']
        );

        $result = $column->prepareDataSource([
            'data' => ['items' => [['label_id' => '5', 'name' => '<b>Sale</b>'], ['name' => 'no id']]],
        ]);
        $actions = $result['data']['items'][0]['actions'];

        self::assertSame('majistar_productlabels/label/edit/id/5', $actions['edit']['href']);
        self::assertSame('majistar_productlabels/label/delete/id/5', $actions['delete']['href']);
        self::assertTrue($actions['delete']['post']);
        self::assertStringContainsString('&lt;b&gt;Sale&lt;/b&gt;', (string) $actions['delete']['confirm']['message']);
        self::assertArrayNotHasKey('actions', $result['data']['items'][1]);
    }
}
