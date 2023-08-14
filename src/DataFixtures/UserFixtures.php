<?php

namespace App\DataFixtures;

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
        $this->addUser('Carol', 'Jackson', 'carolJackson12@gmail.com');
        $this->addUser('Lacy', 'Maynard', 'lacy.mayard786@hotmail.com');
        $this->addUser('Jason', 'Turner', 'jason-turner@gmail.com');
        $manager->flush();
    }

    private function addUser(string $firstName, string $lastName, string $email)
    {
        $user = new User();
        $user->setFirstName($firstName);
        $user->setLastName($lastName);
        $user->setEmail($email);
        $this->entityManager->persist($user);
    }
}
