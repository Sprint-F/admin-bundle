<?php

namespace SprintF\Bundle\Admin\Button;

use SprintF\Bundle\Workflow\Entity\WorkflowEntityInterface;

/**
 * Кнопка какого-либо действия, отображающаяся рядом с сущностью.
 */
class EntityButton
{
    public function __construct(
        /** Надпись на кнопке */
        public string $label,
        /** URL, куда отправит нас кнопка */
        public string $url,
        /** HTML, содержащий иконку для кнопки. Необязательно. */
        public ?string $icon = null,
        /** JS на кнопку */
        public ?string $onclick = null,
    ) {
    }

    public function visible(WorkflowEntityInterface $entity): bool
    {
        return true;
    }
}
