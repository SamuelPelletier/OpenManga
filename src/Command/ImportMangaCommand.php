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
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
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
#[AsCommand(name: 'app:import-manga')]
class ImportMangaCommand extends Command
{
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
        $this->setDescription('Import manga');
    }

    /**
     * This method is executed after initialize(). It usually contains the logic
     * to execute to complete this command task.
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $url = $_ENV['API_SEARCH'];
        $data = file_get_contents($url);
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


        $command = $this->getApplication()->find('app:check-manga');
        $arguments = [
            'command' => 'app:check-manga',
            '--iterations' => '10'
        ];
        $greetInput = new ArrayInput($arguments);
        $command->run($greetInput, $output);


        $command = $this->getApplication()->find('app:remove-zip');
        $arguments = [
            'command' => 'app:remove-zip'
        ];
        $greetInput = new ArrayInput($arguments);
        $command->run($greetInput, $output);


        return 0;
    }

    private function downloadManga($link)
    {
        $repoManga = $this->em->getRepository(Manga::class);
        $repoLanguage = $this->em->getRepository(Language::class);
        $repoTag = $this->em->getRepository(Tag::class);
        $repoAuthor = $this->em->getRepository(Author::class);
        $repoParody = $this->em->getRepository(Parody::class);
        $explode = explode("/", $link);
        $mangaId = $explode[4];
        $this->logger->info('Try to import - manga id : ' . $mangaId);
        $token = $explode[5];
        $json = json_decode($this->CallAPI("POST", $_ENV['API_URL'], '{
            "method": "gdata",
            "gidlist": [
                [' . $mangaId . ',"' . $token . '"]
            ],
            "namespace": 1
          }'), true)['gmetadata'][0];

        if ($mangaFind = $repoManga->findOneBy(['title' => $json['title']])) {
            $this->logger->warning('Manga already exist - id : ' . $mangaFind->getId());
        } else {
            $manga = new Manga();
            $manga->setTitle($json['title']);
            $manga->setCountPages($json['filecount']);
            $manga->setPublishedAt(new \DateTime('NOW'));

            $tags = $json['tags'];
            foreach ($tags as $tagName) {
                $explodeTag = explode(':', $tagName);
                if (!isset($explodeTag[1])) {
                    array_unshift($explodeTag, 'empty');
                }
                switch ($explodeTag[0]) {
                    case 'artist':
                    case 'group':
                        if (($author = $repoAuthor->findOneBy(['name' => $explodeTag[1]])) == null) {
                            $author = new Author();
                            $author->setName($explodeTag[1]);
                            $this->em->persist($author);
                            $this->em->flush();
                        }
                        $manga->addAuthor($author);
                        break;

                    case 'parody':
                        if (($parody = $repoParody->findOneBy(['name' => $explodeTag[1]])) == null) {
                            $parody = new Parody();
                            $parody->setName($explodeTag[1]);
                            $this->em->persist($parody);
                            $this->em->flush();
                        }
                        $manga->addParody($parody);
                        break;

                    case 'language':
                        if (($language = $repoLanguage->findOneBy(['name' => $explodeTag[1]])) == null) {
                            $language = new Language();
                            $language->setName($explodeTag[1]);
                            $this->em->persist($language);
                            $this->em->flush();
                        }
                        $manga->addLanguage($language);
                        break;

                    default:
                        if (in_array($explodeTag[1], explode(',', $_ENV['API_TAG_BLOCKED']))) {
                            $this->logger->info('End of import - tag blocked detected');
                            exit;
                        }

                        if (($tagObject = $repoTag->findOneBy(['name' => $explodeTag[1]])) == null) {
                            $tagObject = new Tag();
                            $tagObject->setName($explodeTag[1]);
                            $this->em->persist($tagObject);
                            $this->em->flush();
                        }
                        $manga->addTag($tagObject);
                        break;
                }
            }

            $this->em->persist($manga);
            $this->em->flush();
            $fileSystem = new Filesystem();
            $fileSystem->mkdir(dirname(__DIR__) . '/../public/media/' . $manga->getId(), 0700);
            $data = file_get_contents($_ENV['API_MANGA_URL'] . $mangaId . '/' . $token);
            $data = strip_tags($data, "<a>");
            $aTags = preg_split("/<\/a>/", $data);
            $maxPage = 0;
            foreach ($aTags as $aTag) {
                if (preg_match('/^<a href=.*\/\?p=(?<maxPage>[0-9]{1,2})".*[0-9]$/', $aTag, $matches)) {
                    if ((int)$matches['maxPage'] > $maxPage) {
                        $maxPage = (int)$matches['maxPage'];
                    }
                }
            }
            $i = 1;
            for ($page = 0; $page < ($maxPage + 1); $page++) {
                if ($page !== 0) {
                    $data = file_get_contents($_ENV['API_MANGA_URL'] . $mangaId . '/' . $token . '/?p=' . $page);
                    $data = strip_tags($data, "<a>");
                    $aTags = preg_split("/<\/a>/", $data);
                }
                foreach ($aTags as $aTag) {
                    if (strpos($aTag, "<a href=") !== false) {
                        $aTag = preg_replace("/.*<a\s+href=\"/sm", "", $aTag);
                        $imageLink = preg_replace("/\".*/", "", $aTag);
                        if (strstr($aTag, $_ENV['API_IMAGE_URL']) != false) {
                            $imageLinkContent = file_get_contents($imageLink);
                            preg_match_all('@src="([^"]+)"@', $imageLinkContent, $match);
                            $src = array_pop($match);
                            if (strstr($src[5], '509.gif') != false) {
                                break;
                            }
                            $i = str_pad($i, 3, "0", STR_PAD_LEFT);
                            try {
                                $fileSystem->copy($src[5],
                                    dirname(__DIR__) . '/../public/media/' . $manga->getId() . '/' . $i . '.jpg', true);
                            } catch (\Exception $e) {
                                break;
                            }
                            // Create thumbnail
                            if ($i == 1) {
                                $source = dirname(__DIR__) . '/../public/media/' . $manga->getId() . '/' . $i . '.jpg';
                                $destination = dirname(__DIR__) . '/../public/media/' . $manga->getId() . '/thumb.webp';

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
                            $i++;
                        }
                    }
                }
            }
            $finder = new Finder();
            $finder->files()->in(dirname(__DIR__) . '/../public/media/' . $manga->getId());
            if (count($finder) != $i || count($finder) <= 2) {
                $this->em->remove($manga);
                $this->em->flush();
                $this->logger->error('End of import - manga : ' . $manga->getTitle() . ' -> fail because all image are not download (find :' . (count($finder) - 1) . ', expected : ' . $i . ')');
            } else {
                $this->logger->info('End of import - manga : ' . $manga->getTitle() . ' ## New ID : ' . $manga->getId());
            }
        }
    }

    private function CallAPI($method, $url, $data = false)
    {
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
        switch ($method) {
            case "POST":
                curl_setopt($curl, CURLOPT_POST, 1);

                if ($data) {
                    curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
                }
                break;
            case "PUT":
                curl_setopt($curl, CURLOPT_PUT, 1);
                break;
            default:
                if ($data) {
                    $url = sprintf("%s?%s", $url, http_build_query($data));
                }
        }

        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($curl, CURLOPT_HTTPHEADER, array(
                'Content-Type: application/json',
                'Content-Length: ' . strlen($data)
            )
        );

        $result = curl_exec($curl);

        curl_close($curl);

        return $result;
    }
}
