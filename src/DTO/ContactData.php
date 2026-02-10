<?php

namespace App\DTO;

use Symfony\Component\Validator\Constraints as Assert;

/**
 * DTO du formulaire de contact. Class simple pour récupérer les données du formulaire. (pas d'entity required)
 */
class ContactData
{
    #[Assert\NotBlank(message: 'Veuillez entrer votre nom')]
    #[Assert\Length(max: 100, maxMessage: 'Le nom ne peut pas dépasser {{ limit }} caractères')]
    public ?string $nom = null;

    #[Assert\NotBlank(message: 'Veuillez entrer votre email')]
    #[Assert\Email(message: 'Veuillez entrer une adresse email valide')]
    public ?string $email = null;

    #[Assert\NotBlank(message: 'Veuillez entrer un code postal')]
    #[Assert\Regex(
        pattern: '/^[0-9]{5}$/',
        message: 'Le code postal doit être composé de 5 chiffres'
    )]
    public ?string $code_postal = null;

    #[Assert\NotBlank(message: 'Veuillez entrer un sujet')]
    #[Assert\Length(max: 200, maxMessage: 'Le sujet ne peut pas dépasser {{ limit }} caractères')]
    public ?string $sujet = null;

    #[Assert\NotBlank(message: 'Veuillez entrer votre message')]
    #[Assert\Length(
        min: 10,
        max: 2000,
        minMessage: 'Le message doit contenir au moins {{ limit }} caractères',
        maxMessage: 'Le message ne peut pas dépasser {{ limit }} caractères'
    )]
    public ?string $message = null;
}
