<?php

declare(strict_types=1);

namespace App\Modules\Indexing\Domain\ValueObject;

use InvalidArgumentException;

final class Embedding
{
    /**
     * @param list<float> $vector
     */
    private function __construct(private readonly array $vector)
    {
        if ($vector === []) {
            throw new InvalidArgumentException('Embedding vector cannot be empty.');
        }
    }

    /**
     * @param list<float|int|string> $values
     */
    public static function fromArray(array $values): self
    {
        $vector = array_map(
            static fn (float|int|string $value): float => (float) $value,
            $values,
        );

        return new self($vector);
    }

    /**
     * @return list<float>
     */
    public function toArray(): array
    {
        return $this->vector;
    }
}
