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

final class AdviceController extends AbstractController
{
    #[Route('/advice', name: 'get_all_advices', methods: ['GET'])]
    public function getAdvices(AdviceRepository $adviceRepository): JsonResponse
    {
        $currentMonth = (new DateTime())->format('m');
        $advices = $adviceRepository->findByMonth($currentMonth);

        if (!$advices) {
            return new JsonResponse(['error' => 'Aucun conseil trouvé pour ce mois'], 404);
        }

        return $this->json($advices);
    }

    #[Route('/advice/{month}', methods: ['GET'])]
    public function getAdviceByMonth(int $month, AdviceRepository $adviceRepository): JsonResponse
    {
        if ($month < 1 || $month > 12) {
            return new JsonResponse(['error' => 'Le mois doit être compris entre 1 et 12'], 400);
        }

        $advices = $adviceRepository->findByMonth($month);

        if (!$advices) {
            return new JsonResponse(['error' => 'Aucun conseil trouvé pour ce mois'], 404);
        }

        return new JsonResponse($advices);
    }

    #[IsGranted('ROLE_ADMIN')]
    #[Route('/advice/add', name: 'add_advice', methods: ['POST'])]
    public function addAdvice(Request $request, EntityManagerInterface $entityManager): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        if (!$data || !isset($data['advice'], $data['months'])) {
            return new JsonResponse(['error' => 'Données invalides'], 400);
        }

        if (!is_string($data['advice']) || strlen($data['advice']) > 255) {
            return new JsonResponse(['error' => 'Le conseil doit être une chaîne de caractères valide et ne pas dépasser 255 caractères'], 400);
        }

        if (!is_array($data['months'])) {
            return new JsonResponse(['error' => 'Le champ "months" doit être un tableau'], 400);
        }

        $advice = new Advice();
        $advice->setAdvice($data['advice']);
        $advice->setMonth( $data['months']);

        $entityManager->persist($advice);
        $entityManager->flush();

        return new JsonResponse(['message' => 'Conseil ajouté avec succès'], 201);
    }

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
