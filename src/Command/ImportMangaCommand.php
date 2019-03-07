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
 */
class ImportMangaCommand extends Command
{
    // a good practice is to use the 'app:' prefix to group all your custom application commands
    protected static $defaultName = 'app:import-manga';

    private $entityManager;
    private $logger;

    public function __construct(EntityManagerInterface $em, LoggerInterface $logger)
    {
        parent::__construct();

        $this->entityManager = $em;
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
        $url=$_ENV['API_SEARCH'];
        $data=file_get_contents($url);
        $data = strip_tags($data,"<a>");
        $d = preg_split("/<\/a>/",$data);
        $mangasLink = array();
        foreach ( $d as $k=>$u ){
            if( strpos($u, "<a href=") !== FALSE ){
                $u = preg_replace("/.*<a\s+href=\"/sm","",$u);
                $u = preg_replace("/\".*/","",$u);
                if(strstr($u,$_ENV['API_MANGA_URL']) != false){
                    array_push($mangasLink,$u);
                }
            }
        }

        /*foreach($mangasLink as $link){
            $explode = explode("/",$link);
            $mangaId = $explode[4];
            $token = $explode[5];
            $json_decode($this->CallAPI("POST",$_ENV['API_URL'],'{
                "method": "gdata",
                "gidlist": [
                    ['.$mangaId.',"'.$token.'"]
                ],
                "namespace": 1
              }'),true)['gmetadata'][0]['title'];
        }*/
        $emManga = $this->entityManager->getRepository(Manga::class);
        $explode = explode("/",$mangasLink[0]);
        $mangaId = $explode[4];
        $this->logger->info('Try to import - manga id : '.$mangaId);
        $token = $explode[5];
        $json = json_decode($this->CallAPI("POST",$_ENV['API_URL'],'{
            "method": "gdata",
            "gidlist": [
                ['.$mangaId.',"'.$token.'"]
            ],
            "namespace": 1
          }'),true)['gmetadata'][0];

        if($emManga->findOneBy(['title' => $json['title']])){
            $this->logger->warning('Manga already exist - id : '.$mangaId);
        }else{
            $manga = new Manga();
            $manga->setTitle($json['title']);
            $manga->setCountPages($json['filecount']);
            $manga->setPublishedAt(new \DateTime('NOW'));
            $this->entityManager->persist($manga);
            $this->entityManager->flush();
            $fileSystem = new Filesystem();
            $fileSystem->mkdir(dirname(__DIR__).'/../public/media/'.$manga->getId(), 0700);
            $data=file_get_contents($_ENV['API_MANGA_URL'].$mangaId.'/'.$token);
            $data = strip_tags($data,"<a>");
            $d = preg_split("/<\/a>/",$data);
            $mangasLink = array();
            $i = 1;
            foreach ( $d as $k=>$u ){
                if( strpos($u, "<a href=") !== FALSE ){
                    $u = preg_replace("/.*<a\s+href=\"/sm","",$u);
                    $u = preg_replace("/\".*/","",$u);
                    if(strstr($u,$_ENV['API_IMAGE_URL']) != false){
                        $datau = file_get_contents($u);
                        preg_match_all( '@src="([^"]+)"@' , $datau, $match );
                        $src = array_pop($match);
                        $i = str_pad($i, 3, "0", STR_PAD_LEFT);
                        $fileSystem->copy($src[5], dirname(__DIR__).'/../public/media/'.$manga->getId().'/'.$i.'.jpg');
                        $i++;
                    }
                }
            }
            $this->logger->info('End of import - manga id : '.$mangaId);
        }
    }

    private function CallAPI($method, $url, $data = false)
    {
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
        switch ($method)
        {
            case "POST":
                curl_setopt($curl, CURLOPT_POST, 1);

                if ($data)
                    curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
                break;
            case "PUT":
                curl_setopt($curl, CURLOPT_PUT, 1);
                break;
            default:
                if ($data)
                    $url = sprintf("%s?%s", $url, http_build_query($data));
        }

        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($curl, CURLOPT_HTTPHEADER, array(                                                                          
            'Content-Type: application/json',                                                                                
            'Content-Length: ' . strlen($data))                                                                       
        );

        $result = curl_exec($curl);

        curl_close($curl);

        return $result;
    }
}
