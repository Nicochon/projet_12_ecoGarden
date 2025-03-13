<?php

namespace App\Controller;

use App\Repository\UserRepository;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpClient\HttpClient;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;
use Nelmio\ApiDocBundle\Attribute\Model;
use OpenApi\Attributes as OA;

final class WeatherController extends AbstractController
{
    private string $apiKey = 'fdd787f4dba6b80e8f6a5e3ac7e49fb9';

    #[OA\Parameter(
        name: 'city',
        description: 'Le nom de la ville pour récupérer la météo (si non précisé, la ville de l\'utilisateur sera utilisée)',
        in: 'path',
        required: false,
        schema: new OA\Schema(type: 'string', example: 'Paris')
    )]
    #[OA\Response(
        response: 200,
        description: 'Données météorologiques récupérées avec succès',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'ville', type: 'string', example: 'Paris'),
                new OA\Property(property: 'température', type: 'string', example: '15°C'),
                new OA\Property(property: 'description', type: 'string', example: 'Nuageux'),
                new OA\Property(property: 'vent', type: 'string', example: '5 m/s'),
                new OA\Property(property: 'humidité', type: 'string', example: '80%')
            ]
        )
    )]
    #[OA\Response(
        response: 404,
        description: 'Aucune ville trouvée pour cet utilisateur',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'error', type: 'string', example: 'Aucune ville trouvée pour cet utilisateur')
            ]
        )
    )]
    #[OA\Response(
        response: 500,
        description: 'Impossible de récupérer les données météorologiques',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'error', type: 'string', example: 'Impossible de récupérer la météo')
            ]
        )
    )]
    #[OA\Response(
        response: 400,
        description: 'La ville spécifiée est invalide ou la requête est malformée',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'error', type: 'string', example: 'Ville invalide')
            ]
        )
    )]
    #[OA\Tag(name: 'weather')]
    #[Route('/weather/{city?}', name: 'get_weather', methods: ['GET'])]
    public function getWeatherByTown(?string $city, CacheInterface $cache, Security $security, UserRepository $userRepository, LoggerInterface $logger): JsonResponse
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

        $logger->info("Cache HIT pour $city, pas d'appel API !");

        $result = [
            'ville' => $city,
            'température' => $weatherData['main']['temp'] . '°C',
            'description' => ucfirst($weatherData['weather'][0]['description']),
            'vent' => $weatherData['wind']['speed'] . ' m/s',
            'humidité' => $weatherData['main']['humidity'] . '%',
        ];

        $response = new JsonResponse($result);
        $response->setPublic();
        $response->setMaxAge(1800);
        $response->setSharedMaxAge(1800);
        $response->headers->set('Cache-Control', 'public, max-age=1800, s-maxage=1800');
        $response->setEtag(md5(json_encode($result)));


        return $response;
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
