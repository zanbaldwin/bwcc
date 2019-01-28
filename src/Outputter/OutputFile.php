<?php declare(strict_types=1);

namespace App\Outputter;

class OutputFile extends \SplFileObject
{
    /** @var string $importHash */
    private $importHash;

    public function __construct(string $rootImportDirectory, string $importHash, string $filename)
    {
        $this->importHash = $importHash;
        parent::__construct(\sprintf(
            '%s/%s/%s',
            $rootImportDirectory,
            $importHash,
            $filename
        ), 'w+');
    }

    public function getImportHash(): string
    {
        return $this->importHash;
    }
}
