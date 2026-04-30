<?php

namespace App\Contract;

use App\Entity\Utilisateur;

/** Contrat du service d'envoi d'emails de réinitialisation de mot de passe. */
interface PasswordResetMailerServiceInterface
{
    /** Envoie le lien de réinitialisation à l'utilisateur avec le token UUID. */
    public function envoyerLienReset(Utilisateur $user, string $token): void;
}
