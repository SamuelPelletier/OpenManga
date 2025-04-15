<?php

namespace App\Form\Validator;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class FileExtensionValidator extends ConstraintValidator
{
    public function validate($value, Constraint $constraint)
    {
        if (!$value instanceof UploadedFile) {
            return;
        }

        $extension = strtolower($value->getClientOriginalExtension());

        if (!in_array($extension, $constraint->extensions)) {
            $this->context->buildViolation($constraint->message)
                ->setParameter('{{ filename }}', $value->getClientOriginalName())
                ->addViolation();
        }
    }
}
