<?php declare(strict_types=1);

namespace App\Outputter;

use App\Model\Collection\CollectionInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

abstract class AbstractOutputter implements OutputterInterface
{
    protected const IMPORT_DIRECTORY_ROOT = '%kernel.project_dir%/var/imports';

    /** @var string $importDirectory */
    private $importDirectory;

    /** @var \Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface $parameterBag */
    private $parameterBag;
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

    protected function createFileHandle(string $collection, ?string $ext = null): \SplFileObject
    {
        return new \SplFileObject(\sprintf(
            '%s/%s.%s',
            $this->importDirectory ?? $this->generateImportDirectory(),
            $collection,
            $ext ?? static::getFormat()
        ), 'w+');
    }

    protected function generateImportDirectory(): string
    {
        $root = $this->getImportDirectoryRoot();
        do {
            $directory = $root . '/' . \sha1(\microtime(true));
        } while (\file_exists($directory));
        if (!@\mkdir($directory, 0755, true)) {
            throw new \RuntimeException(\sprintf('Could not create import directory "%s" for writing.', $directory));
        }
        return $this->importDirectory = $directory;
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
