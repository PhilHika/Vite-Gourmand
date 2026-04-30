<?php

namespace App\Contract;

use App\DTO\ContactData;

/** Contrat du service d'envoi d'emails de contact. */
interface ContactMailerServiceInterface
{
    /** Envoie le message à tous les gestionnaires (ROLE_SALARIE + ROLE_ADMIN). */
    public function envoyerMessageContact(ContactData $data): void;
}
