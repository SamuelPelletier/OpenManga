<?php

namespace App\Form\Constraint;

use App\Form\Validator\FileExtensionValidator;
use Symfony\Component\Validator\Constraint;

class FileExtension extends Constraint
{
    public string $message = 'Le fichier "{{ filename }}" doit avoir une extension .jpg ou .jpeg.';
    public array $extensions = ['jpg', 'jpeg'];

    public function validatedBy(): string
    {
        return FileExtensionValidator::class;
    }
}
