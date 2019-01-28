<?php declare(strict_types=1);

namespace App\DependencyInjection\Compiler;

use App\Factory\OutputterFactory;
use App\Outputter\OutputterInterface;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\Compiler\ServiceLocatorTagPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

class OutputterFactoryPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container)
    {
        if (!$container->hasDefinition(OutputterFactory::class)) {
            return;
        }

        $factory = $container->getDefinition(OutputterFactory::class);
        $outputterDefinitions = $container->findTaggedServiceIds(OutputterInterface::class, true);
        $factoryServices = [];
        foreach ($outputterDefinitions as $id => $tags) {
            /** @var \App\Outputter\OutputterInterface|string $outputterClass */
            $outputterClass = $container->getDefinition($id)->getClass();
            if (!\is_a($outputterClass, OutputterInterface::class, true)) {
                continue;
            }
            $factoryServices[$outputterClass::getFormat()] = new Reference($id);
        }
        $factory->setArguments([
            ServiceLocatorTagPass::register($container, $factoryServices),
            \array_keys($factoryServices),
        ]);
    }
}
