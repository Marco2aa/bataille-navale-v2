<?php
// src/Controller/GameController.php
namespace App\Controller;

use App\Entity\Game;
use App\Entity\Plateau;
use App\Entity\BoardCase;
use App\Entity\Ship;
use App\Entity\User;
use App\Service\GameRulesService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class GameController extends AbstractController
{
    #[Route('/game/new', name: 'game_new')]
    public function newGame(EntityManagerInterface $em, GameRulesService $gameRulesService): Response
    {
        // Vérifier que l'utilisateur est connecté
        $user = $this->getUser();
        if (!$user) {
            throw $this->createAccessDeniedException('Vous devez être connecté pour jouer.');
        }

        // Création d'une nouvelle partie
        $game = new Game();
        $game->setPlayer1($user);

        // Vérifier si un adversaire existe déjà
        $adversaireEmail = 'adversaire@example.com';
        $adversaire = $em->getRepository(User::class)->findOneBy(['email' => $adversaireEmail]);
        if (!$adversaire) {
            $adversaire = new User();
            $adversaire->setEmail($adversaireEmail);
            // Dans un vrai contexte, hachez le mot de passe
            $adversaire->setPassword('password');
            $em->persist($adversaire);
        }
        $game->setPlayer2($adversaire);
        $game->setStatus("in_progress");
        $game->setStartTime(new \DateTime());
        $game->setEndTime(new \DateTime('+1 hour'));

        // Création des plateaux pour le joueur (flotte de placement et plateau d'attaque)
        $playerShipPlateau = $this->createPlateau(10, 10);
        $playerAttackPlateau = $this->createPlateau(10, 10);

        // Création des plateaux pour l'adversaire
        $opponentShipPlateau = $this->createPlateau(10, 10);
        $opponentAttackPlateau = $this->createPlateau(10, 10);

        // Persistance des plateaux
        $em->persist($playerShipPlateau);
        $em->persist($playerAttackPlateau);
        $em->persist($opponentShipPlateau);
        $em->persist($opponentAttackPlateau);

        // Fonction anonyme pour générer un placement aléatoire
        $generateRandomPlacement = function (Plateau $plateau, int $size): array {
            $width = $plateau->getLargeur();
            $height = $plateau->getHauteur();
            // 0 = horizontal, 1 = vertical
            $orientation = random_int(0, 1);
            if ($orientation === 0) {
                // Horizontal: x entre 0 et width - size
                $x = random_int(0, $width - $size);
                $y = random_int(0, $height - 1);
                $coords = [];
                for ($i = 0; $i < $size; $i++) {
                    $coords[] = ['x' => $x + $i, 'y' => $y];
                }
            } else {
                // Vertical: y entre 0 et height - size
                $x = random_int(0, $width - 1);
                $y = random_int(0, $height - $size);
                $coords = [];
                for ($i = 0; $i < $size; $i++) {
                    $coords[] = ['x' => $x, 'y' => $y + $i];
                }
            }
            return $coords;
        };

        // Définition de la flotte classique
        $fleetDefinitions = [
            ['type' => 'Porte-avions', 'size' => 5],
            ['type' => 'Cuirassé', 'size' => 4],
            ['type' => 'Croiseur', 'size' => 3],
            ['type' => 'Sous-marin', 'size' => 3],
            ['type' => 'Torpilleur', 'size' => 2],
        ];

        $maxAttempts = 100;

        // Générer la flotte pour le joueur
        foreach ($fleetDefinitions as $def) {
            $attempt = 0;
            $placed = false;
            while (!$placed && $attempt < $maxAttempts) {
                $attempt++;
                $coords = $generateRandomPlacement($playerShipPlateau, $def['size']);
                if ($gameRulesService->isShipPlacementValid($playerShipPlateau, $coords)) {
                    $ship = new Ship();
                    $ship->setGame($game);
                    $ship->setUser($user);
                    $ship->setType($def['type']);
                    $ship->setPoints(100);
                    $ship->setPointsDeVie($def['size']);
                    $ship->setEstCoule(false);
                    $ship->setPlateau($playerShipPlateau);
                    $gameRulesService->placeShip($playerShipPlateau, $ship, $coords);
                    // Optionnel: stocker la chaîne de coordonnées dans position
                    $positionString = implode(';', array_map(function ($c) {
                        return $c['x'] . ',' . $c['y'];
                    }, $coords));
                    $ship->setPosition($positionString);
                    $em->persist($ship);
                    $placed = true;
                }
            }
            if (!$placed) {
                throw new \Exception("Impossible de placer le navire {$def['type']} pour le joueur après $maxAttempts essais.");
            }
        }

        // Générer la flotte pour l'adversaire
        foreach ($fleetDefinitions as $def) {
            $attempt = 0;
            $placed = false;
            while (!$placed && $attempt < $maxAttempts) {
                $attempt++;
                $coords = $generateRandomPlacement($opponentShipPlateau, $def['size']);
                if ($gameRulesService->isShipPlacementValid($opponentShipPlateau, $coords)) {
                    $ship = new Ship();
                    $ship->setGame($game);
                    $ship->setUser($game->getPlayer2());
                    $ship->setType($def['type']);
                    $ship->setPoints(100);
                    $ship->setPointsDeVie($def['size']);
                    $ship->setEstCoule(false);
                    $ship->setPlateau($opponentShipPlateau);
                    $gameRulesService->placeShip($opponentShipPlateau, $ship, $coords);
                    $positionString = implode(';', array_map(function ($c) {
                        return $c['x'] . ',' . $c['y'];
                    }, $coords));
                    $ship->setPosition($positionString);
                    $em->persist($ship);
                    $placed = true;
                }
            }
            if (!$placed) {
                throw new \Exception("Impossible de placer le navire {$def['type']} pour l'adversaire après $maxAttempts essais.");
            }
        }

        // Persistance finale
        $em->persist($game);
        $em->flush();

        return $this->render('game.html.twig', [
            'game' => $game,
            'playerShipPlateau' => $playerShipPlateau,
            'playerAttackPlateau' => $playerAttackPlateau,
            'opponentShipPlateau' => $opponentShipPlateau,
            'opponentAttackPlateau' => $opponentAttackPlateau,
        ]);
    }

    /**
     * Fonction utilitaire pour créer un plateau avec toutes ses cases.
     */
    private function createPlateau(int $width, int $height): Plateau
    {
        $plateau = new Plateau();
        $plateau->setLargeur($width);
        $plateau->setHauteur($height);
        for ($y = 0; $y < $height; $y++) {
            for ($x = 0; $x < $width; $x++) {
                $boardCase = new BoardCase();
                $boardCase->setX($x);
                $boardCase->setY($y);
                $boardCase->setEstTouche(false);
                $boardCase->setPlateau($plateau);
                $plateau->addBoardCase($boardCase);
            }
        }
        return $plateau;
    }
}
