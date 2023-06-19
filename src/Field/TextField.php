<?php

namespace SprintF\Bundle\Admin\Field;

use SprintF\Bundle\Admin\Form\Type\TextEditorType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;

/**
 * Представление поля типа "text".
 */
class TextField extends EntityField
{
    public const FORM_TYPE = TextareaType::class; // @todo: TextEditorType

    protected function getDefaultFormOptions(): array
    {
        return ['attr' => ['rows' => 10]];
    }

    public function renderAsLabel(object $entity): string
    {
        $value = $this->value($entity);

        return mb_substr($value, 0, 100).'...';
    }
}
