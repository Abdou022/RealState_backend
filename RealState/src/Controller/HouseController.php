<?php

namespace App\Controller;

use App\Entity\House;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Uid\Uuid;
use Symfony\Component\HttpFoundation\File\Exception\FileException;

class HouseController extends AbstractController
{
    // ================================
    // 1️⃣ CREATE HOUSE (multipart/form-data)
    // ================================
    
    public function createHouse(
        Request $req,
        EntityManagerInterface $em
    ): JsonResponse {

        // Récupération form-data
        $ownerId     = $req->request->get('ownerId');
        $title       = $req->request->get('title');
        $description = $req->request->get('description');
        $price       = $req->request->get('price');
        $address     = $req->request->get('address');
        $surface     = $req->request->get('surface');
        $rooms       = $req->request->get('rooms');
        $image       = $req->files->get('image');

        if (!$ownerId) {
            return $this->json(['error' => 'ownerId is required'], 400);
        }

        // Vérifier owner
        $owner = $em->getRepository(User::class)->find($ownerId);
        if (!$owner) {
            return $this->json(['error' => 'Owner not found'], 404);
        }

        // Upload image (si fournie)
        $filename = null;
        if ($image) {
            $uploadDir = $this->getParameter('kernel.project_dir') . '/public/uploads/images/';

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
        }

        // Création House
        $house = new House();
        $house->setTitle($title);
        $house->setDescription($description);
        $house->setPrice($price);
        $house->setAddress($address);
        $house->setSurface($surface);
        $house->setRooms($rooms);
        $house->setImage($filename);
        $house->setOwner($owner);

        $em->persist($house);
        $em->flush();

        return $this->json([
            'message' => 'House created successfully',
            'id' => $house->getId()
        ]);
    }

    // ================================
    // 2️⃣ GET ALL HOUSES
    // ================================
    
    public function getAllHouses(EntityManagerInterface $em): JsonResponse
    {
        $houses = $em->getRepository(House::class)->findAll();
        $data = [];

        foreach ($houses as $h) {
            $data[] = [
                'id' => $h->getId(),
                'title' => $h->getTitle(),
                'price' => $h->getPrice(),
                'address' => $h->getAddress(),
                'surface' => $h->getSurface(),
                'rooms' => $h->getRooms(),
                'image' => $h->getImage(),
                'ownerId' => $h->getOwner()->getId(),
                'ownerName' => $h->getOwner()->getFullName(),
            ];
        }

        return $this->json($data);
    }

    public function getLastHouses(EntityManagerInterface $em): JsonResponse
{
    $houses = $em->getRepository(House::class)->findBy(
        [],                   // critères (vide = toutes)
        ['createdAt' => 'DESC'], // tri décroissant par date
        4                     // limite = 4
    );

    $data = [];
    foreach ($houses as $h) {
        $data[] = [
            'id' => $h->getId(),
            'title' => $h->getTitle(),
            'price' => $h->getPrice(),
            'address' => $h->getAddress(),
            'surface' => $h->getSurface(),
            'rooms' => $h->getRooms(),
            'image' => $h->getImage(),
            'ownerId' => $h->getOwner()->getId(),
            'ownerName' => $h->getOwner()->getFullName(),
        ];
    }

    return new JsonResponse($data);
}

    // ================================
    // 3️⃣ GET HOUSE DETAILS
    // ================================
    
    public function getHouseDetails(House $house): JsonResponse
    {
        return $this->json([
            'id' => $house->getId(),
            'title' => $house->getTitle(),
            'description' => $house->getDescription(),
            'price' => $house->getPrice(),
            'address' => $house->getAddress(),
            'surface' => $house->getSurface(),
            'rooms' => $house->getRooms(),
            'image' => $house->getImage(),
            'ownerId' => $house->getOwner()->getId(),
            'ownerName' => $house->getOwner()->getFullName(),
        ]);
    }

    // ================================
    // 4️⃣ UPDATE HOUSE (multipart/form-data)
    // ================================
    
    public function updateHouse(
    Request $req,
    EntityManagerInterface $em,
    $id
): JsonResponse {

    $house = $em->getRepository(House::class)->find($id);
    if (!$house) {
        return $this->json(['error' => 'House not found'], 404);
    }

    // Récupération form-data
    if ($req->request->has('title'))       $house->setTitle($req->request->get('title'));
    if ($req->request->has('description')) $house->setDescription($req->request->get('description'));
    if ($req->request->has('price'))       $house->setPrice($req->request->get('price'));
    if ($req->request->has('address'))     $house->setAddress($req->request->get('address'));
    if ($req->request->has('surface'))     $house->setSurface($req->request->get('surface'));
    if ($req->request->has('rooms'))       $house->setRooms($req->request->get('rooms'));

    // Upload nouvelle image
    $newImage = $req->files->get('image');
    if ($newImage) {
        $uploadDir = $this->getParameter('kernel.project_dir') . '/public/uploads/images/';
        if (!is_dir($uploadDir)) mkdir($uploadDir, 0775, true);
        $filename = Uuid::v4()->toRfc4122() . '.' . $newImage->guessExtension();
        try {
            $newImage->move($uploadDir, $filename);
        } catch (FileException $e) {
            return $this->json(['error' => 'Image upload failed', 'details' => $e->getMessage()], 500);
        }
        $house->setImage($filename);
    }

    $em->flush();

    return $this->json(['message' => 'House updated successfully']);
}


    // ================================
    // 5️⃣ DELETE HOUSE
    // ================================
    
    public function deleteHouse(
        House $house,
        EntityManagerInterface $em
    ): JsonResponse {

        $em->remove($house);
        $em->flush();

        return $this->json(['message' => 'House deleted successfully']);
    }
}
