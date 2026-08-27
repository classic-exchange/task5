<?php

namespace App\Service;

use Faker\Factory;
use Random\Engine\PcgOneseq128XslRr64;
use Random\Randomizer;
use Xylis\FakerCinema\Provider\Movie;

class MovieGenerator
{
    private array $wordCache = [];

    public function generate(int $seed, string $locale, int $count, float $likesAverage, float $reviewsAverage, int $page): array
    {
        $movies = [];
        $startIndex = (($page - 1) * $count) + 1;
        $endIndex = $startIndex + $count;
        for ($i = $startIndex; $i < $endIndex; $i++) {
            $faker = Factory::create($locale);
            $faker->addProvider(new Movie($faker));
            $faker->seed($this->initPcg($seed, 'movie', $locale, $i)->getInt(0, 0xFFFFFFFF));
            $genre = $faker->movieGenre;
            $title = $this->generateTitle($seed, $locale, $i);
            $actors = $this->generateActors($seed, $locale, $i);
            $movies[] = [
                'index' => $i,
                'title' => $title,
                'actors' => $actors,
                'year' => $this->initPcg($seed, 'year', $locale, $i)->getInt(1980, 2026),
                'genre' => $genre,
                'likes' => $this->generateIntCount($likesAverage, $this->initPcg($seed, 'likes', $locale, $i)),
                'reviews' => $this->generateReviews($seed, $locale, $i, $reviewsAverage),
                'trailer' => $this->generateTrailer($seed, $locale, $i, $title, $actors),
            ];
        }
        return $movies;
    }

    private function generateTitle(int $seed, string $locale, int $index): string
    {
        $randomizer = $this->initPcg($seed, 'title', $locale, $index);
        $nouns = $this->loadWords($locale, 'nouns');
        $adjectives = $this->loadWords($locale, 'adjectives');
        $templates = match ($locale) {
            'de_DE' => ['%1$s %2$s', '%2$s und %3$s', '%2$s ohne %3$s'],
            'en_US' => ['The %1$s %2$s', '%1$s %2$s', 'The %1$s %2$s', '%1$s %2$s', 'The %2$s of %3$s', '%2$s and the %3$s'],
        };
        $template = $templates[$randomizer->getInt(0, count($templates) - 1)];
        return sprintf(
            $template,
            mb_convert_case($adjectives[$randomizer->getInt(0, count($adjectives) - 1)], MB_CASE_TITLE, 'UTF-8'),
            mb_convert_case($nouns[$randomizer->getInt(0, count($nouns) - 1)], MB_CASE_TITLE, 'UTF-8'),
            mb_convert_case($nouns[$randomizer->getInt(0, count($nouns) - 1)], MB_CASE_TITLE, 'UTF-8')
        );
    }

    private function loadWords(string $locale, string $type): array
    {
        $key = $locale . ':' . $type;
        if (!isset($this->wordCache[$key])) {
            $path = dirname(__DIR__, 2) . "/resources/words/$locale/$type.txt";
            $words = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            if ($words === false || $words === []) {
                throw new \RuntimeException("Could not load word list: $path");
            }
            $this->wordCache[$key] = $words;
        }
        return $this->wordCache[$key];
    }

    private function generateActors(int $seed, string $locale, int $i): array
    {
        $randomizer = $this->initPcg($seed, 'actors', $locale, $i);
        $faker = Factory::create($locale);
        $s = $randomizer->getInt(2, 5);
        $actors = [];
        for ($j = 0; $j < $s; $j++) {
            $faker->seed($this->initPcg($seed, 'actor-' . $j, $locale, $i)->getInt(0, 0xFFFFFFFF));
            $actors[] = $faker->firstName() . ' ' . $faker->lastName();
        }
        return $actors;
    }

    private function generateReviews(int $seed, string $locale, int $i, float $reviewsAverage): array
    {
        $randomizer = $this->initPcg($seed, 'reviews', $locale, $i);
        $faker = Factory::create($locale);
        $s = $this->generateIntCount($reviewsAverage, $randomizer);
        $reviews = [];
        for ($j = 0; $j < $s; $j++) {
            $faker->seed($this->initPcg($seed, 'review-' . $j, $locale, $i)->getInt(0, 0xFFFFFFFF));
            $reviews[] = $faker->realTextBetween(80, 160);
        }
        return $reviews;
    }

    private function generateTrailer(int $seed, string $locale, int $index, string $title, array $actors): array
    {
        $randomizer = $this->initPcg($seed, 'trailer', $locale, $index);
        $clips = $this->getTrailerClips();
        $clipCount = min($randomizer->getInt(2, 3), count($clips));
        $keys = $randomizer->pickArrayKeys($clips, $clipCount);
        $scenes = [];
        foreach ($keys as $key) {
            $scenes[] = [
                'type' => 'video',
                'clip' => $clips[$key],
                'start' => $randomizer->getFloat(0.0, 3.0),
                'duration' => $randomizer->getFloat(1.8, 2.2),
                'speed' => $randomizer->getFloat(0.8, 1.2),
                'zoom' => $randomizer->getFloat(1.0, 1.15),
                'brightness' => $randomizer->getFloat(0.8, 1.2),
                'contrast' => $randomizer->getFloat(0.9, 1.3),
                'animation' => $randomizer->getInt(0, 2),
            ];
        }
        $preview = $scenes[$randomizer->getInt(0, count($scenes) - 1)];
        $scenes[] = [
            'type' => 'text',
            'text' => $title,
            'duration' => $randomizer->getFloat(0.8, 1.2),
            'animation' => $randomizer->getInt(0, 2),
        ];
        if ($randomizer->getInt(0, 1) === 1) {
            $scenes[] = [
                'type' => 'text',
                'text' => $actors[$randomizer->getInt(0, count($actors) - 1)],
                'duration' => $randomizer->getFloat(0.7, 1.0),
                'animation' => $randomizer->getInt(0, 2),
            ];
        }
        $scenes = $randomizer->shuffleArray($scenes);
        return [
            'scenes' => $scenes,
            'preview' => [
                'clip' => $preview['clip'],
                'start' => $preview['start'],
            ],
        ];
    }

    private function getTrailerClips(): array
    {
        $files = glob(
            dirname(__DIR__, 2) . '/public/trailer/clips/*.mp4'
        );
        if ($files === false || $files === []) {
            throw new \RuntimeException('No trailer clips found.');
        }
        sort($files, SORT_STRING);
        return array_map('basename', $files);
    }


    private function initPcg(int $seed, string $type, string $locale, int $i): Randomizer
    {
        $key = json_encode([$seed, $type, $locale, $i,], JSON_THROW_ON_ERROR);
        $seedBytes = substr(hash('sha256', $key, true), 0, 16);
        return new Randomizer(new PcgOneseq128XslRr64($seedBytes));
    }

    private function generateIntCount(float $average, Randomizer $randomizer): int
    {
        $whole = (int) floor($average);
        $fraction = $average - $whole;
        if ($fraction <= 0)
            return $whole;
        $roll = $randomizer->getInt(1, 100);
        return $roll <= $fraction * 100 ? $whole + 1 : $whole;
    }
}
