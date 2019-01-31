<?php declare(strict_types=1);

namespace App\Tests\Serializer;

use App\Serializer\UuidNormalizer;
use PHPUnit\Framework\TestCase;
use Ramsey\Uuid\Converter\NumberConverterInterface;
use Ramsey\Uuid\Exception\UnsupportedOperationException;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;
use Symfony\Component\Serializer\Exception\InvalidArgumentException;
use Symfony\Component\Serializer\Exception\RuntimeException;
use Symfony\Component\Serializer\Exception\UnexpectedValueException;

class UuidNormalizerTest extends TestCase
{
    /** @var \App\Serializer\UuidNormalizer $normalizer */
    private $normalizer;

    public function setUp(): void
    {
        $this->normalizer = new UuidNormalizer;
    }

    public function testNormalizationSupport(): void
    {
        $this->assertTrue($this->normalizer->supportsNormalization(Uuid::uuid4()));
        $this->assertFalse($this->normalizer->supportsNormalization(Uuid::NIL));
    }

    public function testDenormalizationSupport(): void
    {
        $invalidUuid = '00000000-0000-400g-0000-000000000000';
        $this->assertFalse($this->normalizer->supportsDenormalization(Uuid::NIL, null));
        $this->assertFalse($this->normalizer->supportsDenormalization(Uuid::NIL, \LogicException::class));
        $this->assertFalse($this->normalizer->supportsDenormalization($invalidUuid, UuidInterface::class));
        $this->assertFalse($this->normalizer->supportsDenormalization($invalidUuid, Uuid::class));
        $this->assertTrue($this->normalizer->supportsDenormalization(Uuid::NIL, UuidInterface::class));
    }

    public function dataProviderInvalidUuids(): array
    {
        return [
            [new \LogicException],
            ['123'],
            [123],
            [null],
        ];
    }

    /** @dataProvider dataProviderInvalidUuids */
    public function testExceptionIsThrownWhenNonUuidIsNormalized($value): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Cannot normalize a non-UUID object into a string.');
        $this->normalizer->normalize($value);
    }

    public function testUuidStringIsReturnedFromNormalization(): void
    {
        $uuid = Uuid::NIL;
        $this->assertSame($uuid, $this->normalizer->normalize(Uuid::fromString($uuid)));
    }

    public function testExceptionIsThrownIfAttemptingToNormalizeNonString(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Cannot normalize a non-UUID object into a string.');
        $this->normalizer->normalize(123);
    }

    public function testExceptionIsThrownTryingToDenormalizeNonString(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('UUID objects can only be constructed from strings.');
        $this->normalizer->denormalize(123, UuidInterface::class);
    }

    public function testExceptionIsThrownWhenClassDoesNotImplementInterface(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Target denormalization type "LogicException" does not implement "Ramsey\Uuid\UuidInterface".');
        $this->normalizer->denormalize(Uuid::NIL, \LogicException::class);
    }

    public function testExceptionIsThrownIfClassDoesNotExist(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Target denormalization type "App\Tests\Serializer\NonExistent" does not exist.');
        $this->normalizer->denormalize(Uuid::NIL, NonExistent::class);
    }

    public function testExceptionIsThrownIsClassDoesNotPubliclyImplementMethod(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Target denormalization type "App\Tests\Serializer\IncompleteUuidImplementation" does not publicly implement "fromString" method.');
        $this->normalizer->denormalize(Uuid::NIL, IncompleteUuidImplementation::class);
    }

    public function testExceptionIsThrownWhenUuidIsNotValid(): void
    {
        $this->expectException(UnexpectedValueException::class);
        $this->expectExceptionMessage('Value provided is not a valid UUID.');
        $this->normalizer->denormalize('asd', Uuid::class);
    }

    public function testUuidIsCorrectlyDenormalized(): void
    {
        $uuid = $this->normalizer->denormalize(Uuid::NIL, Uuid::class);
        $this->assertInstanceOf(Uuid::class, $uuid);
        $this->assertSame(Uuid::NIL, $uuid->toString());
    }

    public function testInterfaceIsCorrectlyDenormalizedIntoConcreteClass(): void
    {
        $uuid = $this->normalizer->denormalize(Uuid::NIL, UuidInterface::class);
        $this->assertInstanceOf(Uuid::class, $uuid);
    }
}
