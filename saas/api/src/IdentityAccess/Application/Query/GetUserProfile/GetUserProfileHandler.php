<?php

namespace App\IdentityAccess\Application\Query\GetUserProfile;

use App\Tenancy\Domain\Repository\AccountMemberRepositoryInterface;

final class GetUserProfileHandler
{
    public function __construct(
        private readonly AccountMemberRepositoryInterface $accountMemberRepository,
    ) {
    }

    /** @return array{user: array<string, mixed>, accounts: list<array<string, mixed>>} */
    public function handle(GetUserProfileQuery $query): array
    {
        $user = $query->user;
        $memberships = $this->accountMemberRepository->findActiveByUser($user);

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
            'accounts' => array_map(static fn ($m) => [
                'id' => (string) $m->getAccount()->getId(),
                'name' => $m->getAccount()->getName(),
                'slug' => $m->getAccount()->getSlug(),
                'role' => $m->getRole()->value,
                'shops' => array_map(static fn ($s) => [
                    'id' => (string) $s->getId(),
                    'name' => $s->getName(),
                    'slug' => $s->getSlug(),
                ], $m->getAccount()->getShops()->toArray()),
            ], $memberships),
        ];
    }
}
