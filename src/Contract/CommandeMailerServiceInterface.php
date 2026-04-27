<?php

namespace App\Contract;

use App\Entity\Commande;

/** Contrat du service d'emails liés aux commandes. */
interface CommandeMailerServiceInterface
{
    /** Envoie la confirmation au client après création d'une commande. */
    public function envoyerConfirmation(Commande $commande): void;

    /** Envoie un email au client lors du changement de statut. */
    public function envoyerChangementStatut(Commande $commande, string $ancienStatut): void;
}
