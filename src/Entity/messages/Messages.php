<?php

namespace App\Entity\messages;

use App\Repository\MessagesRepository;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\DBAL\Types\Types;
use App\Entity\utils\BaseEntite;
use App\Entity\courriers\Courriers;
use App\Entity\utilisateurs\Utilisateurs;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use App\Entity\utils\Fichiers;

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

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $observation = null;

    #[ORM\OneToMany(mappedBy: 'message', targetEntity: Fichiers::class, cascade: ['persist', 'remove'])]
    private Collection $fichiers;

    public function __construct()
    {
        $this->fichiers = new ArrayCollection();
    }


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

    public function getObservation(): ?string
    {
        return $this->observation;
    }

    public function setObservation(?string $observation): static
    {
        $this->observation = $observation;
        return $this;
    }

    /**
     * @return Collection<int, Fichiers>
     */
    public function getFichiers(): Collection
    {
        return $this->fichiers;
    }

    public function addFichier(Fichiers $fichier): self
    {
        if (!$this->fichiers->contains($fichier)) {
            $this->fichiers->add($fichier);
            $fichier->setMessage($this);
        }

        return $this;
    }

    public function removeFichier(Fichiers $fichier): self
    {
        if ($this->fichiers->removeElement($fichier)) {
            // set the owning side to null (unless already changed)
            if ($fichier->getMessage() === $this) {
                $fichier->setMessage(null);
            }
        }

        return $this;
    }

    public function toArray(): array
    {
        return [
            'id' => $this->getId(),
            'courrier' => $this->courrier ? [
                'id' => $this->courrier->getId(),
                'reference' => $this->courrier->getReference(),
                'object' => $this->courrier->getObject(),
            ] : null,
            'expediteur' => $this->expediteur ? [
                'id' => $this->expediteur->getId(),
                'nom' => $this->expediteur->getNom(),
                'prenom' => $this->expediteur->getPrenom(),
            ] : null,
            'destinataire' => $this->destinataire ? [
                'id' => $this->destinataire->getId(),
                'nom' => $this->destinataire->getNom(),
                'prenom' => $this->destinataire->getPrenom(),
            ] : null,
            'isReadAt' => $this->isReadAt ? $this->isReadAt->format('Y-m-d H:i:s') : null,
            'observation' => $this->observation,
            'dateCreation' => $this->getDateCreation() ? $this->getDateCreation()->format('Y-m-d H:i:s') : null,
            'fichiers' => $this->fichiers->map(fn(Fichiers $f) => [
                'id' => $f->getId(),
                'nom' => $f->getNom(),
                'type' => $f->getType(),
            ])->toArray(),
        ];
    }
}
