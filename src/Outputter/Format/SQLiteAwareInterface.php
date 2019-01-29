<?php declare(strict_types=1);

namespace App\Outputter\Format;

interface SQLiteAwareInterface
{
    public static function createTable(\PDO $pdo): void;
    public function getDatabaseColumns(\PDO $pdo): array;
}
