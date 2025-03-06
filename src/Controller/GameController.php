<?php
// src/Controller/GameController.php
namespace App\Controller;

use App\Entity\Game;
use App\Entity\Plateau;
use App\Entity\BoardCase;
use App\Entity\Ship;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class GameController extends AbstractController
{
    #[Route('/game/new', name: 'game_new')]
    public function newGame(EntityManagerInterface $em): Response
    {
        // Pour cet exemple, on exige que l'utilisateur soit connecté.
        $user = $this->getUser();
        if (!$user) {
            throw $this->createAccessDeniedException('Vous devez être connecté pour jouer.');
        }

        // Création d'une nouvelle partie
        $game = new Game();
        $game->setPlayer1($user);
        // Pour cet exemple, nous ne définissons pas player2 (ou on pourrait créer un joueur "IA")
        $game->setStatus("in_progress");
        $game->setStartTime(new \DateTime());
        $game->setEndTime(new \DateTime('+1 hour'));

        // Création d'un plateau pour le joueur (dimensions 10x10)
        $plateau = new Plateau();
        $plateau->setLargeur(10);
        $plateau->setHauteur(10);

        // Génération de toutes les cases du plateau
        for ($y = 0; $y < $plateau->getHauteur(); $y++) {
            for ($x = 0; $x < $plateau->getLargeur(); $x++) {
                $boardCase = new BoardCase();
                $boardCase->setX($x);
                $boardCase->setY($y);
                $boardCase->setEstTouche(false);
                $boardCase->setPlateau($plateau);
                $plateau->addBoardCase($boardCase);
            }
        }

        // Pour la démonstration, plaçons un navire de type "Destroyer" qui occupe les cases (2,3), (3,3) et (4,3)
        $ship = new Ship();
        $ship->setGame($game);
        $ship->setUser($user);
        $ship->setPosition(10, 10); // Par exemple, définir la position initiale comme "2,3"
        $ship->setType("Destroyer");
        $ship->setPoints(100);
        $ship->setPointsDeVie(3); // Longueur du navire = 3
        $ship->setEstCoule(false);
        $ship->setPlateau($plateau);

        // Parcourir les cases du plateau et associer celles correspondant aux coordonnées souhaitées au navire
        foreach ($plateau->getBoardCases() as $boardCase) {
            if (($boardCase->getX() >= 2 && $boardCase->getX() <= 4) && $boardCase->getY() == 3) {
                $ship->addBoardCase($boardCase);
                $boardCase->setNavire($ship);
            }
        }

        // Persistance des entités
        $em->persist($game);
        $em->persist($plateau);
        $em->persist($ship);
        $em->flush();

        // On transmet le plateau (et éventuellement le game) à la vue
        return $this->render('game.html.twig', [
            'game' => $game,
            'plateau' => $plateau,
        ]);
    }
}
