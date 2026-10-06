<?php
declare(strict_types=1);

namespace Majistar\ProductLabels\Test\Unit\Observer;

use Majistar\ProductLabels\Api\ProductLabelAssignmentInterface;
use Majistar\ProductLabels\Observer\SaveProductAssignments;
use Magento\Backend\App\Action;
use Magento\Catalog\Model\Product;
use Magento\Framework\App\Request\Http;
use Magento\Framework\AuthorizationInterface;
use Magento\Framework\Event;
use Magento\Framework\Event\Observer;
use Magento\Framework\Message\ManagerInterface;
use PHPUnit\Framework\TestCase;

class SaveProductAssignmentsTest extends TestCase
{
    private function observer(array $post): Observer
    {
        $request = $this->createStub(Http::class);
        $request->method('getPost')->willReturnCallback(static fn(string $name) => $name === 'product' ? $post : null);
        $controller = $this->createStub(Action::class);
        $controller->method('getRequest')->willReturn($request);
        $product = $this->createStub(Product::class);
        $product->method('getId')->willReturn(10);

        return new Observer(['event' => new Event(['controller' => $controller, 'product' => $product])]);
    }

    private function subject(ProductLabelAssignmentInterface $assignment, bool $allowed = true): SaveProductAssignments
    {
        $authorization = $this->createStub(AuthorizationInterface::class);
        $authorization->method('isAllowed')->willReturn($allowed);
        return new SaveProductAssignments($assignment, $authorization, $this->createStub(ManagerInterface::class));
    }

    public function testSelectedLabelsAreSaved(): void
    {
        $assignment = $this->createMock(ProductLabelAssignmentInterface::class);
        $assignment->expects(self::once())->method('setLabelIds')->with(10, [2, 7]);

        $this->subject($assignment)->execute($this->observer([
            'majistar_product_labels_submitted' => '1',
            'majistar_product_labels' => ['2', '7'],
        ]));
    }

    public function testDeselectingEverythingClearsTheAssignments(): void
    {
        $assignment = $this->createMock(ProductLabelAssignmentInterface::class);
        $assignment->expects(self::once())->method('setLabelIds')->with(10, []);

        $this->subject($assignment)->execute($this->observer(['majistar_product_labels_submitted' => '1']));
    }

    public function testFormWithoutTheFieldsetLeavesAssignmentsAlone(): void
    {
        $assignment = $this->createMock(ProductLabelAssignmentInterface::class);
        $assignment->expects(self::never())->method('setLabelIds');

        $this->subject($assignment)->execute($this->observer(['name' => 'Bag']));
    }

    public function testAdminWithoutPermissionCannotChangeAssignments(): void
    {
        $assignment = $this->createMock(ProductLabelAssignmentInterface::class);
        $assignment->expects(self::never())->method('setLabelIds');

        $this->subject($assignment, false)->execute($this->observer([
            'majistar_product_labels_submitted' => '1',
            'majistar_product_labels' => ['2'],
        ]));
    }
}
