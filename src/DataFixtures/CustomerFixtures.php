<?php

namespace App\DataFixtures;

use App\Entity\Customer;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ObjectManager;

class CustomerFixtures extends Fixture
{
    private EntityManagerInterface $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    public function load(ObjectManager $manager)
    {
        // Password AustinG1234*
        $this->addCustomer('Austin Goff',
            '$2y$10$gHSoGl6c2U20UbfZsWA4IeTFM3QIuhtBQEbmScN3q9wBBJYEASk7m',
            'austinGoff@hotmail.com'
        );
        // Password NitaA1234*
        $this->addCustomer('Nita Atkins',
            '$2y$10$zjVOWCPvzVVdsBadK2/G8uazVX8n5V2FZGEpuC3K42fQGmuxCGnDy',
            'nita.atkins5147@gmail.com'
        );
        // Password ShelbyM1234*
        $this->addCustomer(
            'Shelby Mathews',
            '$2y$10$v4PKX3M.1tAJUd9n4Qhzi.CaRZokJcObGWSMw2r5sR.Gc5ybEvKvG',
            'shelbyMathews789@gmail.com)'
        );

        $manager->flush();
    }

    private function addCustomer(string $name, string $password, string $email)
    {
        $customer = new Customer();
        $customer->setName($name);
        $customer->setPassword($password);
        $customer->setEmail($email);
        $customer->setRoles(['ROLE_CUSTOMER']);
        $customer->setCreationDate(new \DateTime());

        $this->entityManager->persist($customer);
    }
}
