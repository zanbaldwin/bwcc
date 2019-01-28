<?php declare(strict_types=1);

namespace App\Outputter\Format;

use App\Model\EntityInterface;
use App\Outputter\AbstractOutputter;
use Symfony\Component\Yaml\Dumper as YamlDumper;

class YAML extends AbstractOutputter
{
    public static function getFormat(): string
    {
        return 'yaml';
    }

    public function flushToDisk(): array
    {
        $outputFiles = [];
        /** @var \App\Model\Collection\CollectionInterface $collection */
        foreach ($this->collections as $collection) {
            $file = $this->createFileHandle($collection->getCollectionName());
            \file_put_contents(
                $file->getRealPath(),
                (new YamlDumper)->dump($collection->map(function (EntityInterface $entity): array {
                    return $entity->getData();
                })->toArray(), 5)
            );
            $outputFiles[] = $file;
        }
        return $outputFiles;
    }
}
