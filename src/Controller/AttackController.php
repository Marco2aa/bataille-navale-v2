<?php
// src/Controller/AttackController.php
namespace App\Controller;

use App\Entity\Game;
use App\Entity\Plateau;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class AttackController extends AbstractController
{
    #[Route('/api/attack', name: 'api_attack', methods: ['POST'])]
    public function attack(Request $request, EntityManagerInterface $em): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $gameId = $data['gameId'] ?? null;
        $opponentPlateauId = $data['opponentPlateauId'] ?? null;
        $coordinate = $data['coordinate'] ?? null;

        if (!$gameId || !$opponentPlateauId || !$coordinate) {
            return new JsonResponse(['success' => false, 'message' => 'Données manquantes pour l\'attaque.'], 400);
        }

        // Récupérer le jeu
        $game = $em->getRepository(Game::class)->find($gameId);
        if (!$game) {
            return new JsonResponse(['success' => false, 'message' => 'Jeu non trouvé.'], 404);
        }

        // Récupérer le plateau adverse
        $opponentPlateau = $em->getRepository(Plateau::class)->find($opponentPlateauId);
        if (!$opponentPlateau) {
            return new JsonResponse(['success' => false, 'message' => 'Plateau adverse non trouvé.'], 404);
        }

        $x = (int)$coordinate['x'];
        $y = (int)$coordinate['y'];
        $hit = false;
        $hitShip = null;
        // Parcourir les cases du plateau adverse
        foreach ($opponentPlateau->getBoardCases() as $boardCase) {
            if ((int)$boardCase->getX() === $x && (int)$boardCase->getY() === $y) {
                if ($boardCase->getNavire() !== null) {
                    $hit = true;
                    $hitShip = $boardCase->getNavire();
                    $boardCase->setEstTouche(true);
                }
                break;
            }
        }

        if ($hit && $hitShip !== null) {
            $allTouched = true;
            foreach ($hitShip->getBoardCases() as $case) {
                if (!$case->isEstTouche()) {
                    $allTouched = false;
                    break;
                }
            }
            if ($allTouched) {
                $hitShip->setEstCoule(true);
                $em->flush();
                return new JsonResponse(['success' => true, 'message' => "Touché, navire coulé: " . $hitShip->getType()], 200);
            }
            $em->flush();
            return new JsonResponse(['success' => true, 'message' => "Touché sur un " . $hitShip->getType()], 200);
        }
        $em->flush();
        return new JsonResponse(['success' => true, 'message' => "Manqué."], 200);
    }
}
