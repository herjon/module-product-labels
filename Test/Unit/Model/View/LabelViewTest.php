<?php
declare(strict_types=1);

namespace Majistar\ProductLabels\Test\Unit\Model\View;

use Majistar\ProductLabels\Api\Data\LabelViewInterface;
use Majistar\ProductLabels\Model\View\LabelView;
use PHPUnit\Framework\TestCase;

class LabelViewTest extends TestCase
{
    public function testExposesEveryField(): void
    {
        $view = new LabelView(7, 2, 'text', '-20%', null, null, 'pill', 's', '#e11d48', '#ffffff', 25, 'top-right');

        self::assertInstanceOf(LabelViewInterface::class, $view);
        self::assertSame(7, $view->getLabelId());
        self::assertSame(2, $view->getPriority());
        self::assertSame('text', $view->getType());
        self::assertSame('-20%', $view->getText());
        self::assertNull($view->getImageUrl());
        self::assertNull($view->getImageAlt());
        self::assertSame('pill', $view->getShape());
        self::assertSame('s', $view->getTextSize());
        self::assertSame('#e11d48', $view->getBackgroundColor());
        self::assertSame('#ffffff', $view->getTextColor());
        self::assertSame(25, $view->getWidthPercent());
        self::assertSame('top-right', $view->getPosition());
    }
}
