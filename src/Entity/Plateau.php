<?php

namespace App\Entity;

use App\Repository\PlateauRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: PlateauRepository::class)]
class Plateau
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column]
    private ?int $largeur = null;

    #[ORM\Column]
    private ?int $hauteur = null;

    #[ORM\Column]
    private array $listCases = [];

    /**
     * @var Collection<int, Ship>
     */
    #[ORM\OneToMany(targetEntity: Ship::class, mappedBy: 'plateau')]
    private Collection $navires;

    /**
     * @var Collection<int, BoardCase>
     */
    #[ORM\OneToMany(targetEntity: BoardCase::class, mappedBy: 'plateau')]
    private Collection $boardCases;

    public function __construct()
    {
        $this->navires = new ArrayCollection();
        $this->boardCases = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getLargeur(): ?int
    {
        return $this->largeur;
    }

    public function setLargeur(int $largeur): static
    {
        $this->largeur = $largeur;

        return $this;
    }

    public function getHauteur(): ?int
    {
        return $this->hauteur;
    }

    public function setHauteur(int $hauteur): static
    {
        $this->hauteur = $hauteur;

        return $this;
    }

    public function getListCases(): array
    {
        return $this->listCases;
    }

    public function setListCases(array $listCases): static
    {
        $this->listCases = $listCases;

        return $this;
    }

    /**
     * @return Collection<int, Ship>
     */
    public function getNavires(): Collection
    {
        return $this->navires;
    }

    public function addNavire(Ship $navire): static
    {
        if (!$this->navires->contains($navire)) {
            $this->navires->add($navire);
            $navire->setPlateau($this);
        }

        return $this;
    }

    public function removeNavire(Ship $navire): static
    {
        if ($this->navires->removeElement($navire)) {
            // set the owning side to null (unless already changed)
            if ($navire->getPlateau() === $this) {
                $navire->setPlateau(null);
            }
        }

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
            $this->boardCases->add($boardCase);
            $boardCase->setPlateau($this);
        }

        return $this;
    }

    public function removeBoardCase(BoardCase $boardCase): static
    {
        if ($this->boardCases->removeElement($boardCase)) {
            // set the owning side to null (unless already changed)
            if ($boardCase->getPlateau() === $this) {
                $boardCase->setPlateau(null);
            }
        }

        return $this;
    }
}
