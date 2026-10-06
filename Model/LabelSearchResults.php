<?php
declare(strict_types=1);

namespace Majistar\ProductLabels\Model;

use Majistar\ProductLabels\Api\Data\LabelSearchResultsInterface;
use Magento\Framework\Api\SearchResults;

class LabelSearchResults extends SearchResults implements LabelSearchResultsInterface
{
}
