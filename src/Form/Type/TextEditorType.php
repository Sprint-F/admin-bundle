<?php

namespace SprintF\Bundle\Admin\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\OptionsResolver\OptionsResolver;

class TextEditorType extends AbstractType
{
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefault('attr', ['class' => 'editor']);
    }

    public function getParent(): string
    {
        return TextareaType::class;
    }
}
