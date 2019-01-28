<?php declare(strict_types=1);

namespace App\Outputter;

class JSON extends AbstractOutputter
{
    public static function getFormat(): string
    {
        return 'json';
    }

    public function flushToDisk(): \SplFileObject
    {
    }
}
