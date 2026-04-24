<?php

namespace App\Contract;

use App\Entity\Commande;

interface CommandeMailerServiceInterface
{
    public function envoyerConfirmation(Commande $commande): void;
    public function envoyerChangementStatut(Commande $commande, string $ancienStatut): void;
}
