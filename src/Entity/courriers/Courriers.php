<?php

namespace App\Entity\courriers;

use Doctrine\ORM\Mapping as ORM;
use App\Repository\CourriersRepository;
use App\Entity\utils\Fichiers;
use App\Entity\utils\BaseEntite;

#[ORM\Entity(repositoryClass: CourriersRepository::class)]
class Courriers extends BaseEntite
{
    #[ORM\Column(type: "string", length: 100, nullable: false)]
    private ?string $reference = null;

    #[ORM\Column(type: "text", nullable: true)]
    private ?string $description = null;

    #[ORM\Column(type: "string", length: 100, nullable: false)]
    private ?string $mail = null;

    // Relation ManyToOne vers Fichier
    #[ORM\ManyToOne(targetEntity: Fichiers::class)]
    #[ORM\JoinColumn(nullable: true, onDelete: "SET NULL")]
    private ?Fichiers $fichier = null;


    public function getReference(): ?string
    {
        return $this->reference;
    }

    public function setReference(?string $reference): self
    {
        $this->reference = $reference;
        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): self
    {
        $this->description = $description;
        return $this;
    }

    public function getMail(): ?string
    {
        return $this->mail;
    }

    public function setMail(?string $mail): self
    {
        $this->mail = $mail;
        return $this;
    }

    public function getFichier(): ?Fichiers
    {
        return $this->fichier;
    }

    public function setFichier(?Fichiers $fichier): self
    {
        $this->fichier = $fichier;
        return $this;
    }
}
