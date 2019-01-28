<?php declare(strict_types=1);

namespace App\Outputter\Format;

use App\Model\EntityInterface;
use App\Outputter\AbstractOutputter;
use League\Csv\Writer;

class CSV extends AbstractOutputter
{
    public static function getFormat(): string
    {
        return 'csv';
    }

    public function flushToDisk(): array
    {
        $outputFiles = [];
        /** @var \App\Model\Collection\CollectionInterface $collection */
        foreach ($this->collections as $collection) {
            $file = $this->createFileHandle($collection->getCollectionName());
            $writer = Writer::createFromFileObject($file);
            $writer->insertAll($collection->map(function (EntityInterface $entity): array {
                return $entity->getData();
            }));
            $outputFiles[] = $file;
        }
        return $outputFiles;
    }
}
