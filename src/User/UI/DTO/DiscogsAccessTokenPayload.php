<?php

namespace App\User\UI\DTO;

use Symfony\Component\Validator\Constraints as Assert;

class DiscogsAccessTokenPayload
{
    #[Assert\NotBlank()]
    public string $accessToken;
}
