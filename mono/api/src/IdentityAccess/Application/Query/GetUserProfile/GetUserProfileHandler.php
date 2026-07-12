<?php

namespace App\IdentityAccess\Application\Query\GetUserProfile;

final class GetUserProfileHandler
{
    /** @return array{user: array<string, mixed>} */
    public function handle(GetUserProfileQuery $query): array
    {
        $user = $query->user;

        return [
            'user' => [
                'id' => (string) $user->getId(),
                'email' => $user->getEmail(),
                'username' => $user->getUsername(),
                'first_name' => $user->getFirstName(),
                'last_name' => $user->getLastName(),
                'status' => $user->getStatus()->value,
                'roles' => $user->getRoles(),
            ],
        ];
    }
}
