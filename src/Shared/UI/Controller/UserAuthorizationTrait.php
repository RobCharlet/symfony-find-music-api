<?php

namespace App\Shared\UI\Controller;

use App\Shared\Domain\UuidAwareUserInterface;
use App\Shared\UI\UserAuthorization;

trait UserAuthorizationTrait
{
    private function getUserAuthorization(): UserAuthorization
    {
        $securityUser = $this->getUser();

        if (!$securityUser instanceof UuidAwareUserInterface) {
            throw $this->createAccessDeniedException();
        }

        $userUuid = $securityUser->getUuid();
        $isAdmin  = $this->isGranted('ROLE_ADMIN');

        return new UserAuthorization($userUuid, $isAdmin);
    }
}
