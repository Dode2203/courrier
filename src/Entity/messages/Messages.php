<?php

namespace App\Entity\messages;

use App\Repository\MessagesRepository;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\DBAL\Types\Types;
use App\Entity\utils\BaseEntite;
use App\Entity\courriers\Courriers;
use App\Entity\utilisateurs\Utilisateurs;

#[ORM\Entity(repositoryClass: MessagesRepository::class)]
class Messages extends BaseEntite
{
    #[ORM\ManyToOne(targetEntity: Courriers::class)]
    #[ORM\JoinColumn(nullable: false)]
    private ?Courriers $courrier = null;

    #[ORM\ManyToOne(targetEntity: Utilisateurs::class)]
    #[ORM\JoinColumn(nullable: false)]
    private ?Utilisateurs $expediteur = null;

    #[ORM\ManyToOne(targetEntity: Utilisateurs::class)]
    #[ORM\JoinColumn(nullable: false)]
    private ?Utilisateurs $destinataire = null;


    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $isReadAt = null;

        
    public function getCourrier(): ?Courriers
    {
        return $this->courrier;
    }

    public function setCourrier(?Courriers $courrier): static
    {
        $this->courrier = $courrier;
        return $this;
    }

    public function getExpediteur(): ?Utilisateurs
    {
        return $this->expediteur;
    }

    public function setExpediteur(?Utilisateurs $expediteur): static
    {
        $this->expediteur = $expediteur;
        return $this;
    }

    public function getDestinataire(): ?Utilisateurs
    {
        return $this->destinataire;
    }

    public function setDestinataire(?Utilisateurs $destinataire): static
    {
        $this->destinataire = $destinataire;
        return $this;
    }

    public function getIsReadAt(): ?\DateTimeInterface
    {
        return $this->isReadAt;
    }

    public function setIsReadAt(?\DateTimeInterface $isReadAt): static
    {
        $this->isReadAt = $isReadAt;
        return $this;
    }

}
