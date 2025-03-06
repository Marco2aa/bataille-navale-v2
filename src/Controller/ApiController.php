<?php
// src/Controller/ApiController.php
namespace App\Controller;

use App\Entity\Plateau;
use App\Entity\Ship;
use App\Service\GameRulesService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class ApiController extends AbstractController
{
    #[Route('/api/place-ships', name: 'api_place_ships', methods: ['POST'])]
    public function placeShips(
        Request $request,
        EntityManagerInterface $em,
        GameRulesService $gameRulesService
    ): JsonResponse {
        $data = json_decode($request->getContent(), true);

        $playerPlateauId = $data['playerPlateauId'] ?? null;
        $opponentPlateauId = $data['opponentPlateauId'] ?? null;
        $playerShips = $data['playerShips'] ?? [];
        $opponentShips = $data['opponentShips'] ?? [];

        if (!$playerPlateauId) {
            return new JsonResponse(['success' => false, 'message' => 'Player plateau ID manquant.'], 400);
        }

        // Récupérer le plateau du joueur via le repository
        $playerPlateau = $em->getRepository(Plateau::class)->find($playerPlateauId);
        if (!$playerPlateau) {
            return new JsonResponse(['success' => false, 'message' => 'Plateau du joueur non trouvé.'], 404);
        }

        // Vérifier le placement sur le plateau du joueur
        if (!$gameRulesService->isShipPlacementValid($playerPlateau, $playerShips)) {
            return new JsonResponse(['success' => false, 'message' => 'Placement invalide pour le joueur.'], 400);
        }

        // Créer un navire pour le joueur et définir ses propriétés
        $ship = new Ship();
        $ship->setType("Destroyer");
        $ship->setPoints(100);
        // La longueur du navire correspond au nombre de cases placées
        $ship->setPointsDeVie(count($playerShips));
        $ship->setEstCoule(false);
        $ship->setPlateau($playerPlateau);

        // Appliquer le placement via le service
        $placed = $gameRulesService->placeShip($playerPlateau, $ship, $playerShips);
        if (!$placed) {
            return new JsonResponse(['success' => false, 'message' => 'Erreur lors du placement du navire pour le joueur.'], 400);
        }
        $em->persist($ship);

        // Optionnel : Si l'adversaire a également défini ses placements, on les traite
        if ($opponentPlateauId && !empty($opponentShips)) {
            $opponentPlateau = $em->getRepository(Plateau::class)->find($opponentPlateauId);
            if (!$opponentPlateau) {
                return new JsonResponse(['success' => false, 'message' => "Plateau de l'adversaire non trouvé."], 404);
            }
            if (!$gameRulesService->isShipPlacementValid($opponentPlateau, $opponentShips)) {
                return new JsonResponse(['success' => false, 'message' => "Placement invalide pour l'adversaire."], 400);
            }

            $opponentShip = new Ship();
            $opponentShip->setType("Destroyer");
            $opponentShip->setPoints(100);
            $opponentShip->setPointsDeVie(count($opponentShips));
            $opponentShip->setEstCoule(false);
            $opponentShip->setPlateau($opponentPlateau);

            $placedOpponent = $gameRulesService->placeShip($opponentPlateau, $opponentShip, $opponentShips);
            if (!$placedOpponent) {
                return new JsonResponse(['success' => false, 'message' => "Erreur lors du placement du navire pour l'adversaire."], 400);
            }
            $em->persist($opponentShip);
        }

        $em->flush();

        return new JsonResponse(['success' => true, 'message' => 'Placements enregistrés.']);
    }
}
