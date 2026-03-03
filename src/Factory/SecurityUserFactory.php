<?php

namespace App\Factory;

use App\User\Infra\Security\SecurityUser;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Uid\Uuid;
use Zenstruck\Foundry\Persistence\PersistentObjectFactory;

/**
 * @extends PersistentObjectFactory<SecurityUser>
 */
final class SecurityUserFactory extends PersistentObjectFactory
{
    private const array USER_EMAILS = [
        'alice.smith@gmail.com',
        'bob.johnson@yahoo.com',
        'charlie.williams@hotmail.com',
        'diana.brown@outlook.com',
        'emma.jones@protonmail.com',
        'frank.garcia@icloud.com',
        'grace.miller@gmail.com',
        'henry.davis@yahoo.com',
        'isabella.rodriguez@hotmail.com',
        'jack.martinez@outlook.com',
        'kate.hernandez@protonmail.com',
        'liam.lopez@icloud.com',
        'mia.wilson@gmail.com',
        'noah.anderson@yahoo.com',
        'olivia.thomas@hotmail.com',
    ];

    public function __construct(
        private UserPasswordHasherInterface $passwordHasher,
    ) {
        parent::__construct();
    }

    #[\Override]
    public static function class(): string
    {
        return SecurityUser::class;
    }

    #[\Override]
    protected function defaults(): array|callable
    {
        return [
            'uuid' => Uuid::fromString(self::faker()->uuid()),
            'email' => self::faker()->unique()->randomElement(self::USER_EMAILS),
            'password' => 'password123',
            'roles' => ['ROLE_USER'],
        ];
    }

    #[\Override]
    protected function initialize(): static
    {
        return $this->afterInstantiate(function (SecurityUser $securityUser): void {
            $hashedPassword = $this->passwordHasher->hashPassword($securityUser, $securityUser->getPassword());
            $user = $securityUser->toDomain();
            $user->update(
                $user->getEmail(),
                $user->getRoles(),
                $hashedPassword,
            );
            $securityUser->updateFromDomain($user);
        });
    }
}
