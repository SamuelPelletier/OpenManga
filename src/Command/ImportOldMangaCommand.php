<?php

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
use Symfony\Component\Cache\Adapter\FilesystemAdapter;

#[AsCommand(
    name: 'app:import-old-manga',
    description: 'Import old manga',
)]
class ImportOldMangaCommand extends Command
{
    private $em;
    private $logger;

    public function __construct(EntityManagerInterface $em, LoggerInterface $logger)
    {
        parent::__construct();
        $this->em = $em;
        $this->logger = $logger;
    }

    protected function configure(): void
    {
        $this->setDescription('Importing old manga');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        //user output command line
        $io = new SymfonyStyle($input, $output);

        // Get the next page number
        // Create a cache adapter
        $cache = new FilesystemAdapter();
        
        // Try to retrieve the integer value from the cache
        
        $cacheItem = $cache->getItem('next_page_to_scrap');
        if ($cacheItem->isHit()) {
            $nextPageNumber = $cacheItem->get();
        } else {
            $nextPageNumber = 1;
            $cacheItem->set($nextPageNumber);
            $cache->save($cacheItem);
        }
        


        $url = $_ENV['API_OLD_MANGA_SEARCH'] . $nextPageNumber ;
        
        // Fetch the webpage content
        $html = file_get_contents($url);

        if ($html === false) {
            echo "Failed to fetch the webpage.";
            exit;
        }

        $pattern = $_ENV['API_PATERN'];

        preg_match_all($pattern, $html, $matches, PREG_SET_ORDER);

        if (!empty($matches)) {
            //$nextNmber = max(array_column($matches, 2));
            //DEBUG ! TO REMOVE
            $nextNmber = $nextPageNumber +1;
                
            // Update the next page number in the config file
            echo "next page : $nextNmber\n";
            echo "linsting the links of mangas to scrap :\n";
            foreach ($matches as $match) {
                $links[] = $match[1];
            }
                        
            // Now you can use the $links array to access the links
            foreach ($links as $link) {
                echo $link . "\n";

                for ($i = count($links); $i > 0; $i--) {
                    $io->note(sprintf('downloading :', $links[$i - 1]));
                    //DEBUG ! TO REMOVE
                    //$this->downloadManga($links[$i - 1]);

                    $this->CallAPI("POST", $_ENV['API_URL'], '{
                        "method": "gdata",
                        "gidlist": [
                            [' . 'debug' . ',"' . 'debug' . '"]
                        ],
                        "namespace": 1
                      }');
                    //END DEBUG
                    $io->success($links[$i - 1], ' has been downloaded');
                    sleep(144);
                }
            }
        } else {
            $io->error('No links found.');
            exit;
        }
        
        return Command::SUCCESS;
    }    

    private function CallAPI($method=null, $url, $data = false)
    {
        $curl = curl_init();
        
        if($method) {
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
            curl_setopt($curl, CURLOPT_HTTPHEADER, array(
                'Content-Type: application/json',
                'Content-Length: ' . strlen($data)
            )
        );
        }
        
        $proxyList = [
            //'173.212.195.139:80',
            //'185.49.170.20:43626',
            //'50.217.29.198:80',
            '8.219.97.248:80',
        ];
        
        $randomProxy = $proxyList[array_rand($proxyList)];
        /*

        $oldIp = $this->GetIp(); //get ip without proxy
        echo $oldIp;
        echo "#";
        
        $newIp = $this->GetIp($randomProxy); //get ip with proxy
        echo $newIp;
        */

        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($curl, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt($curl, CURLOPT_HEADER, 1);

        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($curl, CURLOPT_PROXY, $randomProxy);

        curl_setopt($curl, CURLOPT_HTTPPROXYTUNNEL, 1);
        curl_setopt($curl, CURLOPT_PROXYTYPE, CURLPROXY_HTTP);
        //DEBUG
        curl_setopt($curl, CURLOPT_VERBOSE, true);

        
        // Set a custom user agent string
        $userAgent = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/94.0.4606.61 Safari/537.36';
        curl_setopt($curl, CURLOPT_USERAGENT, $userAgent);
        //curl_setopt($ch, CURLOPT_PROXYUSERPWD, "$proxyUser:$proxyPass");

        // Execute cURL session and get the response
        $result = curl_exec($curl);

        // Check for cURL errors
        if(curl_errno($curl)) {
            echo 'Curl error: ' . curl_error($curl);
            exit;
        }
        
        // Close cURL session
        curl_close($curl);
        
        // Output the response
        echo $result;

        return $result;
    }

//duplicata de la fonction download 
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
        $raw = $this->CallAPI("POST", $_ENV['API_URL'], '{
            "method": "gdata",
            "gidlist": [
                [' . $mangaId . ',"' . $token . '"]
            ],
            "namespace": 1
          }');
        echo $raw;
        
        if (json_decode($raw, true)['gmetadata'][0] === null) {
            $io->error("Empty page scraping or http error.");
            exit;
        } else {
            $json = json_decode($raw, true)['gmetadata'][0];
        }

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
}