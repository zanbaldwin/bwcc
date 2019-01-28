<?php declare(strict_types=1);

namespace App\Outputter;

use App\Model\Collection\CollectionInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

abstract class AbstractOutputter implements OutputterInterface
{
    public const IMPORT_DIRECTORY_ROOT = '%kernel.project_dir%/var/imports';

    /** @var string $importHash */
    private $importHash;

    /** @var \Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface $parameterBag */
    private $parameterBag;

    /** @var \App\Model\Collection\CollectionInterface[] $collections */
    protected $collections = [];

    public function persistCollection(CollectionInterface $collection): void
    {
        $this->collections[] = $collection;
    }

    /** @required */
    public function setParameterBag(ParameterBagInterface $parameterBag): void
    {
        $this->parameterBag = $parameterBag;
    }

    protected function createFileHandle(string $collection, ?string $ext = null): OutputFile
    {
        return new OutputFile(
            $this->getImportDirectoryRoot(),
            $this->importHash ?? $this->generateImportHashDirectory(),
            $collection . '.' . ($ext ?? static::getFormat())
        );
    }

    protected function generateImportHashDirectory(): string
    {
        $root = $this->getImportDirectoryRoot();
        do {
            $importHash = \sha1(\microtime());
            $directory = $root . '/' . $importHash;
        } while (\file_exists($directory));
        if (!@\mkdir($directory, 0755, true)) {
            throw new \RuntimeException(\sprintf('Could not create import directory "%s" for writing.', $directory));
        }
        return $this->importHash = $importHash;
    }

    private function getImportDirectoryRoot(): string
    {
        $directory = $this->parameterBag->resolveValue(static::IMPORT_DIRECTORY_ROOT);
        if (\file_exists($directory) && (!\is_dir($directory) || !\is_writable($directory))) {
            throw new \RuntimeException('Import directory "%s" is not a writable directory.');
        } elseif (!\file_exists($directory) && !@\mkdir($directory, 0755, true)) {
            throw new \RuntimeException('Could not create root import directory "%s" for writing.');
        }
        return \rtrim($directory, '/');
    }
}
