<?php

namespace SprintF\Bundle\Admin\Field;

use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Translation\TranslatableMessage;

/**
 * Представление группы полей вложенной сущности.
 */
class EmbeddedFields extends EntityField
{
    public const FORM_TYPE = FormType::class;

    public function __construct(
        public string $name,
        public string|TranslatableMessage $label,
        public string $entityClass,
        public array $fields,
        public string $formType = self::FORM_TYPE,
        public array $formOptions = [],
    ) {
        parent::__construct($this->name, $this->label, $this->formType, $this->formOptions, false);
    }
}
