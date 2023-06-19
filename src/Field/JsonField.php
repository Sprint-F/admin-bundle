<?php

namespace SprintF\Bundle\Admin\Field;

/**
 * Представление поля типа "json".
 */
class JsonField extends EntityField
{
    public function renderAsLabel(object $entity): string
    {
        $value = $this->value($entity);

        return json_encode($value);
    }
}
