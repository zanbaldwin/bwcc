<?php declare(strict_types=1);

namespace App\Outputter\Format;

use App\Model\EntityInterface;
use App\Outputter\AbstractOutputter;

class JSON extends AbstractOutputter
{
    public static function getFormat(): string
    {
        return 'json';
    }

    public function flushToDisk(): array
    {
        $outputFiles = [];
        /** @var \App\Model\Collection\CollectionInterface $collection */
        foreach ($this->collections as $collection) {
            $file = $this->createFileHandle($collection->getCollectionName());
            \file_put_contents(
                $file->getRealPath(),
                \json_encode($collection->map(function (EntityInterface $entity): array {
                    return $entity->getData();
                }))
            );
            $outputFiles[] = $file;
        }
        return $outputFiles;
    }
}
