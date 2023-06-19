<?php

namespace SprintF\Bundle\Admin\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\File;

class FileUploadType extends AbstractType
{
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'constraints' => new File(),
            'mapped' => false,
            'data_class' => null,
        ]);
    }

    public function getParent(): string
    {
        return FileType::class;
    }
}
