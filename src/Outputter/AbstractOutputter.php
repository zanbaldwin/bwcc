<?php declare(strict_types=1);

namespace App\Outputter;

use App\Model\Collection\CollectionInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

abstract class AbstractOutputter implements OutputterInterface
{
    protected const IMPORT_DIRECTORY = '%kernel.project_dir%/var/imports';

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

    protected function generateFilename(?string $ext = null): string
    {
        $directory = $this->getDirectory();
        do {
            $file = $directory . '/' . \sha1(\microtime(true)) . '.' . ($ext ?? static::getFormat());
        } while (\file_exists($file));
        return $file;
    }

    protected function getDirectory(): string
    {
        $directory = $this->parameterBag->resolveValue(static::IMPORT_DIRECTORY);
        if (\file_exists($directory) && (!\is_dir($directory) || !\is_writable($directory))) {
            throw new \RuntimeException('Import directory "%s" is not a writable directory.');
        } elseif (!\file_exists($directory) && !@\mkdir($directory, 0755, true)) {
            throw new \RuntimeException('Could not create import directory "%s" for writing.');
        }
        return \rtrim($directory, '/');
    }
}
