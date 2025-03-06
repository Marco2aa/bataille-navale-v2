<?php
// src/Service/GameRulesService.php
namespace App\Service;

use App\Entity\Plateau;
use App\Entity\Ship;
use App\Entity\BoardCase;

class GameRulesService
{
    /**
     * Vérifie si le placement d'un navire est valide sur un plateau.
     *
     * @param Plateau $plateau Le plateau sur lequel le navire doit être placé.
     * @param array   $coordinates Un tableau de coordonnées (chaque élément est un tableau associatif ['x' => int, 'y' => int]).
     *
     * @return bool Retourne true si toutes les cases sont dans le plateau et non occupées, false sinon.
     */
    public function isShipPlacementValid(Plateau $plateau, array $coordinates): bool
    {
        $largeur = $plateau->getLargeur();
        $hauteur = $plateau->getHauteur();

        foreach ($coordinates as $coord) {
            $x = $coord['x'];
            $y = $coord['y'];
            // Vérifier si la case est dans les limites du plateau
            if ($x < 0 || $x >= $largeur || $y < 0 || $y >= $hauteur) {
                return false;
            }
            // Vérifier que la case n'est pas déjà occupée par un autre navire
            foreach ($plateau->getBoardCases() as $boardCase) {
                if ($boardCase->getX() === $x && $boardCase->getY() === $y && $boardCase->getNavire() !== null) {
                    return false;
                }
            }
        }

        return true;
    }

    /**
     * Place un navire sur le plateau en associant les cases correspondantes.
     *
     * @param Plateau $plateau Le plateau sur lequel placer le navire.
     * @param Ship    $ship Le navire à placer.
     * @param array   $coordinates Tableau de coordonnées à occuper par le navire.
     *
     * @return bool Retourne true si le placement est effectué, false si le placement est invalide.
     */
    public function placeShip(Plateau $plateau, Ship $ship, array $coordinates): bool
    {
        if (!$this->isShipPlacementValid($plateau, $coordinates)) {
            return false;
        }
        // Pour chaque case du plateau, si ses coordonnées font partie du placement, on l'associe au navire.
        foreach ($plateau->getBoardCases() as $boardCase) {
            foreach ($coordinates as $coord) {
                if ($boardCase->getX() === $coord['x'] && $boardCase->getY() === $coord['y']) {
                    $ship->addBoardCase($boardCase);
                    $boardCase->setNavire($ship);
                }
            }
        }
        return true;
    }

    /**
     * Traite un tir sur un plateau.
     *
     * @param Plateau $plateau Le plateau ciblé par le tir.
     * @param int     $x La coordonnée x du tir.
     * @param int     $y La coordonnée y du tir.
     *
     * @return array Retourne un tableau avec les informations sur le résultat du tir.
     */
    public function processShot(Plateau $plateau, int $x, int $y): array
    {
        // Recherche de la case ciblée
        $targetCase = null;
        foreach ($plateau->getBoardCases() as $boardCase) {
            if ($boardCase->getX() === $x && $boardCase->getY() === $y) {
                $targetCase = $boardCase;
                break;
            }
        }
        if (!$targetCase) {
            return [
                'success' => false,
                'message' => 'Case invalide.'
            ];
        }

        // Vérifier si la case a déjà été touchée
        if ($targetCase->isEstTouche()) {
            return [
                'success' => false,
                'message' => 'Cette case a déjà été touchée.'
            ];
        }

        // Marquer la case comme touchée
        $targetCase->setEstTouche(true);
        $ship = $targetCase->getNavire();

        if ($ship) {
            // Décrémenter les points de vie du navire
            $currentPV = $ship->getPointsDeVie();
            $ship->setPointsDeVie($currentPV - 1);
            $result = 'touché';
            if ($ship->getPointsDeVie() <= 0) {
                $ship->setEstCoule(true);
                $result = 'coulé';
            }
            return [
                'success' => true,
                'message' => "Navire $result.",
                'shipStatus' => $result
            ];
        }

        return [
            'success' => true,
            'message' => 'Manqué.'
        ];
    }
}
