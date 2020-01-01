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
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Psr\Log\LoggerInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Filesystem\Exception\IOExceptionInterface;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Process;
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
class InstallCommand extends Command
{
    // a good practice is to use the 'app:' prefix to group all your custom application commands
    protected static $defaultName = 'app:install';

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this->setDescription('Install project');
    }

    /**
     * This method is executed after initialize(). It usually contains the logic
     * to execute to complete this command task.
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $outputStyle = new OutputFormatterStyle('green');
        $output->getFormatter()->setStyle('green', $outputStyle);
        $outputStyle = new OutputFormatterStyle('yellow');
        $output->getFormatter()->setStyle('yellow', $outputStyle);

        $section = $output->section();

        $section->writeln('<green>Installation start</>');
        $section->writeln(' - Composer install        <yellow>start</>');
        (Process::fromShellCommandline('composer install'))->run();
        $section->clear(1);
        $section->writeln(' - Composer install        <green>completed</>');
        $section->writeln(' - Drop database           <yellow>start</>');
        (Process::fromShellCommandline('php bin/console doctrine:database:drop --force'))->run();
        $section->clear(1);
        $section->writeln(' - Drop database           <green>completed</>');
        $section->writeln(' - Create database         <yellow>start</>');
        (Process::fromShellCommandline('php bin/console doctrine:database:create'))->run();
        $section->clear(1);
        $section->writeln(' - Create database         <green>completed</>');
        $section->writeln(' - Update database         <yellow>start</>');
        (Process::fromShellCommandline('php bin/console doctrine:migrations:migrate --quiet'))->run();
        $section->clear(1);
        $section->writeln(' - Update database         <green>completed</>');
        $section->writeln(' - Generate front side     <yellow>start</>');
        (Process::fromShellCommandline('yarn build'))->run();
        $section->clear(1);
        $section->writeln(' - Generate front side     <green>completed</>');
        $section->writeln(' - Clear cache             <yellow>start</>');
        (Process::fromShellCommandline('php bin/console c:c'))->run();
        $section->clear(1);
        $section->writeln(' - Clear cache             <green>completed</>');
        $section->writeln('<green>Installation done !</>');
        return 0;
    }
}
