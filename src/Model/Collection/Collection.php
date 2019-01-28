<?php declare(strict_types=1);

namespace App\Model\Collection;

use App\Model\RemoteEntityInterface;
use Doctrine\Common\Collections\ArrayCollection;

class Collection extends ArrayCollection implements CollectionInterface
{
    /** @var string $name */
    private $name;

    public function __construct(string $entityClass, array $elements = [])
    {
        if (!\is_a($entityClass, RemoteEntityInterface::class, true)) {
            throw new \RuntimeException(\sprintf(
                'Cannot create collection for non-remote entity type "%s".',
                $entityClass
            ));
        }
        /** @var \App\Model\RemoteEntityInterface|string $entityClass */
        $this->name = $entityClass::getCollectionName();
        parent::__construct($elements);
    }

    public function getCollectionName(): string
    {
        return $this->name;
    }

    public function toArray(): array
    {
        return parent::toArray();
    }
}
