<?php

namespace SprintF\Bundle\Admin\Field;

use SprintF\Bundle\Admin\Form\Type\FileUploadType;
use Symfony\Component\Translation\TranslatableMessage;

/**
 * Представление поля типа "Загружаемый файл".
 */
class FileField extends EntityField
{
    public const FORM_TYPE = FileUploadType::class;

    public function __construct(
        public string $name,
        public string|TranslatableMessage $label,
        public string $formType = self::FORM_TYPE,
        public array $formOptions = [],
        public bool $primary = false,
        public string $uploadPath = 'uploads',
    ) {
        parent::__construct($this->name, $this->label, $this->formType, $this->formOptions, $this->primary);
    }

    protected function getDefaultFormOptions(): array
    {
        return ['required' => false];
    }

    public function renderAsLabel(object $entity): string
    {
        $value = $this->value($entity);

        return null === $value ? '-' : $value;
    }
}
