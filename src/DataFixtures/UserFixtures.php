<?php

namespace App\DataFixtures;

use App\Entity\Customer;
use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ObjectManager;

class UserFixtures extends Fixture implements DependentFixtureInterface
{
    private EntityManagerInterface $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    public function getDependencies()
    {
        return [
            CustomerFixtures::class,
        ];
    }

    public function load(ObjectManager $manager)
    {
        $customerRepository = $manager->getRepository(Customer::class);
        $customer1 = $customerRepository->findOneBy(['name' => 'Austin Goff']);
        $customer2 = $customerRepository->findOneBy(['name' => 'Nita Atkins']);

        $this->addUser('Carol', 'Jackson', 'carolJackson12@gmail.com', $customer2);
        $this->addUser('Lacy', 'Maynard', 'lacy.mayard786@hotmail.com', $customer2);
        $this->addUser('Jason', 'Turner', 'jason-turner@gmail.com', $customer1);
        $manager->flush();
    }

    private function addUser(string $firstName, string $lastName, string $email, Customer $customer)
    {
        $user = new User();
        $user->setFirstName($firstName);
        $user->setLastName($lastName);
        $user->setEmail($email);
        $user->setCustomer($customer);

        $this->entityManager->persist($user);
    }
}
