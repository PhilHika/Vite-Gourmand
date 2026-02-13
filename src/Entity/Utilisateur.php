<?php

namespace App\Entity;

use App\Entity\Traits\HasAdresseTrait; // collection adresse
use App\Entity\Traits\HasAvisTrait; // collection avis
use App\Entity\Traits\HasCommandesUtilisateurTrait; // collection commandes
use App\Repository\UtilisateurRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Validator\Constraints as Assert;
use App\Entity\Avis;
use App\Entity\Commande;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

#[ORM\Entity(repositoryClass: UtilisateurRepository::class)]
#[ORM\Table(name: '`utilisateur`')]
#[ORM\UniqueConstraint(name: 'UNIQ_IDENTIFIER_EMAIL', fields: ['email'])]
#[UniqueEntity(fields: ['email'], message: 'There is already an account with this email')]
class Utilisateur implements UserInterface, PasswordAuthenticatedUserInterface
{
    use HasCommandesUtilisateurTrait;
    use HasAvisTrait;
    use HasAdresseTrait;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: 'utilisateur_id')]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'utilisateurs')]
    #[ORM\JoinColumn(name: 'role_id', nullable: false)]
    private ?Role $role = null;

    #[ORM\Column(name: 'password', length: 255)]
    private ?string $password = null;

    #[ORM\Column(name: 'email', length: 50)]
    #[Assert\NotBlank(message: 'L\'email est obligatoire')]
    #[Assert\Email(message: 'L\'email est invalide')]
    private ?string $email = null;

    #[ORM\Column(name: 'prenom', length: 50)]
    #[Assert\NotBlank(message: 'Le prénom est obligatoire')]
    #[Assert\Length(max: 50, maxMessage: 'Le prénom ne peut pas dépasser {{ limit }} caractères')]
    private ?string $prenom = null;

    #[ORM\Column(name: 'nom', length: 50)]
    #[Assert\NotBlank(message: 'Le nom est obligatoire')]
    #[Assert\Length(max: 50, maxMessage: 'Le nom ne peut pas dépasser {{ limit }} caractères')]
    private ?string $nom = null;

    #[ORM\Column(name: 'telephone', length: 50)]
    #[Assert\NotBlank(message: 'Le téléphone est obligatoire')]
    #[Assert\Length(max: 50, maxMessage: 'Le téléphone ne peut pas dépasser {{ limit }} caractères')]
    #[Assert\Regex(
        pattern: '/^\d+$/',
        message: 'Le téléphone ne peut contenir que des chiffres'
    )]
    private ?string $telephone = null;

    #[ORM\Column(name: 'ville', length: 50)]
    #[Assert\NotBlank(message: 'La ville est obligatoire')]
    #[Assert\Length(max: 50, maxMessage: 'La ville ne peut pas dépasser {{ limit }} caractères')]
    private ?string $ville = null;

    #[ORM\Column(name: 'pays', length: 50)]
    #[Assert\NotBlank(message: 'Le pays est obligatoire')]
    #[Assert\Length(max: 50, maxMessage: 'Le pays ne peut pas dépasser {{ limit }} caractères')]
    private ?string $pays = null;

    #[ORM\Column(name: 'adresse_postale', length: 50)]
    #[Assert\NotBlank(message: 'L\'adresse est obligatoire')]
    #[Assert\Length(max: 50, maxMessage: 'L\'adresse ne peut pas dépasser {{ limit }} caractères')]
    private ?string $adressePostale = null;

    #[ORM\OneToMany(mappedBy: 'utilisateur', targetEntity: Avis::class)]
    private Collection $avis;

    #[ORM\OneToMany(mappedBy: 'utilisateur', targetEntity: Commande::class)]
    private Collection $commandes;

    // Dans __construct()
    public function __construct()
    {
        $this->avis = new ArrayCollection();
        $this->commandes = new ArrayCollection();
    }


    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUserIdentifier(): string
    {
        return (string) $this->email;
    }

    // getRoles() pour suivre le schema annex 1 del a DB
    // On s'eloigne de la methode classique Symfony mais on ecrit de la meme façon : ROLE_XXX
    public function getRoles(): array
    {
        return array_unique([
            $this->role?->getLibelle() ?? 'ROLE_USER'
        ]);
    }

    public function getRole(): ?Role
    {
        return $this->role;
    }

    public function setRole(?Role $role): static
    {
        $this->role = $role;

        return $this;
    }

    public function getPassword(): ?string
    {
        return $this->password;
    }

    public function setPassword(string $password): static
    {
        $this->password = $password;

        return $this;
    }

    public function eraseCredentials(): void
    {
        // Si tu stockes des données sensibles temporaires, vide-les ici
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(string $email): static
    {
        $this->email = $email;

        return $this;
    }

    public function getPrenom(): ?string
    {
        return $this->prenom;
    }

    public function setPrenom(string $prenom): static
    {
        $this->prenom = $prenom;

        return $this;
    }

    public function getNom(): ?string
    {
        return $this->nom;
    }

    public function setNom(string $nom): static
    {
        $this->nom = $nom;

        return $this;
    }

    public function getTelephone(): ?string
    {
        return $this->telephone;
    }

    public function setTelephone(string $telephone): static
    {
        $this->telephone = preg_replace('/\D/', '', $telephone);

        return $this;
    }
}
