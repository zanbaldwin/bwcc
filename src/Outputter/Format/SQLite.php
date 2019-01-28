<?php declare(strict_types=1);

namespace App\Outputter\Format;

use App\Outputter\AbstractOutputter;

/**
 * FOR DEMONSTRATION PURPOSES ONLY.
 * This outputter for SQLite contains really bad practices, which are not ideal for production.
 */
class SQLite extends AbstractOutputter
{
    public static function getFormat(): string
    {
        return 'sqlite';
    }

    public function flushToDisk(): array
    {
        $outputFile = $this->createFileHandle('import', 'db');
        $pdo = new \PDO('sqlite:' . $outputFile->getRealPath());
        /** @var \App\Model\Collection\CollectionInterface $collection */
        foreach ($this->collections as $collection) {
            /** @var \App\Outputter\Format\SQLiteAwareInterface|string $entityClass */
            if (!\is_a($entityClass = $collection->getEntityClass(), SQLiteAwareInterface::class, true)) {
                continue;
            }
            $entityClass::createTable($pdo);
            /** @var \App\Model\RemoteEntityInterface|\App\Outputter\Format\SQLiteAwareInterface $entity */
            foreach ($collection as $entity) {
                $columns = $entity->getDatabaseColumns($pdo);
                $sql = \sprintf(
                    'INSERT INTO %s (%s) VALUES (%s);',
                    $entity::getCollectionName(),
                    '`' . implode('`, `', \array_keys($columns)) . '`',
                    \implode(', ', \array_values($columns))
                );
                $pdo->exec($sql);
            }
        }
        return [$outputFile];
    }
}
