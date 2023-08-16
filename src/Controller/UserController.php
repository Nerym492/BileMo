<?php

namespace App\Controller;

use App\Entity\User;
use App\Repository\CustomerRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use JMS\Serializer\SerializationContext;
use JMS\Serializer\SerializerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class UserController extends AbstractController
{
    public function __construct(
        private UserRepository $userRepository,
        private CustomerRepository $customerRepository,
        private EntityManagerInterface $entityManager,
        private Security $security,
        private SerializerInterface $serializer
    ) {
    }

    #[Route('/api/users', name: 'users', methods: ['GET'])]
    #[IsGranted('ROLE_CUSTOMER', message: 'You must be logged in to view the list of users.')]
    public function getUserList(): JsonResponse
    {
        $users = $this->userRepository->findByCustomer($this->security->getUser()->getUserIdentifier());

        $context = SerializationContext::create()->setGroups(['getUsers']);
        $jsonUsers = $this->serializer->serialize($users, 'json', $context);

        return new JsonResponse($jsonUsers, Response::HTTP_OK, [], true);
    }

    #[Route('api/users', name: 'createUser', methods: ['POST'])]
    #[isGranted('ROLE_CUSTOMER', message: 'You must be logged in to create a new user.')]
    public function addUser(Request $request, ValidatorInterface $validator): JsonResponse
    {
        $user = $this->serializer->deserialize($request->getContent(), User::class, 'json');
        $errors = $validator->validate($user);

        if ($errors->count() > 0) {
            return new JsonResponse($this->serializer->serialize($errors, 'json'));
        }

        // Link the logged customer to the created user
        $loggedCustomerMail = $this->security->getUser()->getUserIdentifier();
        $loggedCustomer = $this->customerRepository->findOneBy(['email' => $loggedCustomerMail]);
        $user->setCustomer($loggedCustomer);

        $this->entityManager->persist($user);
        $this->entityManager->flush();

        $context = SerializationContext::create()->setGroups('getUsers');
        $jsonUser = $this->serializer->serialize($user, 'json', $context);

        return new JsonResponse($jsonUser, Response::HTTP_CREATED, [], true);
    }
}
