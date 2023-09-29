<?php
// src/MessageHandler/ImageTranslationHandler.php
namespace App\MessageHandler;

use App\Message\ImageTranslation;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Process\Process;

#[AsMessageHandler]
class ImageTranslationHandler
{
    private MessageBusInterface $messageBus;

    public function __construct(MessageBusInterface $messageBus)
    {
        $this->messageBus = $messageBus;
    }

    public function __invoke(ImageTranslation $message)
    {
        $inputFolderPath = $message->getInputFolderPath();
        $inputLanguage = $message->getInputLanguage();
        $outputLanguage = $message->getOutputLanguage();
        $outputFolderPath = $message->getOutputFolderPath();
        $transparency = $message->getTransparency();

        // Get a list of image files in the input folder
        //$imageFiles = scandir($inputFolderPath);

        //$totalImages = count($imageFiles);
        //$currentProgress = 0;

        //foreach ($imageFiles as $imageFile) {
            //if (pathinfo($imageFile, PATHINFO_EXTENSION) === 'jpg') {
                // Construct the full image file path
                //$imageFilePath = $inputFolderPath . $imageFile;

                // Build the Python script command with the updated arguments
        $command = [
            'C:/Python310/python.exe',
            'C:\Users\Skull\Documents\github\Open-Translation\trad.py',
            '-i',
            $inputLanguage,
            '-o',
            $outputLanguage,
            '-if',
            $inputFolderPath,
            '-of',
            $outputFolderPath,
            '-t',
            $transparency,
        ];

        // Create a new Process instance
        $process = new Process($command);

        // Start the process
        $process->start();

        // Execute the Python script
        $process->setTimeout(6800);
        /*
        $process->run(function ($type, $buffer) use ($totalImages) {
            if (Process::ERR === $type) {
                echo 'ERR > '.$buffer;
            } else {
                echo 'OUT > '.$buffer;
            }
            // Update progress before processing the image
            //$currentProgress++;
            //$progressPercentage = ($currentProgress / $totalImages) * 100;

            // Send progress update as a message to the message bus
            //$this->messageBus->dispatch(new ProgressUpdate($progressPercentage));
        });
        */

        // Wait for the process to finish
        //$process->wait();

        // Check if the process was successful and handle accordingly
        if (!$process->isSuccessful()) {
            // Handle errors if needed
        }
        return $process->isSuccessful();
    }
}
