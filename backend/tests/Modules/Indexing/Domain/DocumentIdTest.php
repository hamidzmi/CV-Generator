<?php

declare(strict_types=1);

namespace App\Tests\Modules\Indexing\Domain;

use App\Modules\Indexing\Domain\ValueObject\DocumentId;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Uid\Uuid;

final class DocumentIdTest extends TestCase
{
    public function testItGeneratesUuidWhenInputIsNotUuid(): void
    {
        $id = DocumentId::fromString('demo-identifier');

        self::assertTrue(Uuid::isValid($id->toString()));
        self::assertSame('demo-identifier', $id->original());
    }

    public function testItKeepsGivenUuid(): void
    {
        $uuid = Uuid::v4()->toRfc4122();

        $id = DocumentId::fromString($uuid);

        self::assertSame($uuid, $id->toString());
        self::assertSame($uuid, $id->original());
    }

    public function testEmptyValueThrowsException(): void
    {
        $this->expectException(InvalidArgumentException::class);

        DocumentId::fromString('');
    }
}
