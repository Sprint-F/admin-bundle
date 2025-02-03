<?php

namespace SprintF\Bundle\Admin\Field;

use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Translation\TranslatableMessage;

/**
 * Представление поля типа "перечисление".
 */
class EnumField extends EntityField
{
    public const FORM_TYPE = ChoiceType::class;

    public function __construct(
        public string $name,
        public string|TranslatableMessage $label,
        public string $formType = self::FORM_TYPE,
        public array $formOptions = [],
        public bool $primary = false,
    ) {
        parent::__construct($this->name, $this->label, $this->formType, $this->formOptions, $this->primary);
    }

    public function renderAsLabel(object $entity): string
    {
        $value = $this->value($entity);

        return null === $value ? '-' : $value->label();
    }
}
