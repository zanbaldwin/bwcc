<?php declare(strict_types=1);

namespace App\Model\Collection;

use App\Model\RemoteEntityInterface;
use PHPUnit\Framework\TestCase;

class CollectionTest extends TestCase
{
    /** @var \App\Model\RemoteEntityInterface $entity */
    private $entity;

    public function setUp(): void
    {
        $this->entity = new class implements RemoteEntityInterface {
            public function getData(): array
            {
                return [];
            }

            public static function getCollectionName(): string
            {
                return 'example-entity-collection';
            }

            public static function getRemoteUrl(): string
            {
                return 'https://example.com/api/collection';
            }

            public static function extract(array $data): array
            {
                return $data;
            }
        };
    }

    public function testEntityIsSetInCollection(): void
    {
        $collection = new Collection([], $entityClass = \get_class($this->entity));
        $this->assertSame($entityClass, $collection->getEntityClass());
        $this->assertSame('example-entity-collection', $collection->getCollectionName());
    }

    public function testExceptionIsThrownWhenClassSuppliedIsNotRemoteEntity(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Cannot create collection for non-remote entity type "InvalidArgumentException".');
        new Collection([], \InvalidArgumentException::class);
    }

    public function testExceptionIsThrownGettingEntityClassIfNotSet(): void
    {
        $collection = new Collection;
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Entity class not set, use before modification creates new instance.');
        $collection->getEntityClass();
    }

    public function testExceptionIsThrownGettingCollectionNameIfNotSet(): void
    {
        $collection = new Collection;
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Collection name not set, use before modification creates new instance.');
        $collection->getCollectionName();
    }

    public function testElementsAreReturnedAsExpected(): void
    {
        $element = new \stdClass;
        $collection = new Collection([$element]);
        $this->assertSame([$element], $collection->toArray());
    }
}
