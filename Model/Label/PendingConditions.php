<?php
declare(strict_types=1);

namespace Majistar\ProductLabels\Model\Label;

use Majistar\ProductLabels\Model\Label;
use Magento\Framework\App\Request\DataPersistorInterface;

/**
 * Keeps the conditions posted by a failed save, so the condition builder shows them again (read once).
 */
class PendingConditions
{
    public const PERSISTOR_KEY = 'majistar_product_label_conditions';

    /**
     * @param DataPersistorInterface $dataPersistor
     */
    public function __construct(
        private readonly DataPersistorInterface $dataPersistor
    ) {
    }

    /**
     * Remembers the conditions of a POST of the label form.
     *
     * @param array $post
     * @return void
     */
    public function remember(array $post): void
    {
        $conditions = $post['rule']['conditions'] ?? null;
        if (!is_array($conditions)) {
            $this->dataPersistor->clear(self::PERSISTOR_KEY);
            return;
        }
        $this->dataPersistor->set(self::PERSISTOR_KEY, [
            'label_id' => (int) ($post['label_id'] ?? 0),
            'conditions' => $conditions,
        ]);
    }

    /**
     * Applies the remembered conditions when they belong to this label, then forgets them.
     *
     * @param Label $label
     * @return void
     */
    public function restore(Label $label): void
    {
        $pending = $this->dataPersistor->get(self::PERSISTOR_KEY);
        $this->dataPersistor->clear(self::PERSISTOR_KEY);
        if (!is_array($pending) || !is_array($pending['conditions'] ?? null)) {
            return;
        }
        if ((int) ($pending['label_id'] ?? 0) !== (int) $label->getLabelId()) {
            return;
        }
        $label->loadPost(['conditions' => $pending['conditions']]);
    }
}
