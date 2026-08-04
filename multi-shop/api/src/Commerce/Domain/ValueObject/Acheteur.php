<?php

namespace App\Commerce\Domain\ValueObject;

use Symfony\Component\Uid\Uuid;

/**
 * Encapsulates the buyer of a commercial operation. It holds either a
 * registered ClientId or free-form anonymous details, never both and never none.
 */
final readonly class Acheteur
{
    private function __construct(
        private ?Uuid $clientId,
        private ?string $anonymousInfo,
    ) {
    }

    public static function fromClient(Uuid $clientId): self
    {
        return new self($clientId, null);
    }

    public static function anonymous(string $anonymousInfo): self
    {
        $anonymousInfo = trim($anonymousInfo);
        if ('' === $anonymousInfo) {
            throw new \InvalidArgumentException('Anonymous buyer info cannot be empty.');
        }

        return new self(null, $anonymousInfo);
    }

    /**
     * @param array{client_id?: string|null, anonymous_info?: string|null} $data
     */
    public static function fromArray(array $data): self
    {
        $clientId = $data['client_id'] ?? null;
        $anonymousInfo = $data['anonymous_info'] ?? null;

        if (!empty($clientId) && !empty($anonymousInfo)) {
            throw new \InvalidArgumentException('A buyer cannot be both a registered client and anonymous.');
        }
        if (!empty($clientId)) {
            return self::fromClient(Uuid::fromString($clientId));
        }
        if (!empty($anonymousInfo)) {
            return self::anonymous((string) $anonymousInfo);
        }

        throw new \InvalidArgumentException('A buyer must be either a registered client or anonymous.');
    }

    public function clientId(): ?Uuid
    {
        return $this->clientId;
    }

    public function anonymousInfo(): ?string
    {
        return $this->anonymousInfo;
    }

    public function isRegistered(): bool
    {
        return null !== $this->clientId;
    }

    /** @return array{client_id: string|null, anonymous_info: string|null} */
    public function toArray(): array
    {
        return [
            'client_id' => $this->clientId ? (string) $this->clientId : null,
            'anonymous_info' => $this->anonymousInfo,
        ];
    }
}
