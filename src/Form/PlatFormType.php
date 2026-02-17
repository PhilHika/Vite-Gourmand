<?php

namespace App\Form;

use App\Entity\Allergene;
use App\Entity\Menu;
use App\Entity\Plat;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\File;

class PlatFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('titrePlat', TextType::class, [
                'label' => 'Titre du plat',
                'attr' => ['class' => 'form-control', 'maxlength' => 50]
            ])
            ->add('photoFile', FileType::class, [
                'label' => 'Photo du plat (fichier image)',
                'mapped' => false,
                'required' => false,
                'attr' => ['class' => 'form-control'],
                'constraints' => [
                    new File([
                        'maxSize' => '2048k',
                        'mimeTypes' => [
                            'image/jpeg',
                            'image/png',
                            'image/webp',
                        ],
                        'mimeTypesMessage' => 'Veuillez télécharger une image valide (JPEG, PNG, WEBP)',
                    ])
                ],
            ])
            ->add('deletePhoto', CheckboxType::class, [
                'label' => 'Supprimer la photo actuelle ?',
                'mapped' => false,
                'required' => false,
                'attr' => ['class' => 'form-check-input'],
                'label_attr' => ['class' => 'form-check-label'],
            ])
            ->add('allergenes', EntityType::class, [
                'class' => Allergene::class,
                'choice_label' => 'libelle',
                'label' => 'Allergènes',
                'multiple' => true,
                'required' => false,
                'attr' => ['class' => 'form-select', 'size' => 5],
                'by_reference' => false,
            ])
            ->add('menus', EntityType::class, [
                'class' => Menu::class,
                'choice_label' => 'titre',
                'label' => 'Menus associés',
                'multiple' => true,
                'required' => false,
                'attr' => ['class' => 'form-select', 'size' => 5],
                'by_reference' => false,
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Plat::class,
        ]);
    }
}
