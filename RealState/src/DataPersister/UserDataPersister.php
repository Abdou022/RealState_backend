<?php

namespace App\DataPersister;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class UserDataPersister implements ProcessorInterface
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private UserPasswordHasherInterface $passwordHasher
    ) {}

    // prendre plain_password dans body request faire le hachage et enregistrer dans password et puis plain_password= ''
    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = [])
    {
        if ($data instanceof User) {

            if ($data->getPlainPassword()) {
                $hashedPassword = $this->passwordHasher->hashPassword(
                    $data,
                    $data->getPlainPassword()
                );
                $data->setPassword($hashedPassword);
                $data->setPlainPassword('');
            }

            $this->entityManager->persist($data);
            $this->entityManager->flush();
        }

        return $data;
    }
}
