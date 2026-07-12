<?php

namespace App\IdentityAccess\Domain\Repository;

use App\IdentityAccess\Domain\Entity\User;
use Symfony\Component\Uid\Uuid;

interface UserRepositoryInterface
{
    public function findByEmail(string $email): ?User;

    public function findByUsername(string $username): ?User;

    public function findById(Uuid $id): ?User;

    public function save(User $user, bool $flush = true): void;
}
