<?php

namespace App\Entity;

use App\Repository\UserRepository;
use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation\Groups;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: UserRepository::class)]
#[ORM\Table(name: '`user`')]
#[UniqueEntity(fields: ['email'], message: 'The email {{ value }} is already used.')]
class User
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['getUsers'])]
    private ?int $id = null;

    #[ORM\Column(length: 50)]
    #[Assert\NotBlank(message: "The first name can't be empty")]
    #[Assert\Length(
        min: 3,
        max: 50,
        minMessage: 'The first name must be at least 3 characters long',
        maxMessage: 'The first name must not exceed 50 characters.'
    )]
    #[Groups(['getUsers'])]
    private ?string $firstName = null;

    #[ORM\Column(length: 50)]
    #[Assert\NotBlank(message: "The last name can't be empty")]
    #[Assert\Length(
        min: 3,
        max: 50,
        minMessage: 'The last name must be at least {{ limit }} characters long',
        maxMessage: 'The last name must not exceed {{ limit }} characters.'
    )]
    #[Groups(['getUsers'])]
    private ?string $lastName = null;

    #[ORM\Column(length: 180)]
    #[Assert\NotBlank(message: "The email can't be empty.")]
    #[Assert\Email(message: '{{ value }} is not a valid email.')]
    #[Groups(['getUsers'])]
    private ?string $email = null;

    #[ORM\ManyToOne(inversedBy: 'users')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Customer $customer = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getFirstName(): ?string
    {
        return $this->firstName;
    }

    public function setFirstName(string $firstName): static
    {
        $this->firstName = $firstName;

        return $this;
    }

    public function getLastName(): ?string
    {
        return $this->lastName;
    }

    public function setLastName(string $lastName): static
    {
        $this->lastName = $lastName;

        return $this;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(string $email): static
    {
        $this->email = $email;

        return $this;
    }

    public function getCustomer(): ?Customer
    {
        return $this->customer;
    }

    public function setCustomer(?Customer $customer): static
    {
        $this->customer = $customer;

        return $this;
    }
}
