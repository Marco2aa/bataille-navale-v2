<?php
// src/Controller/ApiController.php
namespace App\Controller;

use App\Entity\Game;
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

        $gameId = $data['gameId'] ?? null;
        $playerPlateauId = $data['playerPlateauId'] ?? null;
        $playerShipsData = $data['playerShips'] ?? []; // maintenant, tableau d'objets
        $opponentPlateauId = $data['opponentPlateauId'] ?? null;
        $opponentShipsData = $data['opponentShips'] ?? [];

        error_log("Payload reçu: " . print_r($data, true));

        if (!$gameId) {
            return new JsonResponse(['success' => false, 'message' => 'Game ID manquant.'], 400);
        }
        if (!$playerPlateauId) {
            return new JsonResponse(['success' => false, 'message' => 'Player plateau ID manquant.'], 400);
        }

        // Récupérer le jeu
        $game = $em->getRepository(Game::class)->find($gameId);
        if (!$game) {
            error_log("Jeu non trouvé pour gameId: $gameId");
            return new JsonResponse(['success' => false, 'message' => 'Jeu non trouvé.'], 404);
        }

        // Récupérer le plateau du joueur
        $playerPlateau = $em->getRepository(Plateau::class)->find($playerPlateauId);
        if (!$playerPlateau) {
            error_log("Plateau du joueur non trouvé pour plateauId: $playerPlateauId");
            return new JsonResponse(['success' => false, 'message' => 'Plateau du joueur non trouvé.'], 404);
        }

        // Traiter les navires du joueur
        foreach ($playerShipsData as $shipData) {
            $type = $shipData['type'] ?? 'Destroyer';
            $coordinates = $shipData['coordinates'] ?? [];
            if (!$gameRulesService->isShipPlacementValid($playerPlateau, $coordinates)) {
                error_log("Validation du placement échouée pour le navire du joueur de type $type.");
                return new JsonResponse(['success' => false, 'message' => "Placement invalide pour le navire $type du joueur."], 400);
            }
            $ship = new Ship();
            $ship->setGame($game);
            $ship->setUser($game->getPlayer1());
            $ship->setType($type);
            $ship->setPoints(100);
            $ship->setPointsDeVie(count($coordinates));
            $ship->setEstCoule(false);
            $ship->setPlateau($playerPlateau);
            // Optionnel : stocker la chaîne de positions
            $positionString = implode(';', array_map(fn($c) => $c['x'] . ',' . $c['y'], $coordinates));
            $ship->setPosition($positionString);
            if (!$gameRulesService->placeShip($playerPlateau, $ship, $coordinates)) {
                error_log("Erreur lors du placement du navire du joueur de type $type.");
                return new JsonResponse(['success' => false, 'message' => "Erreur lors du placement du navire $type pour le joueur."], 400);
            }
            $em->persist($ship);
        }

        // Traiter les navires de l'adversaire (si fournis)
        if ($opponentPlateauId && !empty($opponentShipsData)) {
            $opponentPlateau = $em->getRepository(Plateau::class)->find($opponentPlateauId);
            if (!$opponentPlateau) {
                error_log("Plateau de l'adversaire non trouvé pour plateauId: $opponentPlateauId");
                return new JsonResponse(['success' => false, 'message' => "Plateau de l'adversaire non trouvé."], 404);
            }
            foreach ($opponentShipsData as $shipData) {
                $type = $shipData['type'] ?? 'Destroyer';
                $coordinates = $shipData['coordinates'] ?? [];
                if (!$gameRulesService->isShipPlacementValid($opponentPlateau, $coordinates)) {
                    error_log("Validation du placement échouée pour le navire adverse de type $type.");
                    return new JsonResponse(['success' => false, 'message' => "Placement invalide pour le navire $type de l'adversaire."], 400);
                }
                $ship = new Ship();
                $ship->setGame($game);
                $ship->setUser($game->getPlayer2());
                $ship->setType($type);
                $ship->setPoints(100);
                $ship->setPointsDeVie(count($coordinates));
                $ship->setEstCoule(false);
                $ship->setPlateau($opponentPlateau);
                $positionString = implode(';', array_map(fn($c) => $c['x'] . ',' . $c['y'], $coordinates));
                $ship->setPosition($positionString);
                if (!$gameRulesService->placeShip($opponentPlateau, $ship, $coordinates)) {
                    error_log("Erreur lors du placement du navire adverse de type $type.");
                    return new JsonResponse(['success' => false, 'message' => "Erreur lors du placement du navire $type pour l'adversaire."], 400);
                }
                $em->persist($ship);
            }
        }

        $em->flush();
        return new JsonResponse(['success' => true, 'message' => 'Placements enregistrés.']);
    }
}
