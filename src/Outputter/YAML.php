<?php declare(strict_types=1);

namespace App\Outputter;

class YAML extends AbstractOutputter
{
    public static function getFormat(): string
    {
        return 'yaml';
    }

    public function flushToDisk(): \SplFileObject
    {
    }
}
