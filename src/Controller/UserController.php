<?php

namespace App\Controller;

use App\Entity\Customer;
use App\Entity\User;
use App\Repository\CustomerRepository;
use App\Repository\UserRepository;
use JMS\Serializer\SerializationContext;
use JMS\Serializer\SerializerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

class UserController extends AbstractController
{
    public function __construct(
        private UserRepository $userRepository,
        private CustomerRepository $customerRepository,
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
}
