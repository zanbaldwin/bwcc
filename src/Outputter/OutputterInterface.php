<?php declare(strict_types=1);

namespace App\Outputter;

use App\Model\Collection\CollectionInterface;

interface OutputterInterface
{
    public static function getFormat(): string;
    public function persistCollection(CollectionInterface $collection): void;
    public function flushToDisk(): \SplFileObject;
}
