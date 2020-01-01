<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Command;

use App\Entity\Manga;
use App\Entity\Language;
use App\Entity\Author;
use App\Entity\Tag;
use App\Entity\Parody;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Formatter\OutputFormatterStyle;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Symfony\Component\Console\Style\SymfonyStyle;
use Psr\Log\LoggerInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Filesystem\Exception\IOExceptionInterface;
use Symfony\Component\Finder\Finder;
use WebPConvert\WebPConvert;

/**
 * A console command that lists all the existing users.
 *
 * To use this command, open a terminal window, enter into your project directory
 * and execute the following:
 *
 *     $ php bin/console app:import-manga
 *
 * See https://symfony.com/doc/current/cookbook/console/console_command.html
 * For more advanced uses, commands can be defined as services too. See
 * https://symfony.com/doc/current/console/commands_as_services.html
 *
 * @property EntityManagerInterface entityManager
 */
class ImportMangaFromFolderCommand extends Command
{
    // a good practice is to use the 'app:' prefix to group all your custom application commands
    protected static $defaultName = 'app:import-manga-folder';

    private $em;
    private $logger;

    public function __construct(EntityManagerInterface $em, LoggerInterface $logger)
    {
        parent::__construct();

        $this->em = $em;
        $this->logger = $logger;
    }

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this->setDescription('Import manga from folder')
            ->addOption(
                'example',
                null,
                InputOption::VALUE_NONE,
                'Use example files ?'
            );
    }

    /**
     * This method is executed after initialize(). It usually contains the logic
     * to execute to complete this command task.
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $fileSystem = new Filesystem();
        $exampleOption = $input->getOption('example');
        if ($exampleOption) {
            $fileSystem->mirror(dirname(__DIR__) . '/../public/example/Akame ga kill',
                dirname(__DIR__) . '/../public/media/Akame ga kill');
        }

        $outputStyle = new OutputFormatterStyle('red');
        $output->getFormatter()->setStyle('red', $outputStyle);

        $output->writeln('<red>WARNING ! This command remove and rename files ! Becarefull to follow the right format : images in .jpg in lowercase with only number ! Any else name are removed !</>');
        $helper = $this->getHelper('question');
        $question = new ConfirmationQuestion('Are you sure to continue ? (yes/no)', false, '/^yes$|^y$/');

        if (!$helper->ask($input, $output, $question)) {
            $output->writeln('<red>Abort</>');
            return;
        }

        $finderFolder = new Finder();
        $finderFolder->directories()->in(dirname(__DIR__) . '/../public/media/');
        $repoManga = $this->em->getRepository(Manga::class);

        foreach ($finderFolder as $folder) {
            $folders[] = $folder;
        }

        $progressBar = new ProgressBar($output, count($folders));
        $progressBar->start();

        foreach ($folders as $folder) {
            $manga = $repoManga->findByTitle($folder->getFilename());
            if (count($manga) > 0 || preg_match("/^[0-9]+$/", $folder->getFilename()) == 1) {
                // Already exist
                continue;
            }
            $finderFiles = new Finder();
            $finderFiles->files()->in(dirname(__DIR__) . '/../public/media/' . $folder->getFilename());
            $countPages = 0;
            foreach ($finderFiles as $file) {
                if (preg_match("/^[0-9]{1,3}.jpg$/", $file->getFilename()) == 1) {
                    if (preg_match("/^[0-9]{1,2}.jpg$/", $file->getFilename()) == 1) {
                        $name = explode('.', $file->getFilename())[0];
                        $name = str_pad($name, 3, "0", STR_PAD_LEFT);
                        $fileSystem->rename($file->getRealPath(), $file->getPath() . '/' . $name . '.jpg');
                    }
                    $countPages++;
                    if ($countPages == 1) {
                        $source = $file->getRealPath();
                        $destination = $folder->getRealPath() . '/thumb.webp';
                        try {
                            $success = WebPConvert::convert($source, $destination, [
                                // It is not required that you set any options - all have sensible defaults.
                                // We set some, for the sake of the example.
                                'quality' => 10,
                                'max-quality' => 20,
                                'converters' => ['imagick', 'gmagick', 'gd', 'imagickbinary']
                            ]);
                        } catch (\Exception $e) {
                            $fileSystem->copy($source, $destination);
                        }
                    }
                } else {
                    $fileSystem->remove($file->getRealPath());
                }
            }

            $manga = new Manga();
            $manga->setTitle($folder->getFilename());
            $manga->setCountPages($countPages);
            $manga->setPublishedAt(new \DateTime('NOW'));
            $this->em->persist($manga);
            $this->em->flush();
            $fileSystem->rename($folder->getRealPath(), $folder->getPath() . '/' . $manga->getId());
            $this->logger->info('End of import - manga : ' . $manga->getTitle() . ' ## New ID : ' . $manga->getId());
            $progressBar->advance();
            return 0;
        }

        $progressBar->finish();
    }
}
