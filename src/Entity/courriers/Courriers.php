<?php

namespace App\Entity\courriers;

use Doctrine\ORM\Mapping as ORM;
use App\Repository\courriers\CourriersRepository;
use App\Entity\utils\Fichiers;
use App\Entity\utils\BaseEntite;

#[ORM\Entity(repositoryClass: CourriersRepository::class)]
class Courriers extends BaseEntite
{
    #[ORM\Column(type: "string", length: 100, nullable: false)]
    private ?string $reference = null;

    #[ORM\Column(type: "string", length: 255, nullable: false)]
    private ?string $object = null;

    #[ORM\Column(type: "text", nullable: true)]
    private ?string $description = null;



    #[ORM\Column(type: "string", length: 100, nullable: false)]
    private ?string $mail = null;

    // Relation ManyToOne vers Fichier
    #[ORM\ManyToOne(targetEntity: Fichiers::class)]
    #[ORM\JoinColumn(nullable: true, onDelete: "SET NULL")]
    private ?Fichiers $fichier = null;

    #[ORM\Column(type: "datetime_immutable", nullable: true)]
    private ?\DateTimeImmutable $dateFin = null;


    public function getReference(): ?string
    {
        return $this->reference;
    }

    public function setReference(?string $reference): self
    {
        $this->reference = $reference;
        return $this;
    }
    public function getObject(): ?string
    {
        return $this->object;
    }
    public function setObject(?string $object): self
    {
        $this->object = $object;
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

    public function getDateFin(): ?\DateTimeImmutable
    {
        return $this->dateFin;
    }

    public function setDateFin(?\DateTimeImmutable $dateFin): self
    {
        $this->dateFin = $dateFin;
        return $this;
    }

    // public function toArray(): array
    // {
    //     return [
    //         'id' => $this->getId(),
    //         'reference' => $this->reference,
    //         'object' => $this->object,
    //         'description' => $this->description,
    //         'mail' => $this->mail,
    //         'dateFin' => $this->dateFin ? $this->dateFin->format('Y-m-d H:i:s') : null,
    //         'dateCreation' => $this->getDateCreation() ? $this->getDateCreation()->format('Y-m-d H:i:s') : null,
    //         'fichier' => $this->fichier ? [
    //             'id' => $this->fichier->getId(),
    //             'nom' => $this->fichier->getNom(), // Suppression de l'erreur potentielle si Fichiers a getNom
    //         ] : null,
    //     ];
    // }
}
