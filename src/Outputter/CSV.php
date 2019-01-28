<?php declare(strict_types=1);

namespace App\Outputter;

class CSV extends AbstractOutputter
{
    public static function getFormat(): string
    {
        return 'csv';
    }

    public function flushToDisk(): \SplFileObject
    {
    }
}
