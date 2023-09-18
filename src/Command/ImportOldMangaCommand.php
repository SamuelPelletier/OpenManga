<?php

namespace App\Command;

use App\Entity\Manga;
use App\Entity\Language;
use App\Entity\Author;
use App\Entity\Tag;
use App\Entity\Parody;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
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
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

#[AsCommand(
    name: 'app:import-old-manga',
    description: 'Import old manga',
)]
class ImportOldMangaCommand extends Command
{
    private $em;
    private $logger;
    private $cache;

    public function __construct(EntityManagerInterface $em, LoggerInterface $logger)
    {
        parent::__construct();
        $this->em = $em;
        $this->logger = $logger;
        $this->cache = new FilesystemAdapter();
    }

    protected function configure(): void
    {
        $this->setDescription('Importing old manga');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        //user output command line
        $io = new SymfonyStyle($input, $output);

        // Create a cache adapter
        $cache = new FilesystemAdapter();

        set_time_limit(0); // run without timeout limit

        //$this->PopulateProxyList();

        //Get the list of proxy to use
        // Clear the cache item named 'proxy_list'
        //$this->cache->deleteItem('proxy_list');

        //Get the list of links to scrap
        //$cacheItem = $this->cache->getItem('link_list');
        // Clear the cache item named 'proxy_list'
        //$this->cache->deleteItem('link_list');

        // Read the existing content of the file
        $filename = 'next_page_to_scrap.txt';
        $nextPageNumber = intval(file_get_contents($filename));  



        //while inferior to the first manga already in our storage
        while ($nextPageNumber < 1398238) {
            
            
            $cacheItem = $this->cache->getItem('link_list');

            if (!$cacheItem->isHit() ) {
                echo "# POPULATING LINK LIST\n";
                
                // Load and set your proxy list (e.g., from a file, database, or API)
                //echo $nextPageNumber;
                list($nextNmber, $linkList) = $this->PopulateLinkList($nextPageNumber);

                echo "# found :". count($linkList);

                // Store the proxy list in the cache with a TTL of 1 hour
                $cacheItem->set($linkList);
                $this->cache->save($cacheItem);
            }

            // Retrieve the proxy list from the cache
            $links = $cacheItem->get();
            // Calculate total progress
            $progress = ($nextPageNumber / 1398238) * 100;
            $loadingBar = "Total progress : [" . str_repeat("#", (int) ($progress / 5)) . str_repeat(" ", 20 - (int) ($progress / 5)) . "] " . round($progress, 2) . "%";

            // Print the total loading bar
            echo "\r$loadingBar\n";
            echo "\rcurrent page : $nextPageNumber \n";

            for ($i = count($links); $i > 0; $i--) {
                
                // Calculate progress
                $progress = 100 - (($i / count($links)) * 100);
                $loadingBar = "Batch progress : [" . str_repeat("#", (int) ($progress / 5)) . str_repeat(" ", 20 - (int) ($progress / 5)) . "] " . round($progress, 2) . "%";

                // Print the loading bar
                echo "\r$loadingBar\n";

                //$io->note(sprintf('downloading :'. $links[$i - 1]));
                //DEBUG ! TO REMOVE
                $this->downloadManga($links[$i - 1]);
                //$this->CallAPI("POST", $_ENV['API_URL']);
                //END DEBUG
                $io->success($links[$i - 1]. ' has been downloaded');
                echo("waiting to avoid too much web request");
                sleep(5);
                ////////echo "\033[H\033[2J";
            }
            // Read the existing content of the file
            $nextPageNumber = intval(file_get_contents($filename));

            //save page number
                // Update the number (for example, increment it)
                $updatedNumber = $nextNmber;

                $filename = 'next_page_to_scrap.txt';

                // Write the updated number back to the file
                $file = fopen($filename, 'w');

                if ($file) {
                    fwrite($file, $updatedNumber);

                    fclose($file);

                    echo "Number updated to $updatedNumber in $filename.";
                } else {
                    echo "Unable to open file $filename for writing.";
                }
        }
        return Command::SUCCESS;
    }    

    private function PopulateLinkList($nextPageNumber) {
        
        $url = $_ENV['API_OLD_MANGA_SEARCH'] . $nextPageNumber ;

        // Fetch the webpage content
        $html = file_get_contents($url);

        if ($html === false) {
            //TODO USE PROXY TO FETCH LIST
            echo "Failed to fetch the webpage.";
            exit;
        }

        $pattern = $_ENV['API_PATERN'];

        preg_match_all($pattern, $html, $matches, PREG_SET_ORDER);

        if (!empty($matches)) {
            //get next page number
            $nextNmber = max(array_column($matches, 2));
            foreach ($matches as $match) {
                $links[] = $match[1];
            }
            
        } else {
            echo 'No links found.';
            exit;
        }
        return array($nextNmber,$links);
    }
    
    private function PopulateProxyList() {
        
        echo "# POPULATING PROXY LIST\n";
        echo "Press Enter to continue...";
        fgets(STDIN);

        //start curl
        $curl = curl_init();
        //basic
        $url = $_ENV['PROXY_PROVIDER_2'];
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($curl, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt($curl, CURLOPT_HEADER, 1);
        // Set a custom user agent string
        $userAgent = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/94.0.4606.61 Safari/537.36';
        curl_setopt($curl, CURLOPT_USERAGENT, $userAgent);
        //ssl
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 0);
        //DEBUG
        //curl_setopt($curl, CURLOPT_VERBOSE, true);
        set_time_limit(0);

        // Execute cURL
        $result = curl_exec($curl);
        //DEBUG
        //echo $result;

        // Check for cURL errors
        if(curl_errno($curl)) {
            echo 'Curl error: ' . curl_error($curl);
            exit;
        }

        // Use regex to extract IP and port information
        $pattern = $_ENV['PROXY_REGEX_SCRAP_2'];
        $matches = [];

        // Close cURL session
        curl_close($curl);
        
        // Output the response
        
        if (preg_match_all($pattern, $result, $matches)) {
            //env 1
            /*
            $ips = $matches[1];
            $ports = $matches[2];
            */
            //env 2
            $ips = $matches[0];
            $proxies = [];
            foreach ($ips as $index => $ip) {
            //env1
            /*
                $proxy = $ip . ':' . $ports[$index];
                $proxies[] = $proxy;
                echo $proxy." \n";
                */
            //env 2
                $proxies[] = $ip;
            }
            // $proxies array now contains IP:port combinations
            echo "# FOUND ". count($proxies)." PROXY\n";
            $previousCount = count($proxies);
            //echo "#END POPULATING PROXY LIST\n";







            //PROXY CHECKER 
            // List of proxies to check
            $proxyList = $proxies;
            $workingProxies = $proxies;
            /*
            //debug
            //$proxyList = array_slice($proxyList, 0, 100);

            // Number of proxies to check in each batch
            $batchSize = 25;
            
            $proxies = [];

            // Initialize an array to store the results (working or not)
            $workingProxies = array();

            // Loop through the proxy list in batches
            for ($i = 0; $i < count($proxyList); $i += $batchSize) {

                // Calculate total progress
                $progress = ($i / count($proxyList)) * 100;
                $loadingBar = "Proxy check progress : \n[" . str_repeat("#", (int) ($progress / 5)) . str_repeat(" ", 20 - (int) ($progress / 5)) . "] " . round($progress, 2) . "%";

                // Print the total loading bar
                echo "$loadingBar\n";
                ////////echo "\033[H\033[2J";
                    
                $batch = array_slice($proxyList, $i, $batchSize);
                // Initialize an array to hold cURL handles

                // Initialize an array to store cURL handles
                $curlHandles = array();
                
                // Initialize an array to store proxy addresses associated with cURL handles
                $proxyAddresses = array();

                // Create cURL handles for each proxy
                foreach ($batch as $proxy) {
                    set_time_limit(0);

                    $ch = curl_init("www.google.com");//$_ENV['API_URL']); // Replace with your test URL
                    
                    // Set a custom user agent string
                    $userAgent = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/94.0.4606.61 Safari/537.36';

                    $options = array(
                    //basic
                        CURLOPT_RETURNTRANSFER => 1,
                        CURLOPT_HEADER         => 1,
                        CURLOPT_FOLLOWLOCATION => false,
                        CURLOPT_ENCODING       => "",
                        CURLOPT_AUTOREFERER    => 1,
                        CURLOPT_CONNECTTIMEOUT => 10,
                        CURLOPT_TIMEOUT        => 20,
                        CURLOPT_MAXREDIRS      => 10,
                        CURLOPT_USERAGENT => $userAgent,
                    //ssl
                        CURLOPT_SSL_VERIFYPEER    => false,
                        CURLOPT_SSL_VERIFYHOST    => false,
                        CURLOPT_NOBODY => 1,
                    //proxy
                        CURLOPT_PROXY => $proxy,
                    //debug
                        CURLOPT_VERBOSE => 0,
                    );
                    curl_setopt_array( $ch, $options );

                    //DEBUG
                    //curl_setopt($ch, CURLOPT_VERBOSE, true);

                    $curlHandles[] = $ch;
                    $proxyAddresses[] = $proxy; // Store the proxy address associated with the cURL handle
                }
                
                // Create a multi-handle
                $multiHandle = curl_multi_init();
                
                // Add the cURL handles to the multi-handle
                foreach ($curlHandles as $ch) {
                    curl_multi_add_handle($multiHandle, $ch);
                }
                            
                //execute the multi handle
                do {
                    $status = curl_multi_exec($multiHandle, $active);
                    if ($active) {
                        // Wait a short time for more activity
                        curl_multi_select($multiHandle, 2);
                    }
                } while ($active);

                // Check the results
                foreach ($curlHandles as $key => $ch) {

                    echo curl_getinfo($ch, CURLINFO_HTTP_CODE)."\n";

                    if(!curl_errno($ch)){   
                        echo "\n";
                        echo 'Curl error: ' . curl_error($ch)."\n";
                        echo "\n";

                    } 


                    if (curl_getinfo($ch, CURLINFO_HTTP_CODE) == 200) {
                        $workingProxies[] = $proxyAddresses[$key]; // Retrieve the proxy address using the index
                    }
                
                    curl_multi_remove_handle($multiHandle, $ch);
                }
                
                // Close the multi-handle
                curl_multi_close($multiHandle);
                echo "\n found ".count($workingProxies)." working proxies.";
                
                if (count($workingProxies) > 10 ) {
                    return $workingProxies;
                }
            }
            // $proxies array now contains IP:port combinations
            echo "# REMAIN ". count($workingProxies)."/".$previousCount." PROXY\n";
            */
            return $workingProxies;
        } else {
            echo "Proxy could not be scrapped, change provider or check for ban.";
            exit;
        }
    }









    private function CallAPI($method=null, $url, $data = false)
    {
        //start curl
        $curl = curl_init();
        
        //basic
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($curl, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt($curl, CURLOPT_HEADER, 0);
        // Set a custom user agent string
        $userAgent = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/94.0.4606.61 Safari/537.36';
        curl_setopt($curl, CURLOPT_USERAGENT, $userAgent);
        //ssl
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, 10); // Connection timeout in seconds
        curl_setopt($curl, CURLOPT_TIMEOUT, 30); // Maximum execution time in seconds

        //DEBUG Verbose
        curl_setopt($curl, CURLOPT_VERBOSE, true);
        // Fetch the proxy list from cache if available, or create a new one
        $cacheItem = $this->cache->getItem('proxy_list');
        if (!$cacheItem->isHit()) {
            echo "# PROXY LIST NOT FOUND\n";

            // Load and set your proxy list (e.g., from a file, database, or API)
            $proxyList = $this->PopulateProxyList();
            var_dump($proxyList);
            // Store the proxy list in the cache with a TTL of 1 hour
            $cacheItem->set($proxyList);
            $cacheItem->expiresAfter(300);
            $this->cache->save($cacheItem);
        }

        // Retrieve the proxy list from the cache
        $proxyList = $cacheItem->get();
        //var_dump($proxyList);

        if($proxyList === null) {
        
            // Load and set your proxy list (e.g., from a file, database, or API)
            $proxyList = $this->PopulateProxyList();

            // Store the proxy list in the cache with a TTL
            $cacheItem->set($proxyList);
            $cacheItem->expiresAfter(500);
            $this->cache->save($cacheItem);
            //get first value of list and remove it from the array
            $selectedProxy = array_shift($proxyList);
            
        } else {
                
            //get first value of list and remove it from the array
            $selectedProxy = array_shift($proxyList);
            //echo count($proxyList)." proxies. ";

            if($selectedProxy === null) {
                echo "# SELECTED PROXY IS NULL\n";

                // Load and set your proxy list (e.g., from a file, database, or API)
                $proxyList = $this->PopulateProxyList();

                // Store the proxy list in the cache with a TTL
                $cacheItem->set($proxyList);
                $cacheItem->expiresAfter(500);
                $this->cache->save($cacheItem);
                //get first value of list and remove it from the array
                $selectedProxy = array_shift($proxyList);
            }
        }

        // Update the cached proxy list with the modified version and the original TTL
        $cacheItem->set($proxyList);
        $this->cache->save($cacheItem);

        //Proxy
        //curl_setopt($curl, CURLOPT_PROXY, $selectedProxy);
        //curl_setopt($curl, CURLOPT_HTTPPROXYTUNNEL, 0);
        //curl_setopt($curl, CURLOPT_PROXYTYPE, CURLPROXY_HTTP);
        //custom time out
        //curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, 5);

        //method
        if($method) {
            curl_setopt($curl, CURLOPT_POST, 1);
            if ($data) {
                curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
            }
            curl_setopt($curl, CURLOPT_HTTPHEADER, [ 'Content-Type: application/json',
            'Content-Length: ' . strlen($data) ] );
        }

        //very last proxy failsafe
        if ($selectedProxy ===null) {
            echo "failsafe was activated. Script was unable to find a proxy";
            exit;
        }
        //echo "# curling manga using proxy ".$selectedProxy."\n";
        set_time_limit(0);

        // Execute cURL
        $result = curl_exec($curl);
        echo "\n result returned : \n" .$result."######";
        // Check for cURL errors

        if (curl_errno($curl)) { 
            echo curl_error($curl); 
         } 
        // Close cURL session
        curl_close($curl);

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
        
        try { //TO UNCOMMENT
            if (!json_decode($raw, true)['gmetadata'][0]) {
                echo "Empty page scraping or http error.";
                exit;
            } else { // try to exctract, if error or a null is returned , retry
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
                                echo "# End of import - tag blocked detected";
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
        //TO UNCOMMENT
        
        } catch (Exception $e) {
            echo "Attempt failed: " . $e->getMessage() . PHP_EOL;
            //retry (it will atomatically switch proxy when calling the callapi function)
            $this->downloadManga($link);   
        }
        
    }
}