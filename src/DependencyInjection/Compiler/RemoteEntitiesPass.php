<?php declare(strict_types=1);

namespace App\DependencyInjection\Compiler;

use App\Model\RemoteEntityInterface;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class RemoteEntitiesPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container)
    {
        $entities = [];
        $entityServices = $container->findTaggedServiceIds(RemoteEntityInterface::class, true);
        foreach ($entityServices as $id => $tags) {
            /** @var \App\Model\RemoteEntityInterface $entityClass */
            $entityClass = $container->getDefinition($id)->getClass();
            if (!\is_a($entityClass, RemoteEntityInterface::class, true)) {
                continue;
            }
            $entities[] = $entityClass;
        }
        $container->setParameter('remote_entity_classes', $entities);
    }
}
