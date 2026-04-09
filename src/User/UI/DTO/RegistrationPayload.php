<?php

namespace App\User\UI\DTO;

use Symfony\Component\Validator\Constraints as Assert;

final class RegistrationPayload
{
    #[Assert\NotBlank()]
    #[Assert\Email()]
    public string $email;

    #[Assert\NotBlank()]
    #[Assert\Length(min: 8, minMessage: 'Your password must be at least 8 characters long')]
    public string $password;
}
