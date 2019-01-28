<?php declare(strict_types=1);

namespace App\Outputter;

interface OutputterFactoryInterface
{
    public function createForFormat(string $format): OutputterInterface;
    /** @return string[] */
    public function getValidFormats(): array;
}
