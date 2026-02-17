<?php

namespace App\Entity\utilisateurs;

use App\Repository\RolesRepository;
use Doctrine\ORM\Mapping as ORM;
use App\Entity\utils\BaseEntite;

#[ORM\Entity(repositoryClass: RolesRepository::class)]
class Roles extends BaseEntite
{
    #[ORM\Column(length: 255)]
    private ?string $name = null;

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;
        return $this;
    }
}
