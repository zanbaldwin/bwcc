<?php declare(strict_types=1);

namespace App\Outputter\Format;

use App\Outputter\AbstractOutputter;

class SQLite extends AbstractOutputter
{
    public static function getFormat(): string
    {
        return 'sqlite';
    }

    public function flushToDisk(): array
    {
    }
}
