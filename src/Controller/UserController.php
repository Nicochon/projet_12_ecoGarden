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

final class UserController extends AbstractController
{
    #[Route('/user', methods: ['POST'])]
    public function createUser(Request $request, UserPasswordHasherInterface $passwordHasher, EntityManagerInterface $entityManager, UserRepository $userRepository): JsonResponse
    {
        try {
            $data = json_decode($request->getContent(), true);

            $requiredFields = ['email', 'password', 'city', 'pseudo'];
            foreach ($requiredFields as $field) {
                if (empty($data[$field])) {
                    return new JsonResponse(['error' => "Le champ '$field' est obligatoire"], 400);
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
            return new JsonResponse(['error' => 'Erreur interne', 'details' => $e->getMessage()], 500);
        }
    }

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

                if ($data['email'] !== $user->getEmail()) {
                    $existingUser = $userRepository->findOneBy(['email' => $data['email']]);
                    if ($existingUser && $existingUser->getId() !== $user->getId()) {
                        return new JsonResponse(['error' => 'Cet email est déjà utilisé'], 400);
                    }
                }
                if ($data['pseudo'] !== $user->getPseudo()) {
                    $existingPseudo = $userRepository->findOneBy(['pseudo' => $data['pseudo']]);
                    if ($existingPseudo && $existingPseudo->getId() !== $user->getId()) {
                        return new JsonResponse(['error' => 'Ce pseudo est déjà pris'], 400);
                    }
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

                return new JsonResponse(['message' => 'Utilisateur modifier avec succès'], 200);
            }


        } catch (\Exception $e) {
            return new JsonResponse(['error' => 'Une erreur interne est survenue', 'details' => $e->getMessage()], 500);
        }
    }

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
