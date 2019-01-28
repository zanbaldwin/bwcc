<?php declare(strict_types=1);

namespace App\Outputter;

class SQLite extends AbstractOutputter
{
    public static function getFormat(): string
    {
        return 'sqlite';
    }

    public function flushToDisk(): \SplFileObject
    {
    }
}
