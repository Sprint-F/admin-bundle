<?php

namespace SprintF\Bundle\Admin\Attribute;

use Symfony\Component\Translation\TranslatableMessage;

/**
 * Атрибут, позволяющий задавать наименования сущностям в админ-панели.
 */
#[\Attribute(\Attribute::TARGET_CLASS)]
class EntityLabel
{
    public function __construct(
        /** Наименование в единственном числе */
        protected string $single,
        /** Наименование в множественном числе */
        protected string $plural,
        /** Родительный падеж единственного числа, например "региона" */
        protected ?string $singleGen = null,
        /** Винительный падеж единственного числа, например "регион" */
        protected ?string $singleAcc = null,
    ) {
        if (null === $this->singleAcc) {
            $this->singleAcc = $this->single;
        }
    }

    /**
     * Возвращает наименование в единственном числе с учетом перевода.
     */
    public function getSingle(): TranslatableMessage
    {
        return new TranslatableMessage($this->single, [], 'admin.labels');
    }

    /**
     * Возвращает наименование в множественном числе с учетом перевода.
     */
    public function getPlural(): TranslatableMessage
    {
        return new TranslatableMessage($this->plural, [], 'admin.labels');
    }

    /**
     * Возвращает наименование в единственном числе в родительном падеже с учетом перевода.
     */
    public function getSingleGen(): TranslatableMessage
    {
        return new TranslatableMessage($this->singleGen, [], 'admin.labels');
    }

    /**
     * Возвращает наименование в единственном числе в винительном падеже с учетом перевода.
     */
    public function getSingleAcc(): TranslatableMessage
    {
        return new TranslatableMessage($this->singleAcc, [], 'admin.labels');
    }
}
