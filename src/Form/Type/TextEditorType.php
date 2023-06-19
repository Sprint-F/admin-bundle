<?php

namespace SprintF\Bundle\Admin\Form\Type;

use Symfony\Component\DependencyInjection\ParameterBag\ContainerBagInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;

class TextEditorType extends AbstractType
{
    public function __construct(
        private ContainerBagInterface $params
    ) {
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
        ]);
    }

    public function getParent(): string
    {
        return TextareaType::class;
    }

    public function buildView(FormView $view, FormInterface $form, array $options): void
    {
        $view->vars['tinyMceApiKey'] = $this->params->get('app.tinymce.api_key');
    }
}
