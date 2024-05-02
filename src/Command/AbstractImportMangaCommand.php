<?php

namespace App\Command;

use App\Entity\Author;
use App\Entity\Language;
use App\Entity\Manga;
use App\Entity\Parody;
use App\Entity\Tag;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;
use WebPConvert\WebPConvert;

abstract class AbstractImportMangaCommand extends Command
{
    private $em;
    private $mailer;

    public function __construct(EntityManagerInterface $em,LoggerInterface $logger,MailerInterface $mailer )
    {
        parent::__construct();
        $this->em = $em;
        $this->logger = $logger;
        $this->mailer = $mailer;
    }

    protected function downloadManga($link)
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
        $json = json_decode($this->callAPI("POST", $_ENV['API_URL'], '{
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
            $data = $this->callAPI('GET', $_ENV['API_MANGA_URL'] . $mangaId . '/' . $token);
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
                    $data = $this->callAPI('GET', $_ENV['API_MANGA_URL'] . $mangaId . '/' . $token . '/?p=' . $page);
                    $data = strip_tags($data, "<a>");
                    $aTags = preg_split("/<\/a>/", $data);
                }
                foreach ($aTags as $aTag) {
                    if (strpos($aTag, "<a href=") !== false) {
                        $aTag = preg_replace("/.*<a\s+href=\"/sm", "", $aTag);
                        $imageLink = preg_replace("/\".*/", "", $aTag);
                        if (strstr($aTag, $_ENV['API_IMAGE_URL']) != false) {
                            $imageLinkContent = $this->callAPI('GET', $imageLink);
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

    protected function callAPI($method, $url, $data = false)
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

        // Proxy part
        $proxies = explode(',', $_ENV['PROXY_URLS']);
        shuffle($proxies);
        curl_setopt($curl, CURLOPT_PROXY, $proxies[0]);
        curl_setopt($curl, CURLOPT_PROXYPORT, 10001);
        curl_setopt($curl, CURLOPT_PROXYUSERPWD, $_ENV['PROXY_AUTH']);
        curl_setopt($curl, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt($curl, CURLOPT_HEADER, false);

        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($curl, CURLOPT_HTTPHEADER, array(
                'Content-Type: application/json',
                'Content-Length: ' . strlen($data)
            )
        );

        $result = curl_exec($curl);

        if (str_starts_with($result, 'Your IP address has been temporarily banned')) {
            $email = (new TemplatedEmail())
                ->from(new Address($_ENV['MAILER_EMAIL'], $_ENV['APP_NAME']))
                ->to($_ENV['MAILER_EMAIL'])
                ->subject('Failed to get manga')
                ->html('The proxy ' . $proxies[0] . ' failed to get manga !');

            $this->mailer->send($email);
        }

        curl_close($curl);

        return $result;
    }
}
