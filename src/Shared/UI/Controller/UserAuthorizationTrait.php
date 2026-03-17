<?php

namespace App\Shared\UI\Controller;

use App\Shared\UI\UserAuthorization;
use App\User\Infra\Security\SecurityUser;

trait UserAuthorizationTrait
{
    private function getUserAuthorization(): UserAuthorization
    {
        $securityUser = $this->getUser();

        if (!$securityUser instanceof SecurityUser) {
            throw $this->createAccessDeniedException();
        }

        $userUuid = $securityUser->getUuid();
        $isAdmin  = $this->isGranted('ROLE_ADMIN');

        return new UserAuthorization($userUuid, $isAdmin);
    }
}
