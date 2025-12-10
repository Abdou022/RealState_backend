<?php

namespace App\Controller;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Uid\Uuid;
use Symfony\Component\HttpFoundation\File\Exception\FileException;

class UserController extends AbstractController
{
    public function register(
        Request $request,
        EntityManagerInterface $em,
        UserPasswordHasherInterface $passwordHasher
    ): JsonResponse {

        $name     = $request->request->get('fullName');
        $email    = $request->request->get('email');
        $phone    = $request->request->get('phone');
        $password = $request->request->get('plain_password');
        $image    = $request->files->get('image');

        // 1. Vérification des champs obligatoires
        if (!$name || !$email || !$phone || !$password || !$image) {
            return $this->json(['error' => 'All fields are required'], 400);
        }

        // 2. Vérifier si l'utilisateur existe déjà
        if ($em->getRepository(User::class)->findOneBy(['email' => $email])) {
            return $this->json(['error' => 'Email already exists'], 409);
        }

        // 3. Gestion de l’upload de l'image
        $uploadDir = $this->getParameter('kernel.project_dir') . '/public/uploads/profiles/';

        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0775, true);
        }

        $filename = Uuid::v4()->toRfc4122() . '.' . $image->guessExtension();

        try {
            $image->move($uploadDir, $filename);
        } catch (FileException $e) {
            return $this->json([
                'error' => 'Image upload failed',
                'details' => $e->getMessage()
            ], 500);
        }

        // 4. Création de l'user
        $user = new User();
        $user->setFullName($name);
        $user->setEmail($email);
        $user->setPhone($phone);
        $user->setRoles(['ROLE_USER']);
        $user->setImage($filename);

        // 5. Hash Password
        $hashedPassword = $passwordHasher->hashPassword($user, $password);
        $user->setPassword($hashedPassword);

        // 6. Save user
        $em->persist($user);
        $em->flush();

        // 7. Retour JSON
        return $this->json([
            'message' => 'Account created successfully!',
            'id' => $user->getId(),
            'fullName' => $user->getFullName(),
            'email' => $user->getEmail(),
            'phone' => $user->getPhone(),
            'image' => $user->getImage()
        ]);
    }

    //http://127.0.0.1:8000/users/login POST
public function login(
    Request $request,
    EntityManagerInterface $em,
    UserPasswordHasherInterface $passwordHasher
): JsonResponse {

    $email    = $request->request->get('email');
    $password = $request->request->get('password');

    if (!$email || !$password) {
        return $this->json(['error' => 'Email and password are required'], 400);
    }

    // 1. Vérifier user
    $user = $em->getRepository(User::class)->findOneBy(['email' => $email]);
    if (!$user) {
        return $this->json(['error' => 'Email Invalide'], 401);
    }

    // 2. Vérifier mot de passe
    if (!$passwordHasher->isPasswordValid($user, $password)) {
        return $this->json(['error' => 'Mot de Passe Invalide'], 401);
    }


    // 3. Construire les infos user
    $userData = [
        'id' => $user->getId(),
        'fullName' => $user->getFullName(),
        'email' => $user->getEmail(),
        'phone' => $user->getPhone(),
        'image' => $user->getImage()
    ];

    return $this->json([
        'message' => 'Login successful',
        'user'    => $userData
    ]);
}

    //http://127.0.0.1:8000/users/{id}
    public function getUserProfile(User $user): JsonResponse
    {
        return $this->json([
            'id' => $user->getId(),
            'fullName' => $user->getFullName(),
            'email' => $user->getEmail(),
            'phone' => $user->getPhone(),
            'image' => $user->getImage()
        ]);
    }

    //http://127.0.0.1:8000/users/{id}  PUT
    public function updateProfile(
        Request $req,
        User $user,
        EntityManagerInterface $em,
        UserPasswordHasherInterface $hasher
    ): JsonResponse {
        $data = json_decode($req->getContent(), true);

        if (isset($data['fullName'])) $user->setFullName($data['fullName']);
        if (isset($data['phone'])) $user->setPhone($data['phone']);

        if (isset($data['plain_password']) && !empty($data['plain_password'])) {
            $hashed = $hasher->hashPassword($user, $data['plain_password']);
            $user->setPassword($hashed);
        }

        $em->flush();

        return $this->json(['message' => 'User updated successfully']);
    }
}
