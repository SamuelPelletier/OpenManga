<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Controller;

use App\Entity\Manga;
use App\Entity\User;
use App\Repository\AuthorRepository;
use App\Repository\LanguageRepository;
use App\Repository\MangaRepository;
use App\Repository\ParodyRepository;
use App\Repository\TagRepository;
use App\Service\MangaService;
use Doctrine\ORM\EntityManagerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Cache;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Finder\Finder;
use Symfony\Component\HttpKernel\KernelInterface;
use App\Message\ImageTranslation;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Stamp\HandledStamp;
use App\Service\WorkerService;
use Symfony\Component\Dotenv\Dotenv;


/**
 * Controller used to manage blog contents in the public part of the site.
 *
 * @Route("/")
 *
 */
class MangaController extends BaseController
{
    /**
     * @Route("/", defaults={"page": "1"}, methods={"GET"}, name="index")
     * @Route("/page/{page<[1-9]\d*>}", methods={"GET"}, name="index_paginated")
     * @Cache(smaxage="10")
     *
     */
    public function index(
        int             $page,
        Request         $request,
        MangaRepository $mangas
    ): Response
    {
        $isSortByViews = false;
        if ($request->getSession()->get('sort') != null) {
            $isSortByViews = $request->getSession()->get('sort');
        }
        $latestMangas = $mangas->findLatest($page, $isSortByViews);
    
        
        if ($request->isXmlHttpRequest()) {
            if (count($latestMangas->getQuery()->getArrayResult()) === 0) {
                return $this->render('manga_ending.html.twig');
            }
            
            return $this->render('manga_index.html.twig', ['mangas' => $latestMangas]);
        }
        return $this->render('index.html.twig',  ['mangas' => $latestMangas]);
    }

    /**
     * @Route("/trending", defaults={"page": "1"}, methods={"GET"}, name="index_trending")
     * @Route("/page/{page<[1-9]\d*>}", methods={"GET"}, name="index_trending_paginated")
     * @Cache(smaxage="10")
     *
     */
    public function trending(
        int             $page,
        Request         $request,
        MangaRepository $mangas
    ): Response
    {
        $latestMangas = $mangas->findTrending($page);
        if ($request->isXmlHttpRequest()) {
            if (count($latestMangas->getQuery()->getArrayResult()) === 0) {
                return $this->render('manga_ending.html.twig');
            }
            return $this->render('manga_index.html.twig', ['mangas' => $latestMangas]);
        }
        return $this->render('index.html.twig', ['mangas' => $latestMangas]);
    }

    /**
     * @Route("/mangas/{id}", methods={"GET"}, name="manga")
     *
     */
    public function mangaShow(
        Manga                  $manga,
        MangaRepository        $mangaRepository,
        Request                $request,
        MangaService           $mangaService,
        EntityManagerInterface $entityManager,
        ): Response
    {
        /**
         * @var User $user
         */
        $user = $this->getUser();

        $images = array();
        for ($i = 1; $i < $manga->getCountPages(); $i++) {
            $images[] = str_pad($i, 3, 0, STR_PAD_LEFT) . '.jpg';
        }

        $mangaView = explode(',', $request->getSession()->get('manga_view', ''));
        // Check in the session if this manga is already view
        if (!in_array($manga->getId(), $mangaView)) {
            $request->getSession()->set('manga_view',
                $request->getSession()->get('manga_view') . ',' . $manga->getId());
            $manga->setCountViews($manga->getCountViews() + 1);
            $entityManager->persist($manga);
            $entityManager->flush();
        }

        $translationFilePath = 'media/' . $manga->getId() . '/translated/translations.json';
        
        // Initialize translations as an empty array
        $translations = [];
        // Check if the JSON file exists
        if (file_exists($translationFilePath)) {
            // Retrieve JSON translation data if the file exists
            $translationData = file_get_contents($translationFilePath);
            $translations = json_decode($translationData, true);
            /*
            if (json_last_error() !== JSON_ERROR_NONE) {
                $translations = ['translation json error'];
            }
            */
            $translations = is_array($translations) ? $translations : array();
        }

        // Check if translations are enabled
        $translationsEnabled = $_ENV['TRANSLATION_ENABLED'] === 'true';

        // User is logged in
        if ($this->isGranted('ROLE_USER')) {
            $user->addLastMangasRead($manga);
            if (!in_array($manga->getId(), $mangaView)) {
                $user->incrementCountMangasRead();
            }
            $entityManager->persist($user);
            $entityManager->flush();
        }

        $mangasRecommended = $mangaService->getRecommendationByManga($manga);

        return $this->render('manga_show.html.twig',
            [
                'manga' => $manga,
                'images' => $images,
                'mangaRepository' => $mangaRepository,
                'mangas_recommended' => $mangasRecommended,
                'translations' => $translations,
                'translationsEnabled' => $translationsEnabled,
            ]);
    }

    /**
     * @Route("/search", methods={"GET"}, name="search")
     * @Route("/search/page/{page<[1-9]\d*>}", methods={"GET"}, name="search_paginated")
     */
    public function search(
        Request         $request,
        MangaRepository $mangas,
        int             $page = 1
    ): Response
    {
        // No query parameter
        $foundMangas = null;
        $isSortByViews = $request->query->get('sort') != null ? true : false;
        $request->getSession()->set('sort', $isSortByViews);

        if ($request->query->get('q') !== null && $request->query->get('q') == '') {
            return $this->redirectToRoute('index');
        } else {
            if ($request->query->get('q') != '') {
                $query = $request->query->get('q', '');
                $isStrict = $request->query->get('s', false);
                $foundMangas = $mangas->findBySearchQuery($query, $page, $isSortByViews, $isStrict);
            }
        }

        if ($request->isXmlHttpRequest()) {
            if (count($foundMangas->getQuery()->getArrayResult()) === 0) {
                return $this->render('manga_ending.html.twig');
            }
            return $this->render('manga_index.html.twig', ['mangas' => $foundMangas]);
        }

        return $this->render('search.html.twig', ['mangas' => $foundMangas]);
    }

    /**
     * @Route("/download/{id}", methods={"GET"}, name="download")
     */
    public function mangaDownload(
        Manga                  $manga,
        EntityManagerInterface $entityManager
    ): Response
    {
        /**
         * @var User $user
         */
        $user = $this->getUser();

        if (is_dir('media/' . $manga->getId() . '/')) {

            $zipFolder = 'media/zip/';

            $zipName = htmlspecialchars_decode($manga->getTitle(), ENT_QUOTES) . ".zip";
            $zipName = str_replace(['|', '/', '\\'], '', $zipName);
            if (!file_exists($zipFolder . $zipName)) {
                $files = array();
                $finder = new Finder();
                $finder->files()->in('media/' . $manga->getId() . '/');
                foreach ($finder as $file) {
                    if (preg_match("/\.jpg$/", $file->getFilename())) {
                        array_push($files, $file);
                    }
                }

                $zip = new \ZipArchive();
                $zip->open($zipFolder . $zipName, \ZipArchive::CREATE);
                foreach ($files as $f) {
                    $zip->addFromString(basename($f), file_get_contents($f));
                }
                $zip->close();
            }

            $response = new BinaryFileResponse($zipFolder . $zipName);
            $response->headers->set('Content-Type', 'application/zip');
            $response->headers->set('Content-Disposition', 'attachment;filename="' . $zipName . '"');
            $response->headers->set('Content-length', filesize($zipFolder . $zipName));

            if ($user) {
                $user->incrementCountMangasDownload();
                $entityManager->persist($user);
                $entityManager->flush();
            }

            return $response;
        } else {
            throw $this->createNotFoundException('Sorry this file doesn\'t exist');
        }

    }

    /**
     * @Route("/translate/{id}", methods={"POST"}, name="translate")
     */
    public function mangaTranslate(
        Manga $manga,
        MessageBusInterface $bus,
        EntityManagerInterface $entityManager,
        Request $request,
        KernelInterface $kernel
    ): Response
    {
        // Get the root directory of your Symfony application
        $rootDir = $kernel->getProjectDir();

        // Construct the full input folder path
        $inputFolderPath = $rootDir . '/public/media/' . $manga->getId() . '/';

        #get input language
        $languages = $manga->getLanguages();
        if (empty($languages)) {
            $language = 'japaanese';
        } else {
            $language = 'jaapanese';
            // Loop through the languages and find the first one that is not 'translated'
            foreach ($languages as $lang) {
                if ($lang != 'translated') {
                    $language = $lang;
                    break;
                }
            }
        }
        
        #get output language
        $locale = $request->getLocale();

        // Get the values for the input and output languages and other optional 
        $inputLanguage = $request->query->get('inputLanguage', $language);
        $outputLanguage = $request->query->get('outputLanguage', $locale);
        $inputFolderPath = $request->query->get('inputFolderPath', $rootDir.'/public/media/' . $manga->getId() . '/');
        $outputFolderPath = $request->query->get('outputFolderPath', $rootDir.'/public/media/' . $manga->getId() .'/'. 'translated/');
        $transparency = $request->query->get('transparency', 200);
        
        //$envelope = $bus->dispatch(new ImageTranslation( $inputLanguage, $outputLanguage, $inputFolderPath, $outputFolderPath, $transparency));

        // Iterate through the images in the input folder
        $files = scandir($inputFolderPath);
        foreach ($files as $file) {
            if ($file !== '.' && $file !== '..') {
                // Construct the full input file path for each image
                $inputFilePath = $inputFolderPath . $file;

                // Dispatch the translation job for the current image
                $envelope = $bus->dispatch(new ImageTranslation( $inputLanguage, $outputLanguage, $inputFilePath, $outputFolderPath, $transparency));
            }
        }
        
        // get the value that was returned by the last message handler
        //$handledStamp = $envelope->last(HandledStamp::class);
        //$result = $handledStamp->getResult();
        
        // or get info about all of handlers
        //$handledStamps = $envelope->all(HandledStamp::class);

        // Translation is complete, return a response indicating completion
        return new Response(var_dump($envelope));
    }

    /**
     * @Route("/edit-translation/{id}", methods={"POST"}, name="edit_translation")
     */
    public function editTranslation(        
        Manga $manga,
        MessageBusInterface $bus,
        EntityManagerInterface $entityManager,
        Request $request,
        KernelInterface $kernel
    ): Response
    {
        // Get the root directory of your Symfony application
        $rootDir = $kernel->getProjectDir();

        // Get the content of translations.json
        $filePath = $rootDir . '/public/media/' . $manga->getId() . '/translated/translations.json';
        $translations = json_decode(file_get_contents($filePath), true);

        if ($request->isMethod('POST')) {
            // Update translations based on user input
            $newTranslations = $request->request->get('translations');
            $updatedTranslations = array_merge($translations, $newTranslations);

            // Save the updated translations back to the file
            file_put_contents($filePath, json_encode($updatedTranslations, JSON_PRETTY_PRINT));

            // Optionally, redirect to a success page or return a response
            return $this->redirectToRoute('success_page');
        }

        return $this->render('translation/edit.html.twig', [
            'translations' => $translations,
        ]);
    }

    /**
     * @Route("/favorite/{id}/add", methods={"POST"}, name="add_favorite")
     */
    public function addFavorite(Manga $manga, EntityManagerInterface $entityManager)
    {
        /**
         * @var User $user
         */
        $user = $this->getUser();

        $user->addFavoriteManga($manga);
        $entityManager->persist($user);
        $entityManager->flush();

        return $this->json(['response' => true]);
    }

    /**
     * @Route("/favorite/{id}/remove", methods={"POST"}, name="remove_favorite")
     */
    public function removeFavorite(Manga $manga, EntityManagerInterface $entityManager)
    {
        /**
         * @var User $user
         */
        $user = $this->getUser();

        $user->removeFavoriteManga($manga);
        $entityManager->persist($user);
        $entityManager->flush();

        return $this->json(['response' => true]);
    }
}