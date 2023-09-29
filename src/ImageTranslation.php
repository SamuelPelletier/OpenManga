<?php
// src/Message/ImageTranslation.php
namespace App\Message;

class ImageTranslation
{
    private string $inputLanguage;
    private string $outputLanguage;


    private string $inputFolderPath;
    private string $outputFolderPath;
    private int $transparency;

    public function __construct(
        string $inputLanguage,
        string $outputLanguage,
        string $inputFolderPath,
        string $outputFolderPath,
        int $transparency
    ) {
        $this->inputLanguage = $inputLanguage;
        $this->outputLanguage = $outputLanguage;
        $this->inputFolderPath = $inputFolderPath;
        $this->outputFolderPath = $outputFolderPath;
        $this->transparency = $transparency;
    }

    public function getInputFolderPath(): string
    {
        return $this->inputFolderPath;
    }

    public function getInputLanguage(): string
    {
        return $this->inputLanguage;
    }

    public function getOutputLanguage(): string
    {
        return $this->outputLanguage;
    }

    public function getOutputFolderPath(): string
    {
        return $this->outputFolderPath;
    }

    public function getTransparency(): int
    {
        return $this->transparency;
    }
}