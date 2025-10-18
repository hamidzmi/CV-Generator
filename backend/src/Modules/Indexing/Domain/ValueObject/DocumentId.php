<?php

declare(strict_types=1);

namespace App\Modules\Indexing\Domain\ValueObject;

use InvalidArgumentException;
use Symfony\Component\Uid\Uuid;

final class DocumentId
{
    private const NAMESPACE_UUID = 'd399505d-2f32-4966-9ad4-9806de8be7ec';

    private function __construct(
        private readonly string $value,
        private readonly string $original,
    ) {}

    public static function fromString(string $value): self
    {
        if ($value === '') {
            throw new InvalidArgumentException('Document id cannot be empty.');
        }

        $original = $value;

        if (!Uuid::isValid($value)) {
            $value = Uuid::v5(Uuid::fromString(self::NAMESPACE_UUID), $value)->toRfc4122();
        }

        return new self($value, $original);
    }

    public function equals(self $other): bool
    {
        return $other->value === $this->value;
    }

    public function toString(): string
    {
        return $this->value;
    }

    public function __toString(): string
    {
        return $this->value;
    }

    public function original(): string
    {
        return $this->original;
    }
}
