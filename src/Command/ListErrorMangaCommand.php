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
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
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
use Symfony\Component\Console\Attribute\AsCommand;

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
#[AsCommand(name: 'app:check-manga')]
class ListErrorMangaCommand extends Command
{
    private $mangaRepository;
    private $em;
    private $logger;

    public function __construct(LoggerInterface $logger, MangaRepository $mangaRepository, EntityManagerInterface $em)
    {
        parent::__construct();
        $this->mangaRepository = $mangaRepository;
        $this->em = $em;
        $this->logger = $logger;
    }

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this->setDescription('Check every manga can be in error and list them')
            ->addOption(
                'iterations',
                'i',
                InputArgument::OPTIONAL,
                'Number of items check ?',
                0
            )
            ->addOption(
                'only-old',
                'o',
                InputArgument::OPTIONAL,
                'Check only old manga',
                0
            );
    }

    /**
     * This method is executed after initialize(). It usually contains the logic
     * to execute to complete this command task.
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->logger->info('Start check manga');
        $iterations = $input->getOption('iterations');
        $onlyOld = (bool)$input->getOption('only-old');
        $mangas = $this->mangaRepository->findLatestByIdDesc($onlyOld);
        // skip 10 first
        $i = -10;
        /** @var Manga $manga */
        foreach ($mangas as $manga) {
            $i++;
            if ($i < 0) {
                continue;
            }
            $folder = $manga->isOld() ? 'media_old' : 'media';
            $path = dirname(__DIR__) . '/../public/' . $folder . '/' . $manga->getId();
            $fileSystem = new Filesystem();
            if ($fileSystem->exists($path)) {
                $finder = new Finder();
                $finder->files()->in($path);
                $output->writeln($manga->getId() . ' : <fg=red>NOK</>');
                if ($manga->getId() > 1) {
                    if ($path == dirname(__DIR__) . '/../public/' . $folder . '/') {
                        die;
                    }
                    $fileSystem->remove($path);
                }
            }
            if ($iterations != 0 && $i > $iterations) {
                break;
            }
        }
        return 0;
    }
}
