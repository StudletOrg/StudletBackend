<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use App\Entity\User;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface; // Import loggera

class ApiRegistrationController extends AbstractController
{
    #[Route('/api/register', name: 'api_register', methods: ['POST'])]
    public function register(Request $request, EntityManagerInterface $entityManager, ValidatorInterface $validator, LoggerInterface $logger): JsonResponse
    {
        $logger->warning('Odpalam akcje rejestracji');
        $data = json_decode($request->getContent(), true);

        // Walidacja danych
        if (empty($data['email']) || empty($data['password'])) {
            $logger->warning('Registration attempt with missing email or password', [
                'data' => $data
            ]);
            return new JsonResponse(['message' => 'Nazwa użytkowika i hasło są wymagane'], 400);
        }

        // Sprawdzenie, czy użytkownik już istnieje
        $existingUser = $entityManager->getRepository(User::class)->findOneBy(['email' => $data['email']]);
        if ($existingUser) {
            $logger->warning('Registration attempt with existing email', [
                'email' => $data['email']
            ]);
            return new JsonResponse(['message' => 'Użytkownik '.$data['email'].' już istnieje'], 409); // Conflict
        }

        // Tworzenie nowego użytkownika
        $user = new User();
        $user->setEmail($data['email']);
        $user->setPassword(password_hash($data['password'], PASSWORD_BCRYPT)); // Haszowanie hasła
        $logger->info('Creating a new user', [
            'email' => $data['email']
        ]);

        // Walidacja użytkownika
        $errors = $validator->validate($user);
        if (count($errors) > 0) {
            $logger->error('Validation failed', [
                'errors' => (string) $errors,
                'data' => $data
            ]);
            return new JsonResponse(['message' => (string) $errors], 400); // Invalid input
        }

        // Zapisz użytkownika
        try {
            $entityManager->persist($user);
            $entityManager->flush();
            $logger->info('User registered successfully', [
                'email' => $data['email']
            ]);
        } catch (\Exception $e) {
            $logger->error('Error occurred while registering user', [
                'exception' => $e->getMessage()
            ]);
            return new JsonResponse(['message' => 'Wystąpił błąd podczas tworzenia użytkownika.'], 500); // Internal server error
        }

        return new JsonResponse(['message' => 'Rejsestraca zakończona sukcesem'], 201);
    }
}