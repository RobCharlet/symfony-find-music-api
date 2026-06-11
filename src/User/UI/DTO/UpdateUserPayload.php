<?php

namespace App\User\UI\DTO;

final class UpdateUserPayload
{
    public ?string $email = null;

    public ?string $password = null;

    public ?string $currentPassword = null;

    public ?array $roles = null;

    public ?bool $isPublic = null;
}
