<?php

namespace App\Contract;

use App\DTO\ContactData;

interface ContactMailerServiceInterface
{
    public function envoyerMessageContact(ContactData $data): void;
}
