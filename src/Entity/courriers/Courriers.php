<?php

namespace App\Entity\courriers;

use Doctrine\ORM\Mapping as ORM;
use App\Repository\courriers\CourriersRepository;
use App\Entity\utils\BaseEntite;
use App\Entity\utilisateurs\Utilisateurs;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

#[ORM\Entity(repositoryClass: CourriersRepository::class)]
class Courriers extends BaseEntite
{
    #[ORM\Column(type: "string", length: 100, nullable: false)]
    private ?string $reference = null;

    #[ORM\Column(type: "string", length: 255, nullable: false)]
    private ?string $object = null;

    #[ORM\Column(type: "text", nullable: true)]
    private ?string $description = null;



    #[ORM\Column(type: "string", length: 100, nullable: true)]
    private ?string $mail = null;

    #[ORM\Column(type: "string", length: 255, nullable: true)]
    private ?string $nom;

    #[ORM\Column(type: "string", length: 255, nullable: true)]
    private ?string $prenom = null;

    #[ORM\Column(type: "string", length: 255, nullable: true)]
    private ?string $telephone = null;

    #[ORM\Column(type: "datetime_immutable", nullable: true)]
    private ?\DateTimeImmutable $dateFin = null;

    #[ORM\ManyToOne(targetEntity: Utilisateurs::class)]
    #[ORM\JoinColumn(nullable: true)]
    private ?Utilisateurs $createur = null;

    public function __construct()
    {
    }


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

    public function getNom(): ?string
    {
        return $this->nom;
    }

    public function setNom(string $nom): self
    {
        $this->nom = $nom;
        return $this;
    }

    public function getPrenom(): ?string
    {
        return $this->prenom;
    }

    public function setPrenom(?string $prenom): self
    {
        $this->prenom = $prenom;
        return $this;
    }

    public function getTelephone(): ?string
    {
        return $this->telephone;
    }

    public function setTelephone(?string $telephone): self
    {
        $this->telephone = $telephone;
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

    public function getCreateur(): ?Utilisateurs
    {
        return $this->createur;
    }

    public function setCreateur(?Utilisateurs $createur): self
    {
        $this->createur = $createur;
        return $this;
    }

    public function toArray(): array
    {
        return [
            'id' => $this->getId(),
            'reference' => $this->reference,
            'object' => $this->object,
            'description' => $this->description,
            'mail' => $this->mail,
            'nom' => $this->nom,
            'prenom' => $this->prenom,
            'telephone' => $this->telephone,
            'dateFin' => $this->dateFin ? $this->dateFin->format('Y-m-d H:i:s') : null,
            'dateCreation' => $this->getDateCreation() ? $this->getDateCreation()->format('Y-m-d H:i:s') : null,
            'createur' => $this->createur ? [
                'id' => $this->createur->getId(),
                'nom' => $this->createur->getNom(),
                'prenom' => $this->createur->getPrenom(),
            ] : null,
        ];
    }
}
