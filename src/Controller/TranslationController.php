<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class TranslationController extends AbstractController
{
    /**
     * @Route("/translation/",  methods={"GET"},name="translation")
     */
    public function index(Request $request)
    {

        $response = false;
        if ($request->query->get('key') !== null && $request->query->get('key') !== '') {
            $locale = $request->getLocale();
            $jsonTranslationFile = file_get_contents('../translations/json/messages.' . $locale . '.json');
            $jsonTranslationArray = json_decode($jsonTranslationFile, true);
            if (array_key_exists($request->query->get('key'), $jsonTranslationArray)) {
                $response = $jsonTranslationArray[$request->query->get('key')];
            }
        }

        return $this->json(['response' => $response]);
    }
}
