<?php

namespace App\Controller;

use App\Service\MovieGenerator;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class MovieController extends AbstractController
{
    #[Route('/movie', name: 'app_movie')]
    public function index(Request $request, MovieGenerator $movieGenerator): Response
    {
        if ($request->query->has('randomSeed')) {
            return $this->redirectToRoute('app_movie', [
                'seed' => random_int(0, 0xFFFFFFFFFFFF),
                'locale' => $request->query->get('locale', 'en_US'),
                'likes' => $request->query->get('likes', 0),
                'reviews' => $request->query->get('reviews', 0),
                'mode' => $request->query->get('mode', 'table'),
            ]);
        }
        $seed = (int) $request->query->get('seed', 12345);
        $locale = $request->query->get('locale', 'en_US');
        $likesAverage = max(0, min(10, (float) $request->query->get('likes', 0)));
        $reviewsAverage = max(0, min(10, (float) $request->query->get('reviews', 0)));
        $page = max(1, $request->query->getInt('page', 1));
        $mode = $request->query->get('mode') === 'gallery' ? 'gallery' : 'table';
        $count = 10;
        $movies = $movieGenerator->generate(
            $seed,
            $locale,
            $count,
            $likesAverage,
            $reviewsAverage,
            $page
        );
        if ($mode === 'gallery' && $request->query->getBoolean('partial')) {
            return $this->render('movie/_gallery_cards.html.twig', ['movies' => $movies]);
        }
        return $this->render('movie/index.html.twig', [
            'movies' => $movies,
            'seed' => $seed,
            'locale' => $locale,
            'likesAverage' => $likesAverage,
            'reviewsAverage' => $reviewsAverage,
            'page' => $page,
            'mode' => $mode,
        ]);
    }
}
