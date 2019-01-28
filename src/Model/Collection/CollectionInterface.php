<?php declare(strict_types=1);

namespace App\Model\Collection;

use Doctrine\Common\Collections\Collection;

interface CollectionInterface extends Collection
{
    public function getCollectionName(): string;
}
