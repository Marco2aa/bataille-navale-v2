<?php
// src/Controller/GameController.php
namespace App\Controller;

use App\Entity\Game;
use App\Entity\Plateau;
use App\Entity\BoardCase;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class GameController extends AbstractController
{
    #[Route('/game/new', name: 'game_new')]
    public function newGame(EntityManagerInterface $em): Response
    {
        // Vérifier que l'utilisateur est connecté
        $user = $this->getUser();
        if (!$user) {
            throw $this->createAccessDeniedException('Vous devez être connecté pour jouer.');
        }

        // Création d'une nouvelle partie
        $game = new Game();
        $game->setPlayer1($user);

        // Vérifier si un adversaire avec cet email existe déjà
        $adversaireEmail = 'adversaire@example.com';
        $adversaire = $em->getRepository(User::class)->findOneBy(['email' => $adversaireEmail]);
        if (!$adversaire) {
            $adversaire = new User();
            $adversaire->setEmail($adversaireEmail);
            // Dans un vrai contexte, pensez à hacher le mot de passe
            $adversaire->setPassword('password');
            $em->persist($adversaire);
        }
        $game->setPlayer2($adversaire);

        $game->setStatus("in_progress");
        $game->setStartTime(new \DateTime());
        $game->setEndTime(new \DateTime('+1 hour'));

        // Création du plateau pour le joueur
        $playerPlateau = new Plateau();
        $playerPlateau->setLargeur(10);
        $playerPlateau->setHauteur(10);
        for ($y = 0; $y < $playerPlateau->getHauteur(); $y++) {
            for ($x = 0; $x < $playerPlateau->getLargeur(); $x++) {
                $boardCase = new BoardCase();
                $boardCase->setX($x);
                $boardCase->setY($y);
                $boardCase->setEstTouche(false);
                $boardCase->setPlateau($playerPlateau);
                $playerPlateau->addBoardCase($boardCase);
            }
        }

        // Création du plateau pour l'adversaire
        $opponentPlateau = new Plateau();
        $opponentPlateau->setLargeur(10);
        $opponentPlateau->setHauteur(10);
        for ($y = 0; $y < $opponentPlateau->getHauteur(); $y++) {
            for ($x = 0; $x < $opponentPlateau->getLargeur(); $x++) {
                $boardCase = new BoardCase();
                $boardCase->setX($x);
                $boardCase->setY($y);
                $boardCase->setEstTouche(false);
                $boardCase->setPlateau($opponentPlateau);
                $opponentPlateau->addBoardCase($boardCase);
            }
        }

        // À ce stade, aucun navire n'est placé.
        // Le joueur pourra placer ses bateaux sur son plateau de navires,
        // et l'adversaire (ou son IA) pourra placer les siens via l'interface.

        // Persistance des entités
        $em->persist($game);
        $em->persist($playerPlateau);
        $em->persist($opponentPlateau);
        $em->flush();

        // Transmission de la partie et des deux plateaux à la vue
        return $this->render('game.html.twig', [
            'game' => $game,
            'playerPlateau' => $playerPlateau,
            'opponentPlateau' => $opponentPlateau,
        ]);
    }
}
