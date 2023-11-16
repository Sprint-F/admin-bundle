<?php

namespace SprintF\Bundle\Admin\Field;

use SprintF\Bundle\Admin\Form\Type\TextEditorType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;

/**
 * Представление поля типа "text".
 */
class TextField extends EntityField
{
    public const FORM_TYPE = TextEditorType::class;

    public function renderAsLabel(object $entity): string
    {
        $value = $this->value($entity);

        return mb_substr($value, 0, 100).'...';
    }
}
