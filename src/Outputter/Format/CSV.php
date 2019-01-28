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
                return $this->stringifyNestedData($entity->getData());
            }));
            $outputFiles[] = $file;
        }
        return $outputFiles;
    }

    /**
     * CSV encoding cannot handle nested data, encode into JSON so it can be
     * processed by the consumer of what deals with this imported data.
     */
    private function stringifyNestedData(array $data): array
    {
        return \array_map(function ($data) {
            if (\is_array($data)) {
                return \json_encode($data);
            }
            return $data;
        }, $data);
    }
}
