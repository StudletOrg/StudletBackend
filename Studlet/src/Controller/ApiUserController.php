<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;

final class ApiUserController extends AbstractController
{
    #[Route('/user/{id}', name: 'app_api_user_show', methods: ['GET'])]
    public function show(int $id, UserRepository $userRepository): JsonResponse
    {
        $user = $userRepository->find($id);

        if (!$user) {
            throw new NotFoundHttpException('User not found.');
        }

        $data = [
            'id' => $user->getId(),
            'email' => $user->getEmail(),
            'firstName' => $user->getFirstName(),
            'lastName' => $user->getLastName(),
            'roles' => $user->getRoles(),
        ];

        return $this->json($data);
    }

    #[Route('/user/{id}/details', name: 'app_api_user_show_details', methods: ['GET'])]
    public function showDetails(int $id, UserRepository $userRepository): JsonResponse
    {
        $user = $userRepository->find($id);

        if (!$user) {
            throw new NotFoundHttpException('User not found.');
        }

        $groupsAsStudent = [];
        foreach ($user->getGroupsOfStudents() as $group) {
            $groupsAsStudent[] = [
                'id' => $group->getId(),
            ];
        }

        $groupsAsProfessor = [];
        foreach ($user->getGroupsOfProfessor() as $group) {
            $groupsAsProfessor[] = [
                'id' => $group->getId(),
            ];
        }

        $data = [
            'id' => $user->getId(),
            'email' => $user->getEmail(),
            'firstName' => $user->getFirstName(),
            'lastName' => $user->getLastName(),
            'dateOfBirth' => $user->getDateOfBirth()?->format('Y-m-d'),
            'roles' => $user->getRoles(),
            'groupsAsStudent' => $groupsAsStudent,
            'groupsAsProfessor' => $groupsAsProfessor,
        ];

        return $this->json($data);
    }

    #[Route('/api/user/current_user', name: 'api_current_user', methods: ['GET'])]
    public function getMyGrades(UserRepository $userRepository): JsonResponse {
        $user = $this->getUser();

        if (!$user) {
            throw new AccessDeniedHttpException('Brak dostępu - użytkownik niezalogowany.');
        }

        $info = $userRepository->findBy(
            ['id' => $user]
        );


        $data = [
            'id' => $user->getId(),
            'firstname' => $user->getFirstName(),
            'lastname' => $user->getLastName(),
            'dateofbirth' => $user->getDateOfBirth(),
            'email' => $user->getEmail(),
            'roles' => $user->getRoles(),
        ];
        return $this->json($data);
    }
    #[Route('/api/user/my_groups', name: 'api_my_groups', methods: ['GET'])]
    public function getMyGroups(UserRepository $userRepository): JsonResponse {
        $user = $this->getUser();

        if (!$user) {
            throw new AccessDeniedHttpException('Brak dostępu - użytkownik niezalogowany.');
        }

        $groups = $user->getGroupsOfProfessor();

        foreach ($groups as $g) {
            $data[] = [
                'id' => $g->getId(),
                'numer' => $g->getNumer(),
                'name' => $g->getSubjectOfIntance()->getSubject()->getName(),
            ];
        }
        return $this->json($data);
    }

    #[Route('/api/allstudents', name: 'api_all_students', methods: ['GET'])]
    public function getAllStudents(UserRepository $userRepository): JsonResponse
    {
        $allUsers = $userRepository->findAll();

        $students = [];

        foreach ($allUsers as $user) {
            $students[] = [
                'id' => $user->getId(),
                'firstName' => $user->getFirstName(),
                'lastName' => $user->getLastName(),
                'email' => $user->getEmail(),
            ];
        }
        return $this->json($students);
    }

    #[Route('/api/user/{id}', name: 'api_update_user', methods: ['PUT'])]
    public function updateUser(int $id, Request $request, UserRepository $userRepository, EntityManagerInterface $entityManager): JsonResponse {
        $user = $userRepository->find($id);

        if (!$user) {
            throw new NotFoundHttpException('Użytkownik nie znaleziony.');
        }

        $data = json_decode($request->getContent(), true);

        if (isset($data['firstname'])) {
            $user->setFirstName($data['firstname']);
        }

        if (isset($data['lastname'])) {
            $user->setLastName($data['lastname']);
        }

        if (!empty($data['password'])) {
            $user->setPassword(password_hash($data['password'], PASSWORD_BCRYPT)); // Haszowanie hasła
        }
        $entityManager->flush();

        return $this->json([
            'message' => 'Dane użytkownika zostały zaktualizowane.',
            'id' => $user->getId(),
            'firstName' => $user->getFirstName(),
            'lastName' => $user->getLastName(),
            'email' => $user->getEmail()
        ]);
    }

}