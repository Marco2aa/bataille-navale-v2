<?php

namespace App\Entity;

use App\Repository\BoardCaseRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: BoardCaseRepository::class)]
class BoardCase
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column]
    private ?int $x = null;

    #[ORM\Column]
    private ?int $y = null;

    #[ORM\Column]
    private ?bool $estTouche = null;

    #[ORM\ManyToOne(inversedBy: 'boardCases')]
    private ?Ship $navire = null;

    #[ORM\ManyToOne(inversedBy: 'boardCases')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Plateau $plateau = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getX(): ?int
    {
        return $this->x;
    }

    public function setX(int $x): static
    {
        $this->x = $x;

        return $this;
    }

    public function getY(): ?int
    {
        return $this->y;
    }

    public function setY(int $y): static
    {
        $this->y = $y;

        return $this;
    }

    public function isEstTouche(): ?bool
    {
        return $this->estTouche;
    }

    public function setEstTouche(bool $estTouche): static
    {
        $this->estTouche = $estTouche;

        return $this;
    }

    public function getNavire(): ?Ship
    {
        return $this->navire;
    }

    public function setNavire(?Ship $navire): static
    {
        $this->navire = $navire;

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
}
