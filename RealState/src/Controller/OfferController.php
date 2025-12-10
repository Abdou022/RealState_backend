<?php

namespace App\Controller;

use App\Entity\Offer;
use App\Entity\House;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class OfferController extends AbstractController
{
    // ================================
    // 1️⃣ Create Offer (Interest a house)
    // ================================
    public function createOffer(
        Request $req,
        EntityManagerInterface $em
    ): JsonResponse {
        $data = json_decode($req->getContent(), true);

        if (!isset($data['houseId'], $data['applicantId'])) {
            return $this->json(['error' => 'houseId and applicantId are required'], 400);
        }

        $house = $em->getRepository(House::class)->find($data['houseId']);
        if (!$house) {
            return $this->json(['error' => 'House not found'], 404);
        }

        $applicant = $em->getRepository(User::class)->find($data['applicantId']);
        if (!$applicant) {
            return $this->json(['error' => 'Applicant not found'], 404);
        }

        $offer = new Offer();
        $offer->setHouse($house);
        $offer->setApplicant($applicant);
        $offer->setStatus('pending'); // par défaut

        $em->persist($offer);
        $em->flush();

        return $this->json([
            'message' => 'Offer created successfully',
            'offerId' => $offer->getId()
        ]);
    }

    // ================================
    // 2️⃣ Received Offers (Seller)
    // ================================
    public function receivedOffers(
        int $ownerId,
        EntityManagerInterface $em
    ): JsonResponse {
        $offers = $em->getRepository(Offer::class)->findBy([
            'creator' => $ownerId
        ]);

        $data = [];
        foreach ($offers as $o) {
            $data[] = [
                'id' => $o->getId(),
                'status' => $o->getStatus(),
                'houseId' => $o->getHouse()->getId(),
                'houseTitle' => $o->getHouse()->getTitle(),
                'applicantId' => $o->getApplicant()->getId(),
                'applicantName' => $o->getApplicant()->getFullName(),
                'createdAt' => $o->getCreatedAt()->format('Y-m-d H:i:s')
            ];
        }

        return $this->json($data);
    }

    // ================================
    // 3️⃣ Sent Offers (Buyer)
    // ================================
    public function sentOffers(
        int $applicantId,
        EntityManagerInterface $em
    ): JsonResponse {
        $offers = $em->getRepository(Offer::class)->findBy([
            'applicant' => $applicantId
        ]);

        $data = [];
        foreach ($offers as $o) {
            $data[] = [
                'id' => $o->getId(),
                'status' => $o->getStatus(),
                'houseId' => $o->getHouse()->getId(),
                'houseTitle' => $o->getHouse()->getTitle(),
                'creatorId' => $o->getCreator()->getId(),
                'creatorName' => $o->getCreator()->getFullName(),
                'createdAt' => $o->getCreatedAt()->format('Y-m-d H:i:s')
            ];
        }

        return $this->json($data);
    }

    // ================================
    // 4️⃣ Accept Offer
    // ================================
    public function acceptOffer(
        int $offerId,
        EntityManagerInterface $em
    ): JsonResponse {
        $offer = $em->getRepository(Offer::class)->find($offerId);
        if (!$offer) {
            return $this->json(['error' => 'Offer not found'], 404);
        }

        $offer->setStatus('approved');
        $em->flush();

        return $this->json(['message' => 'Offer accepted successfully']);
    }

    // ================================
    // 5️⃣ Reject Offer
    // ================================
    public function rejectOffer(
        int $offerId,
        EntityManagerInterface $em
    ): JsonResponse {
        $offer = $em->getRepository(Offer::class)->find($offerId);
        if (!$offer) {
            return $this->json(['error' => 'Offer not found'], 404);
        }

        $offer->setStatus('rejected');
        $em->flush();

        return $this->json(['message' => 'Offer rejected successfully']);
    }
}
