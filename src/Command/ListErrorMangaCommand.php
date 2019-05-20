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
use App\Repository\MangaRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\Console\Output\OutputInterface;
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
class ListErrorMangaCommand extends Command
{
    // a good practice is to use the 'app:' prefix to group all your custom application commands
    protected static $defaultName = 'app:check-manga';

    private $mangaRepository;
    private $logger;

    public function __construct(LoggerInterface $logger, MangaRepository $mangaRepository)
    {
        parent::__construct();
        $this->mangaRepository = $mangaRepository;
        $this->logger = $logger;
    }

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this->setDescription('Check every manga can be in error and list them');
    }

    /**
     * This method is executed after initialize(). It usually contains the logic
     * to execute to complete this command task.
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $mangas = $this->mangaRepository->findAll();
        foreach ($mangas as $manga) {
            $finder = new Finder();
            $finder->files()->in(dirname(__DIR__) . '/../public/media/' . $manga->getId());
            if ($manga->getCountPages() != count($finder) - 1) {
                $output->writeln($manga->getId());
            }
        }

    }
}