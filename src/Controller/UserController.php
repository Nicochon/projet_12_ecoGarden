<?php

namespace App\Controller;

use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Nelmio\ApiDocBundle\Attribute\Model;
use OpenApi\Attributes as OA;

final class UserController extends AbstractController
{
    #[OA\RequestBody(
        required: true,
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'email', description: 'L\'email de l\'utilisateur', type: 'string', example: 'utilisateur@example.com'),
                new OA\Property(property: 'password', description: 'Le mot de passe de l\'utilisateur', type: 'string', example: 'motdepasse123'),
                new OA\Property(property: 'city', description: 'La ville de l\'utilisateur', type: 'string', example: 'Paris'),
                new OA\Property(property: 'pseudo', description: 'Le pseudo de l\'utilisateur', type: 'string', example: 'utilisateur123')
            ],
            type: 'object'
        )
    )]
    #[OA\Response(
        response: 201,
        description: 'Utilisateur créé avec succès',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'message', type: 'string', example: 'Utilisateur créé avec succès')
            ]
        )
    )]
    #[OA\Response(
        response: 400,
        description: 'Données invalides ou champ manquant, email/pseudo déjà utilisés',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'error champs', type: 'string', example: 'Le champ pseudo est obligatoire et ne peut pas être vide'),
                new OA\Property(property: 'error email', type: 'string', example: 'Format d\'email invalide'),
                new OA\Property(property: 'error email deja utilisé', type: 'string', example: 'Cet email est déjà utilisé'),
                new OA\Property(property: 'error pseudo deja utilisé', type: 'string', example: 'Ce pseudo est déjà pris')
            ]
        )
    )]
    #[OA\Response(
        response: 500,
        description: 'Erreur interne',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'error', type: 'string', example: 'Une erreur interne est survenue'),
                new OA\Property(property: 'details', type: 'string', example: 'Détails de l\'erreur interne')
            ]
        )
    )]
    #[OA\Tag(name: 'user')]
    #[Route('/user', methods: ['POST'])]
    public function createUser(Request $request, UserPasswordHasherInterface $passwordHasher, EntityManagerInterface $entityManager, UserRepository $userRepository): JsonResponse
    {
        try {
            $data = json_decode($request->getContent(), true);

            $requiredFields = ['email', 'password', 'city', 'pseudo'];
            foreach ($requiredFields as $field) {
                if (empty($data[$field])) {
                    return new JsonResponse(['error' => "Le champ '$field' est obligatoire et ne peut pas être vide"], 400);
                }
            }

            if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
                return new JsonResponse(['error' => 'Format d\'email invalide'], 400);
            }

            if (strlen($data['password']) < 6) {
                return new JsonResponse(['error' => 'Le mot de passe doit contenir au moins 6 caractères'], 400);
            }

            $existingUser = $userRepository->findOneBy(['email' => $data['email']]);
            if ($existingUser) {
                return new JsonResponse(['error' => 'Cet email est déjà utilisé'], 400);
            }

            $existingPseudo = $userRepository->findOneBy(['pseudo' => $data['pseudo']]);
            if ($existingPseudo) {
                return new JsonResponse(['error' => 'Ce pseudo est déjà pris'], 400);
            }

            $user = new User();
            $user->setPseudo($data['pseudo']);
            $user->setEmail($data['email']);
            $user->setPassword($passwordHasher->hashPassword($user, $data['password']));
            $user->setCity($data['city']);
            $user->setRoles(['ROLE_USER']);

            $entityManager->persist($user);
            $entityManager->flush();

            return new JsonResponse(['message' => 'Utilisateur créé avec succès'], 201);
        } catch (\Exception $e) {
            return new JsonResponse(['error' => 'Une erreur interne est survenue', 'details' => $e->getMessage()], 500);
        }
    }

    #[OA\Parameter(
        name: 'id',
        description: 'L\'ID de l\'utilisateur à mettre à jour',
        in: 'path',
        required: true,
        schema: new OA\Schema(type: 'integer')
    )]
    #[OA\RequestBody(
        required: true,
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'email', description: 'L\'email de l\'utilisateur', type: 'string', example: 'utilisateur@example.com'),
                new OA\Property(property: 'password', description: 'Le mot de passe de l\'utilisateur', type: 'string', example: 'nouveauMotDePasse123'),
                new OA\Property(property: 'city', description: 'La ville de l\'utilisateur', type: 'string', example: 'Paris'),
                new OA\Property(property: 'pseudo', description: 'Le pseudo de l\'utilisateur', type: 'string', example: 'utilisateur123'),
                new OA\Property(property: 'role', description: 'Les rôles de l\'utilisateur', type: 'array', items: new OA\Items(type: 'string'), example: ['ROLE_USER'])
            ],
            type: 'object'
        )
    )]
    #[OA\Response(
        response: 200,
        description: 'Utilisateur mis à jour avec succès',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'message', type: 'string', example: 'Utilisateur mis à jour avec succès')
            ]
        )
    )]
    #[OA\Response(
        response: 400,
        description: 'Données invalides ou champ manquant, email/pseudo déjà utilisés',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'error pseudo obligatoire', type: 'string', example: 'Le champ pseudo est obligatoire et ne peut pas être vide'),
                new OA\Property(property: 'error email invalide', type: 'string', example: 'Format d\'email invalide'),
                new OA\Property(property: 'error email deja utilisé', type: 'string', example: 'Cet email est déjà utilisé'),
                new OA\Property(property: 'error pseudo deja utilisé', type: 'string', example: 'Ce pseudo est déjà pris'),
                new OA\Property(property: 'error mdp', type: 'string', example: 'Le mot de passe doit contenir au moins 6 caractères')
            ]
        )
    )]
    #[OA\Response(
        response: 404,
        description: 'Utilisateur non trouvé',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'error', type: 'string', example: 'Utilisateur non trouvé')
            ]
        )
    )]
    #[OA\Response(
        response: 500,
        description: 'Erreur interne',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'error', type: 'string', example: 'Une erreur interne est survenue'),
                new OA\Property(property: 'details', type: 'string', example: 'Détails de l\'erreur interne')
            ]
        )
    )]
    #[OA\Tag(name: 'user')]
    #[IsGranted('ROLE_ADMIN')]
    #[Route('/user/update/{id}', name: 'update_user', methods: ['PUT'])]
    public function updateUser(int $id, Request $request,  UserRepository $userRepository, UserPasswordHasherInterface $passwordHasher,  EntityManagerInterface $entityManager): JsonResponse
    {
        try {
            $user = $userRepository->find($id);

            if (!$user) {
                return new JsonResponse(['error' => 'Utilisateur non trouvé'], 404);
            } else {
                $data = json_decode($request->getContent(), true);

                $requiredFields = ['email', 'password', 'city', 'pseudo', 'role'];
                foreach ($requiredFields as $field) {
                    if (empty($data[$field])) {
                        return new JsonResponse(['error' => "Le champ '$field' est obligatoire et ne peut pas être vide"], 400);
                    }
                }

                if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
                    return new JsonResponse(['error' => 'Format d\'email invalide'], 400);
                }

                $existingUser = $userRepository->findOneBy(['email' => $data['email']]);
                if ($existingUser) {
                    return new JsonResponse(['error' => 'Cet email est déjà utilisé'], 400);
                }

                $existingPseudo = $userRepository->findOneBy(['pseudo' => $data['pseudo']]);
                if ($existingPseudo) {
                    return new JsonResponse(['error' => 'Ce pseudo est déjà pris'], 400);
                }

                $user->setPseudo($data['pseudo']);
                $user->setEmail($data['email']);
                $user->setCity($data['city']);
                $user->setRoles(is_array($data['role']) ? $data['role'] : [$data['role']]);

                if (!empty($data['password'])) {
                    if (strlen($data['password']) < 6) {
                        return new JsonResponse(['error' => 'Le mot de passe doit contenir au moins 6 caractères'], 400);
                    }
                    $user->setPassword($passwordHasher->hashPassword($user, $data['password']));
                }

                $entityManager->persist($user);
                $entityManager->flush();

                return new JsonResponse(['message' => 'Utilisateur modifier avec succès'], 201);
            }


        } catch (\Exception $e) {
            return new JsonResponse(['error' => 'Une erreur interne est survenue', 'details' => $e->getMessage()], 500);
        }
    }

    #[OA\Parameter(
        name: 'id',
        description: 'L\'ID de l\'utilisateur à supprimer',
        in: 'path',
        required: true,
        schema: new OA\Schema(type: 'integer')
    )]
    #[OA\Response(
        response: 200,
        description: 'Utilisateur supprimé avec succès',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'message', type: 'string', example: 'Utilisateur supprimé avec succès')
            ]
        )
    )]
    #[OA\Response(
        response: 404,
        description: 'Utilisateur non trouvé',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'error', type: 'string', example: 'Utilisateur non trouvé')
            ]
        )
    )]
    #[OA\Response(
        response: 500,
        description: 'Erreur interne',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'error', type: 'string', example: 'Une erreur interne est survenue'),
                new OA\Property(property: 'details', type: 'string', example: 'Détails de l\'erreur interne')
            ]
        )
    )]
    #[OA\Tag(name: 'user')]
    #[IsGranted('ROLE_ADMIN')]
    #[Route('/user/delete/{id}', name: 'delete_user', methods: ['DELETE'])]
    public function deleteUser(int $id, UserRepository $userRepository,  EntityManagerInterface $entityManager): JsonResponse
    {
        $user = $userRepository->find($id);

        if (!$user) {
            return new JsonResponse(['error' => 'Utilisateur non trouvé'], 404);
        }

        $entityManager->remove($user);
        $entityManager->flush();

        return new JsonResponse(['message' => 'Utilisateur supprimé avec succès'], 200);
    }
}
