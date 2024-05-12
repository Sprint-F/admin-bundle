<?php

namespace SprintF\Bundle\Admin\Field;

use Symfony\Component\DependencyInjection\ParameterBag\ContainerBagInterface;

interface FieldNeedAppParamsInterface
{
    /**
     * Метод, который может вызываться для передачи объекту представления поля сущностей параметров приложения
     */
    public function setAppParams(ContainerBagInterface $appParams);

    /**
     * Метод получения ранее переданных параметров приложения
     */
    public function getAppParams(): ContainerBagInterface;
}
