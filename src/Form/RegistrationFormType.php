<?php

namespace App\Form;

use App\Entity\User;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\IsTrue;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;
use Gregwar\CaptchaBundle\Type\CaptchaType;

class RegistrationFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('username', null, ['label' => 'login.form.signup.form_type.username'])
            //https://github.com/Gregwar/CaptchaBundle
            ->add('captcha', CaptchaType::class, ['label' => 'login.form.signup.form_type.captcha'])
            ->add('plainPassword', RepeatedType::class, [
                // instead of being set onto the object directly,
                // this is read and encoded in the controller
                'type' => PasswordType::class,
                'mapped' => false,
                'first_options' => ['label' => 'login.form.signup.form_type.first_password'],
                'second_options' => ['label' => 'login.form.signup.form_type.second_password'],
                'invalid_message' => 'login.form.signup.form_type.password.must_match',
                'constraints' => [
                    new NotBlank([
                        'message' => 'login.form.signup.form_type.password.not_blank',
                    ]),
                    new Length([
                        'min' => 6,
                        'minMessage' => 'login.form.signup.form_type.password.length',
                        // max length allowed by Symfony for security reasons
                        'max' => 4096,
                    ]),
                ],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => User::class,
        ]);
    }
}
