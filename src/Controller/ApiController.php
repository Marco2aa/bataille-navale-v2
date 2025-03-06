<?php
// src/Controller/ApiController.php
namespace App\Controller;

use App\Entity\BoardCase;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class ApiController extends AbstractController
{
    #[Route('/api/hit', name: 'api_hit', methods: ['POST'])]
    public function hit(Request $request, EntityManagerInterface $em): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $x = $data['x'] ?? null;
        $y = $data['y'] ?? null;
        $plateauId = $data['plateauId'] ?? null;

        if ($x === null || $y === null || $plateauId === null) {
            return new JsonResponse(['error' => 'Missing parameters'], 400);
        }

        // Recherche de la case du plateau selon ses coordonnées et l'identifiant du plateau
        $boardCaseRepo = $em->getRepository(BoardCase::class);
        $boardCase = $boardCaseRepo->findOneBy([
            'plateau' => $plateauId,
            'x' => $x,
            'y' => $y,
        ]);

        if (!$boardCase) {
            return new JsonResponse(['error' => 'Case not found'], 404);
        }

        if ($boardCase->isEstTouche()) {
            return new JsonResponse(['error' => 'Already hit'], 400);
        }

        // Marquer la case comme touchée
        $boardCase->setEstTouche(true);
        $em->persist($boardCase);

        $hit = false;
        $shipSunk = false;
        $message = "Miss";

        // Si la case contient un navire, c'est un touché
        $ship = $boardCase->getNavire();
        if ($ship) {
            $hit = true;
            $message = "Hit";

            // Réduire les points de vie du navire
            $ship->setPointsDeVie($ship->getPointsDeVie() - 1);
            if ($ship->getPointsDeVie() <= 0) {
                $ship->setEstCoule(true);
                $shipSunk = true;
                $message = "Ship sunk!";
            }
            $em->persist($ship);
        }

        $em->flush();

        return new JsonResponse([
            'hit' => $hit,
            'shipSunk' => $shipSunk,
            'message' => $message,
        ]);
    }
}
