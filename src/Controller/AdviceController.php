<?php

namespace App\Controller;

use App\Entity\Advice;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use App\Repository\AdviceRepository;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Nelmio\ApiDocBundle\Attribute\Model;
use OpenApi\Attributes as OA;

final class AdviceController extends AbstractController
{

    #[OA\Response(
        response: 200,
        description: 'Returns the advice',
        content: new OA\JsonContent(
            type: 'array',
            items: new OA\Items(ref: new Model(type: Advice::class))
        )
    )]
    #[OA\Tag(name: 'advice')]
    #[Route('/advice', name: 'get_all_advices', methods: ['GET'])]
    public function getAdvices(AdviceRepository $adviceRepository): JsonResponse
    {
        $currentMonth = (new DateTime())->format('m');
        $advices = $adviceRepository->findByMonth($currentMonth);

        return $this->json($advices);
    }

    #[OA\Parameter(
        name: 'month',
        description: 'Identifiant du mois pour lequel vous souhaitez obtenir des conseils personnalisés',
        in: 'path',
        required: true,
        schema: new OA\Schema(type: 'integer')
    )]
    #[OA\Response(
        response: 200,
        description: 'Retourne les conseils pour le mois spécifié',
        content: new OA\JsonContent(
            type: 'array',
            items: new OA\Items(ref: new Model(type: Advice::class))
        )
    )]
    #[OA\Response(
        response: 400,
        description: 'Le mois doit être compris entre 1 et 12',
        content: new OA\JsonContent(
            properties: [
                'error' => new OA\Property(type: 'string', example: 'Le mois doit être compris entre 1 et 12')
            ],
            type: 'object'
        )
    )]
    #[OA\Tag(name: 'advice')]
    #[Route('/advice/{month}', methods: ['GET'])]
    public function getAdviceByMonth(int $month, AdviceRepository $adviceRepository): JsonResponse
    {
        if ($month < 1 || $month > 12) {
            return new JsonResponse(['error' => 'Le mois doit être compris entre 1 et 12'], 400);
        }

        $advice = $adviceRepository->findByMonth($month);

        return new JsonResponse($advice);
    }

    #[OA\RequestBody(
        required: true,
        content: new OA\JsonContent(
            properties: [
                'advice' => new OA\Property(
                    property: 'advice',
                    description: 'Le texte du conseil',
                    type: 'string',
                    example: 'Conseil important pour ce mois'
                ),
                'months' => new OA\Property(
                    property: 'months',
                    description: 'Liste des mois associés au conseil',
                    type: 'array',
                    items: new OA\Items(type: 'integer'),
                    example: [1, 2, 3]
                )
            ],
            type: 'object'
        )
    )]
    #[OA\Response(
        response: 201,
        description: 'Conseil ajouté avec succès',
        content: new OA\JsonContent(
            properties: [
                'message' => new OA\Property(type: 'string', example: 'Conseil ajouté avec succès')
            ],
            type: 'object'
        )
    )]
    #[OA\Response(
        response: 400,
        description: 'Données invalides. Le champ "advice" et "months" sont requis',
        content: new OA\JsonContent(
            properties: [
                'error' => new OA\Property(type: 'string', example: 'Données invalides')
            ],
            type: 'object'
        )
    )]
    #[OA\Tag(name: 'advice')]
    #[IsGranted('ROLE_ADMIN')]
    #[Route('/advice/add', name: 'add_advice', methods: ['POST'])]
    public function addAdvice(Request $request, EntityManagerInterface $entityManager): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        if (!$data || !isset($data['advice'], $data['months'])) {
            return new JsonResponse(['error' => 'Données invalides'], 400);
        }

        $advice = new Advice();
        $advice->setAdvice($data['advice']);
        $advice->setMonth( $data['months']);

        $entityManager->persist($advice);
        $entityManager->flush();

        return new JsonResponse(['message' => 'Conseil ajouté avec succès'], 201);
    }

    #[OA\Parameter(
        name: 'id',
        description: 'Id pour identifier le conseil à supprimer',
        in: 'path',
        required: true,
        schema: new OA\Schema(type: 'integer')
    )]
    #[OA\Response(
        response: 200,
        description: 'Conseil supprimé avec succès',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(
                    property: 'message',
                    type: 'string',
                    example: 'Conseil supprimé avec succès'
                )
            ],
            type: 'object'
        )
    )]
    #[OA\Response(
        response: 404,
        description: 'Conseil non trouvé',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(
                    property: 'error',
                    type: 'string',
                    example: 'Conseil non trouvé'
                )
            ],
            type: 'object'
        )
    )]
    #[OA\Tag(name: 'advice')]
    #[IsGranted('ROLE_ADMIN')]
    #[Route('/advice/delete/{id}', name: 'delete_advice', methods: ['POST'])]
    public function deleteAdvice(int $id, AdviceRepository $adviceRepository,  EntityManagerInterface $entityManager): JsonResponse
    {
        $advice = $adviceRepository->find($id);

        if (!$advice) {
            return new JsonResponse(['error' => 'Conseil non trouvé'], 404);
        }

        $entityManager->remove($advice);
        $entityManager->flush();

        return new JsonResponse(['message' => 'Conseil supprimé avec succès'], 200);
    }

    #[OA\Parameter(
        name: 'id',
        description: 'ID du conseil à mettre à jour',
        in: 'path',
        required: true,
        schema: new OA\Schema(type: 'integer')
    )]
    #[OA\RequestBody(
        required: true,
        content: new OA\JsonContent(
            properties: [
                new OA\Property(
                    property: 'advice',
                    description: 'Le texte du conseil à mettre à jour',
                    type: 'string',
                    example: 'Nouveau conseil pour ce mois'
                ),
                new OA\Property(
                    property: 'months',
                    description: 'Liste des mois associés au conseil',
                    type: 'array',
                    items: new OA\Items(type: 'integer'),
                    example: [1, 2, 3]
                )
            ],
            type: 'object'
        )
    )]
    #[OA\Response(
        response: 200,
        description: 'Conseil mis à jour avec succès',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'message', type: 'string', example: 'Conseil mis à jour avec succès')
            ]
        )
    )]
    #[OA\Response(
        response: 400,
        description: 'Données invalides, ou format incorrect des données envoyées',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'error', type: 'string', example: 'Données invalides')
            ]
        )
    )]
    #[OA\Response(
        response: 404,
        description: 'Conseil non trouvé',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'error', type: 'string', example: 'Conseil non trouvé')
            ]
        )
    )]
    #[OA\Tag(name: 'advice')]
    #[IsGranted('ROLE_ADMIN')]
    #[Route('/advice/update/{id}', name: 'update_advice', methods: ['POST'])]
    public function updateAdvice(int $id, AdviceRepository $adviceRepository, Request $request, EntityManagerInterface $entityManager): JsonResponse
    {
        $advice = $adviceRepository->find($id);

        if (!$advice) {
            return new JsonResponse(['error' => 'Conseil non trouvé'], 404);
        }

        $data = json_decode($request->getContent(), true);

        if (!$data || array_key_exists($data['advice'], $data['months'])) {
            return new JsonResponse(['error' => 'Données invalides'], 400);
        }

        if (!is_string($data['advice']) || strlen($data['advice']) > 255) {
            return new JsonResponse(['error' => 'Le conseil doit être une chaîne de caractères valide et ne pas dépasser 255 caractères'], 400);
        }

        if (!isset($data['months']) || !is_array($data['months'])) {
            return new JsonResponse(['error' => 'Le champ "months" doit être un tableau'], 400);
        }

        $advice->setAdvice($data['advice']);
        $advice->setMonth( $data['months']);

        $entityManager->persist($advice);
        $entityManager->flush();

        return new JsonResponse(['message' => 'Conseil mis à jour avec succès'], 201);
    }
}
