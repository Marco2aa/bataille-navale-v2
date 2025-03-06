<?php
// src/Entity/Ship.php
namespace App\Entity;

use App\Repository\ShipRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ShipRepository::class)]
class Ship
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'ships')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Game $game = null;

    #[ORM\ManyToOne(inversedBy: 'ships')]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $user = null;

    #[ORM\Column(length: 255)]
    private ?string $type = null;

    #[ORM\Column(length: 255)]
    private ?string $position = null;

    #[ORM\ManyToOne(inversedBy: 'navires')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Plateau $plateau = null;

    #[ORM\Column]
    private ?int $points = null;

    #[ORM\Column]
    private ?int $points_de_vie = null;

    #[ORM\Column]
    private ?bool $estCoule = null;

    /**
     * @var Collection<int, BoardCase>
     */
    #[ORM\OneToMany(targetEntity: BoardCase::class, mappedBy: 'navire')]
    private Collection $boardCases;

    public function __construct()
    {
        $this->boardCases = new ArrayCollection();
    }

    // Getters & setters...
    public function getId(): ?int
    {
        return $this->id;
    }
    public function getGame(): ?Game
    {
        return $this->game;
    }
    public function setGame(?Game $game): static
    {
        $this->game = $game;
        return $this;
    }
    public function getUser(): ?User
    {
        return $this->user;
    }
    public function setUser(?User $user): static
    {
        $this->user = $user;
        return $this;
    }
    public function getType(): ?string
    {
        return $this->type;
    }
    public function setType(string $type): static
    {
        $this->type = $type;
        return $this;
    }
    public function getPosition(): ?string
    {
        return $this->position;
    }
    public function setPosition(string $position): static
    {
        $this->position = $position;
        return $this;
    }
    public function getPlateau(): ?Plateau
    {
        return $this->plateau;
    }
    public function setPlateau(?Plateau $plateau): static
    {
        $this->plateau = $plateau;
        return $this;
    }
    public function getPoints(): ?int
    {
        return $this->points;
    }
    public function setPoints(int $points): static
    {
        $this->points = $points;
        return $this;
    }
    public function getPointsDeVie(): ?int
    {
        return $this->points_de_vie;
    }
    public function setPointsDeVie(int $points_de_vie): static
    {
        $this->points_de_vie = $points_de_vie;
        return $this;
    }
    public function isEstCoule(): ?bool
    {
        return $this->estCoule;
    }
    public function setEstCoule(bool $estCoule): static
    {
        $this->estCoule = $estCoule;
        return $this;
    }
    /**
     * @return Collection<int, BoardCase>
     */
    public function getBoardCases(): Collection
    {
        return $this->boardCases;
    }
    public function addBoardCase(BoardCase $boardCase): static
    {
        if (!$this->boardCases->contains($boardCase)) {
            $this->boardCases[] = $boardCase;
            $boardCase->setNavire($this);
        }
        return $this;
    }
    public function removeBoardCase(BoardCase $boardCase): static
    {
        if ($this->boardCases->removeElement($boardCase)) {
            if ($boardCase->getNavire() === $this) {
                $boardCase->setNavire(null);
            }
        }
        return $this;
    }
}
