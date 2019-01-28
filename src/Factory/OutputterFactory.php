<?php declare(strict_types=1);

namespace App\Factory;

use App\Outputter\OutputterInterface;
use Psr\Container\ContainerInterface;

class OutputterFactory
{
    /** @var \Psr\Container\ContainerInterface $serviceLocator */
    private $serviceLocator;
    /** @var array $formats */
    private $formats = [];

    public function __construct(ContainerInterface $serviceLocator, array $registeredFormats)
    {
        $this->serviceLocator = $serviceLocator;
        $this->formats = $registeredFormats;
    }

    public function createForFormat(string $format): OutputterInterface
    {
        if (!$this->serviceLocator->has($format)) {
            throw new \InvalidArgumentException(\sprintf('Outputter for format "%s" not registered.', $format));
        }
        return $this->serviceLocator->get($format);
    }

    public function getValidFormats(): array
    {
        return $this->formats;
    }
}
