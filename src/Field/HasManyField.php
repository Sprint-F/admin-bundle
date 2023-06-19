<?php

namespace SprintF\Bundle\Admin\Field;

/**
 * Представление поля типа "много связанных сущностей".
 */
class HasManyField extends EntityField
{
    public function renderAsLabel(object $entity): string
    {
        return implode(', ', $this->value($entity)->toArray());
    }
}
