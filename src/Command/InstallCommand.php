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

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Formatter\OutputFormatterStyle;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Process\Process;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;

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

    private $em;
    private $passwordEncoder;

    public function __construct(EntityManagerInterface $em, UserPasswordEncoderInterface $passwordEncoder)
    {
        parent::__construct();

        $this->em = $em;
        $this->passwordEncoder = $passwordEncoder;
    }

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this->setDescription('Install project')
            ->addOption(
                'add-admin',
                null,
                InputOption::VALUE_NONE,
                'Add an admin'
            );
    }

    /**
     * This method is executed after initialize(). It usually contains the logic
     * to execute to complete this command task.
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $section = $output->section();

        $outputStyle = new OutputFormatterStyle('green');
        $output->getFormatter()->setStyle('green', $outputStyle);
        $outputStyle = new OutputFormatterStyle('yellow');
        $output->getFormatter()->setStyle('yellow', $outputStyle);

        $addAdminOption = $input->getOption('add-admin');
        if (!$addAdminOption) {

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
        }
        $section->writeln(' - Add admin               <yellow>start</>');
        $adminCredentials = $this->addAdmin();
        $section->clear(1);
        $section->writeln(' - Add admin               <green>completed</>');
        if (!$addAdminOption) {
            $section->writeln(' - Generate front side     <yellow>start</>');
            (Process::fromShellCommandline('yarn build'))->run();
            $section->clear(1);
            $section->writeln(' - Generate front side     <green>completed</>');
            $section->writeln(' - Clear cache             <yellow>start</>');
            (Process::fromShellCommandline('php bin/console c:c'))->run();
            $section->clear(1);
            $section->writeln(' - Clear cache             <green>completed</>');
            $section->writeln('<green>Installation done !</>');
        }
        $section->writeln('<options=bold>Admin username : ' . $adminCredentials[0] . '</>');
        $section->writeln('<options=bold>Admin password : ' . $adminCredentials[1] . '</>');
        return 0;
    }

    private function addAdmin()
    {
        $admin = new User();

        $adminUsername = 'admin' . random_int(111, 999);
        $adminPassword = random_int(1111111, 9999999);
        $adminPasswordEncoded = $this->passwordEncoder->encodePassword(
            $admin,
            $adminPassword
        );

        $admin->setUsername($adminUsername);
        $admin->setPassword($adminPasswordEncoded);
        $admin->setRoles(['ROLE_ADMIN']);
        $this->em->persist($admin);
        $this->em->flush();
        return [$adminUsername, $adminPassword];
    }
}
