<?php

namespace App\User\Domain\Repository;

use App\User\Domain\User;
use Symfony\Component\Uid\Uuid;

interface UserReaderInterface
{
    public function findUserByUuid(Uuid $uuid): User;

    public function findUserByEmail(string $email): User;
}
