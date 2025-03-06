<?php
// src/Service/GameRulesService.php
namespace App\Service;

use App\Entity\Plateau;
use App\Entity\Ship;
use App\Entity\BoardCase;

class GameRulesService
{
    /**
     * Vérifie que le placement d'un navire est valide.
     * Le placement doit être dans les limites du plateau,
     * les cases doivent être libres et contiguës en ligne droite (horizontalement ou verticalement).
     *
     * @param Plateau $plateau
     * @param array $coordinates Tableau de coordonnées, ex: [ ['x'=>0, 'y'=>0], ... ]
     * @return bool
     */
    public function isShipPlacementValid(Plateau $plateau, array $coordinates): bool
    {
        if (empty($coordinates)) {
            error_log("Validation échouée : aucune coordonnée fournie pour le plateau ID " . $plateau->getId());
            return false;
        }

        $largeur = $plateau->getLargeur();
        $hauteur = $plateau->getHauteur();

        // Vérifier les limites
        foreach ($coordinates as $coord) {
            $x = (int)$coord['x'];
            $y = (int)$coord['y'];
            if ($x < 0 || $x >= $largeur || $y < 0 || $y >= $hauteur) {
                error_log("Plateau ID {$plateau->getId()}: Coordonnée hors limites : ($x, $y) hors de [0, " . ($largeur - 1) . "] x [0, " . ($hauteur - 1) . "].");
                return false;
            }
        }

        // Vérifier la contiguïté
        $allX = array_map(fn($c) => (int)$c['x'], $coordinates);
        $allY = array_map(fn($c) => (int)$c['y'], $coordinates);
        $uniqueX = array_unique($allX);
        $uniqueY = array_unique($allY);
        if (count($uniqueX) === 1) {
            sort($allY);
            for ($i = 1; $i < count($allY); $i++) {
                if ($allY[$i] !== $allY[$i - 1] + 1) {
                    error_log("Plateau ID {$plateau->getId()}: Placement vertical invalide : indices non contigus " . print_r($allY, true));
                    return false;
                }
            }
        } elseif (count($uniqueY) === 1) {
            sort($allX);
            for ($i = 1; $i < count($allX); $i++) {
                if ($allX[$i] !== $allX[$i - 1] + 1) {
                    error_log("Plateau ID {$plateau->getId()}: Placement horizontal invalide : indices non contigus " . print_r($allX, true));
                    return false;
                }
            }
        } else {
            error_log("Plateau ID {$plateau->getId()}: Placement invalide : les cases ne sont pas alignées horizontalement ou verticalement. UniqueX: " . print_r($uniqueX, true) . " UniqueY: " . print_r($uniqueY, true));
            return false;
        }

        // Vérifier que les cases ne sont pas déjà occupées
        foreach ($coordinates as $coord) {
            $x = (int)$coord['x'];
            $y = (int)$coord['y'];
            $found = false;
            foreach ($plateau->getBoardCases() as $boardCase) {
                if ((int)$boardCase->getX() === $x && (int)$boardCase->getY() === $y) {
                    $found = true;
                    if ($boardCase->getNavire() !== null) {
                        error_log("Plateau ID {$plateau->getId()}: La case ($x, $y) est déjà occupée.");
                        return false;
                    }
                    break;
                }
            }
            if (!$found) {
                error_log("Plateau ID {$plateau->getId()}: La case ($x, $y) n'a pas été trouvée.");
                return false;
            }
        }

        error_log("Plateau ID {$plateau->getId()}: Validation réussie pour les coordonnées: " . print_r($coordinates, true));
        return true;
    }

    /**
     * Place un navire sur le plateau en associant aux cases celles correspondant aux coordonnées indiquées.
     *
     * @param Plateau $plateau
     * @param Ship $ship
     * @param array $coordinates Tableau de coordonnées
     * @return bool
     */
    public function placeShip(Plateau $plateau, Ship $ship, array $coordinates): bool
    {
        if (!$this->isShipPlacementValid($plateau, $coordinates)) {
            error_log("La validation de placement a échoué pour le plateau ID " . $plateau->getId());
            return false;
        }
        foreach ($plateau->getBoardCases() as $boardCase) {
            foreach ($coordinates as $coord) {
                if ((int)$boardCase->getX() === (int)$coord['x'] && (int)$boardCase->getY() === (int)$coord['y']) {
                    $ship->addBoardCase($boardCase);
                    $boardCase->setNavire($ship);
                }
            }
        }
        error_log("Placement effectué pour le navire sur le plateau ID " . $plateau->getId());
        return true;
    }
}
