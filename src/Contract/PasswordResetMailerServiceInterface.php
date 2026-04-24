<?php

namespace App\Contract;

use App\Entity\Utilisateur;

interface PasswordResetMailerServiceInterface
{
    public function envoyerLienReset(Utilisateur $user, string $token): void;
}
