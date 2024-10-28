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

use App\Utils\Curl;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * A console command that import mangas from the provided url in .env .
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
#[AsCommand(name: 'app:import-manga')]
class ImportMangaCommand extends AbstractImportMangaCommand
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this->setDescription('Import manga');
    }

    /**
     * This method is executed after initialize(). It usually contains the logic
     * to execute to complete this command task.
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $url = $_ENV['API_SEARCH'];
        $data = $this->callAPI('GET', $url);
        $data = strip_tags($data, "<a>");
        $d = preg_split("/<\/a>/", $data);
        $mangasLink = array();
        foreach ($d as $k => $u) {
            if (strpos($u, "<a href=") !== false) {
                $u = preg_replace("/.*<a\s+href=\"/sm", "", $u);
                $u = preg_replace("/\".*/", "", $u);
                if (strstr($u, $_ENV['API_MANGA_URL']) != false) {
                    array_push($mangasLink, $u);
                }
            }
        }

        for ($i = count($mangasLink); $i > 0; $i--) {
            $this->downloadManga($mangasLink[$i - 1]);
            // Download by batch of last 5
            if ($i == count($mangasLink) - 6) {
                break;
            }
        }
        return 0;
    }
}
