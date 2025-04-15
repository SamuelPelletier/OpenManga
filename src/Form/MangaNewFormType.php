<?php

namespace App\Form;

use App\Entity\Language;
use App\Entity\Manga;
use App\Form\Constraint\FileExtension;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\QueryBuilder;
use Gregwar\CaptchaBundle\Type\CaptchaType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ButtonType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\SearchType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\All;
use Symfony\Component\Validator\Constraints\Count;
use Symfony\Component\Validator\Constraints\File;
use Symfony\Component\Validator\Constraints\Image;
use Symfony\Component\Validator\Constraints\Positive;

class MangaNewFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('title', TextType::class, ['label' => 'creator.form.new.form_type.title', 'required' => true])
            ->add('tags', SearchType::class, ['label' => 'creator.form.new.form_type.tags', 'required' => true])
            ->add('parodies', null, ['label' => 'creator.form.new.form_type.parodies', 'required' => false])
            ->add('languages', EntityType::class, ['label' => 'creator.form.new.form_type.languages', 'class' => Language::class,
                'choice_label' => function (Language $language) {
                    return ucfirst(strtolower($language->getName()));
                }, 'multiple' => true,
                'query_builder' => function (EntityRepository $er): QueryBuilder {
                    return $er->createQueryBuilder('l')
                        ->where('l.name in (:language_allow)')
                        ->setParameter('language_allow', array_keys(Language::ISO_CODE))
                        ->orderBy('l.name', 'ASC');
                },])
            ->add('price', IntegerType::class, ['label' => 'creator.form.new.form_type.price', 'required' => true, 'empty_data' => 0, 'min' => 0, 'constraints' => [new Positive()]])

            //->add('captcha', CaptchaType::class, ['label' => 'login.form.signup.form_type.captcha'])
            ->add('files', FileType::class, [
                'label' => 'creator.form.new.form_type.files',
                'mapped' => false,
                'required' => true,
                'multiple' => true,
                'constraints' => [
                    new Count([
                        'max' => 100,
                        'maxMessage' => 'Vous ne pouvez pas envoyer plus de {{ limit }} fichiers.',
                    ]),
                    new All([
                        'constraints' => [
                            new Image([
                                'maxSize' => '250k',
                                'mimeTypes' => [
                                    'image/jpeg',
                                    'image/webp',
                                    'image/png',
                                ],
                                'mimeTypesMessage' => 'creator.form.new.form_type.files.format_error',
                            ]),
                            new FileExtension()
                        ]
                    ])
                ]
            ])
            ->add('save', SubmitType::class);

    }
}
