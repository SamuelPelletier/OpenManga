<?php

namespace App\Command;

use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Mailer\MailerInterface;

#[AsCommand(
    name: 'app:import-old-manga',
    description: 'Import old manga',
)]
class ImportOldMangaCommand extends AbstractImportMangaCommand
{
    private $projectDir;

    public function __construct(EntityManagerInterface $em, LoggerInterface $logger, MailerInterface $mailer, KernelInterface $kernel)
    {
        parent::__construct($em, $logger, $mailer);
        $this->projectDir = $kernel->getProjectDir();
    }

    protected function configure(): void
    {
        $this->setDescription('Importing old manga');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        // Read the existing content of the file
        $filename = 'next_page_to_scrap.txt';
        $nextPageNumber = intval(file_get_contents($this->projectDir . '/var/' . $filename));
        list($nextPage, $linkList) = $this->populateLinkList($nextPageNumber);
        $progress = new ProgressBar($output, count($linkList));
        for ($i = count($linkList); $i > 0; $i--) {
            $this->downloadManga($linkList[$i - 1]);
            $progress->advance();
            break;
        }
        $progress->finish();

        // Write the updated number back to the file
        $file = fopen($this->projectDir . '/var/' . $filename, 'w');
        fwrite($file, $nextPage);
        fclose($file);
        return Command::SUCCESS;
    }

    private function populateLinkList($nextPageNumber)
    {

        $url = $_ENV['API_OLD_MANGA_SEARCH'] . $nextPageNumber;

        // Fetch the webpage content
        $html = $this->callAPI('GET', $url);
        $pattern = $_ENV['API_PATTERN'];

        preg_match_all($pattern, $html, $matches, PREG_SET_ORDER);

        if (!empty($matches)) {
            //get next page number
            $nextPage = max(array_column($matches, 2));
            foreach ($matches as $match) {
                $links[] = $match[1];
            }

        }
        return array($nextPage, $links);
    }
}
