<?php

namespace App\Form;

use App\Entity\Language;
use App\Entity\Manga;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\QueryBuilder;
use Gregwar\CaptchaBundle\Type\CaptchaType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ButtonType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\File;
use Symfony\Component\Validator\Constraints\Positive;

class MangaTranslationFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('translationFrom', IntegerType::class, ['label' => 'creator.form.new.form_type.manga_id', 'required' => true])
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
            ->add('price', IntegerType::class, ['label' => 'creator.form.new.form_type.price', 'required' => true, 'empty_data' => 0, 'constraints' => [new Positive()]])
            /*->add('captcha', CaptchaType::class, ['label' => 'login.form.signup.form_type.captcha'])
            ->add('brochure', FileType::class, [
                'label' => 'Brochure (PDF file)',

                // unmapped means that this field is not associated to any entity property
                'mapped' => false,

                // make it optional so you don't have to re-upload the PDF file
                // every time you edit the Product details
                'required' => false,

                // unmapped fields can't define their validation using attributes
                // in the associated entity, so you can use the PHP constraint classes
                'constraints' => [
                    new File([
                        'maxSize' => '1024k',
                        'mimeTypes' => [
                            'application/pdf',
                            'application/x-pdf',
                        ],
                        'mimeTypesMessage' => 'Please upload a valid PDF document',
                    ])
                ],
            ])*/
            ->add('save', SubmitType::class);
    }
}
