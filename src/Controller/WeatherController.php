<?php

namespace App\Controller;

use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpClient\HttpClient;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;

final class WeatherController extends AbstractController
{
    private string $apiKey = 'fdd787f4dba6b80e8f6a5e3ac7e49fb9';

    #[Route('/weather/{city?}', name: 'get_weather', methods: ['GET'])]
    public function getWeatherByTown(?string $city, CacheInterface $cache, Security $security, UserRepository $userRepository): JsonResponse
    {
        if (!$city) {
            $city = $this->getUserCity($security, $userRepository);

            if (!$city) {
                return new JsonResponse(['error' => 'Aucune ville trouvée pour cet utilisateur'], 404);
            }
        }

        $cacheKey = 'weather_' . strtolower($city);
        $weatherData = $cache->get($cacheKey, function (ItemInterface $item) use ($city) {
            $item->expiresAfter(1800); //30 minutes
            $client = HttpClient::create();
            $url = sprintf(
                'https://api.openweathermap.org/data/2.5/weather?q=%s&appid=%s&units=metric&lang=fr',
                urlencode($city),
                $this->apiKey
            );

            $response = $client->request('GET', $url);

            if ($response->getStatusCode() !== 200) {
                return null;
            }

            return $response->toArray();
        });

        if (!$weatherData) {
            return new JsonResponse(['error' => 'Impossible de récupérer la météo'], 500);
        }

        $result = [
            'ville' => $city,
            'température' => $weatherData['main']['temp'] . '°C',
            'description' => ucfirst($weatherData['weather'][0]['description']),
            'vent' => $weatherData['wind']['speed'] . ' m/s',
            'humidité' => $weatherData['main']['humidity'] . '%',
        ];

        return new JsonResponse($result);
    }

    private function getUserCity(Security $security, UserRepository $userRepository): ?string
    {
        /** @var UserInterface|null $user */
        $user = $security->getUser();

        if (!$user) {
            return null;
        }

        $userEntity = $userRepository->findOneBy(['email' => $user->getUserIdentifier()]);

        return $userEntity ? $userEntity->getCity() : null;
    }
}
