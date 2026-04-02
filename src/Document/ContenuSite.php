<?php

namespace App\Document;

use App\Repository\ContenuSiteRepository;
use Doctrine\ODM\MongoDB\Mapping\Attribute as MongoDB;
use Symfony\Component\Validator\Constraints as Assert;

#[MongoDB\Document(collection: 'contenu_site', repositoryClass: ContenuSiteRepository::class)]
class ContenuSite
{
    #[MongoDB\Id]
    private ?string $id = null;

    #[MongoDB\Field(type: 'string')]
    #[Assert\NotBlank(message: 'La clé est obligatoire')]
    private ?string $cle = null;

    #[MongoDB\Field(type: 'string')]
    private ?string $contenu = null;

    public function getId(): ?string
    {
        return $this->id;
    }

    public function getCle(): ?string
    {
        return $this->cle;
    }

    public function setCle(string $cle): static
    {
        $this->cle = $cle;

        return $this;
    }

    public function getContenu(): ?string
    {
        return $this->contenu;
    }

    public function setContenu(?string $contenu): static
    {
        $this->contenu = $contenu;

        return $this;
    }
}
