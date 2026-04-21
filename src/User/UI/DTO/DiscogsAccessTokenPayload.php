<?php

namespace App\User\UI\DTO;

use Symfony\Component\Validator\Constraints as Assert;

class DiscogsAccessTokenPayload
{
    #[Assert\NotBlank(normalizer: 'trim')]
    #[Assert\Length(max: 256)]
    public string $accessToken;
}
