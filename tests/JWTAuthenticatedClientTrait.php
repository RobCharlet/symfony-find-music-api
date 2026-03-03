<?php

namespace App\Tests;

use App\Factory\SecurityUserFactory;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;

trait JWTAuthenticatedClientTrait
{
    protected function createJWTAuthenticatedClient(array $roles = ['ROLE_USER']): KernelBrowser
    {
        static::ensureKernelShutdown();
        $client = static::createClient();

        $user = SecurityUserFactory::createOne([
            'email' => 'user'.uniqid().'@user.com',
            'password' => 'password',
            'roles' => $roles,
        ]);

        $jwtManager = $client->getContainer()->get(JWTTokenManagerInterface::class);
        $token = $jwtManager->create($user);

        $client->setServerParameter('HTTP_AUTHORIZATION', sprintf('Bearer %s', $token));

        return $client;
    }
}
