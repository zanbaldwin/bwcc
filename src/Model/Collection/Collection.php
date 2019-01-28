<?php declare(strict_types=1);

namespace App\Model\Collection;

use App\Model\RemoteEntityInterface;
use Doctrine\Common\Collections\ArrayCollection;

class Collection extends ArrayCollection implements CollectionInterface
{
    /** @var string $entityClass */
    private $entityClass;

    public function __construct(string $entityClass, array $elements = [])
    {
        if (!\is_a($entityClass, RemoteEntityInterface::class, true)) {
            throw new \RuntimeException(\sprintf(
                'Cannot create collection for non-remote entity type "%s".',
                $entityClass
            ));
        }
        $this->entityClass = $entityClass;
        parent::__construct($elements);
    }

    public function getEntityClass(): string
    {
        return $this->entityClass;
    }
}
