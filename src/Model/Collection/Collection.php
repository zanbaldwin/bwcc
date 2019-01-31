<?php declare(strict_types=1);

namespace App\Model\Collection;

use App\Model\RemoteEntityInterface;
use Doctrine\Common\Collections\ArrayCollection;

class Collection extends ArrayCollection implements CollectionInterface
{
    /** @var string $entityClass */
    private $entityClass;
    /** @var string $name */
    private $name;

    public function __construct(array $elements = [], ?string $entityClass = null)
    {
        if (\is_string($entityClass)) {
            if (!\is_a($entityClass, RemoteEntityInterface::class, true)) {
                throw new \RuntimeException(\sprintf(
                    'Cannot create collection for non-remote entity type "%s".',
                    $entityClass
                ));
            }
            $this->entityClass = $entityClass;
            /** @var \App\Model\RemoteEntityInterface|string $entityClass */
            $this->name = $entityClass::getCollectionName();
        }
        parent::__construct($elements);
    }

    public function getEntityClass(): string
    {
        if (!\is_string($this->name)) {
            throw new \LogicException('Entity class not set, use before modification creates new instance.');
        }
        return $this->entityClass;
    }

    /**
     * Doctrine will not pass the collection name when creating a new instance
     * due to modification (for example, the map() method). Make sure to use the
     * collection name as soon as possible.
     */
    public function getCollectionName(): string
    {
        if (!\is_string($this->name)) {
            throw new \LogicException('Collection name not set, use before modification creates new instance.');
        }
        return $this->name;
    }

    public function toArray(): array
    {
        return parent::toArray();
    }
}
