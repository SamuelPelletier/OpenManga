<?php
// src/MessageHandler/ImageTranslationHandler.php
namespace App\MessageHandler;

use App\Message\ImageTranslation;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Process\Process;
use Monolog\Logger;
use Monolog\Handler\StreamHandler;

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

        // Create a Monolog logger
        $log = new Logger('trad');
        $log->pushHandler(new StreamHandler('C:\Users\Skull\Documents\github\OpenManga\trad.log', Logger::DEBUG));

        $inputFolderPath = $message->getInputFolderPath();
        $inputLanguage = $message->getInputLanguage();
        $outputLanguage = $message->getOutputLanguage();
        $outputFolderPath = $message->getOutputFolderPath();
        $transparency = $message->getTransparency();

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

        // Set up process event listeners to capture output and errors
        $process->start();
        $process->wait(function ($type, $output) use ($log) {
            if (Process::ERR === $type) {
                $log->error($output);
            } else {
                $log->info($output);
            }
        });

        // Check if the process was successful and handle accordingly
        if (!$process->isSuccessful()) {
            // Handle errors if needed
        }

        return $process->isSuccessful();
    }
}