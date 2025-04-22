<?php

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class EditUserFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('public_name', TextType::class, ['label' => 'edit_profile.form_type.public_name', 'required' => false])
            ->add('username', TextType::class, ['label' => 'login.form.signup.form_type.username', 'required' => false])
            ->add('email', EmailType::class, ['label' => 'reset_password.form_type.email', 'required' => false]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([]);
    }
}
