<?php

namespace Phunk\Cms;

use Phunk\Cms\Command\{NginxCacheClear, PhunkBuild};
use Symfony\Component\Config\Definition\Configurator\DefinitionConfigurator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\HttpKernel\Bundle\AbstractBundle;

class PhunkCmsBundle extends AbstractBundle
{
    public function configure(DefinitionConfigurator $definition): void
    {
        $definition->rootNode()
            ->children()
                ->scalarNode('content_path')->isRequired()->cannotBeEmpty()->end()
                ->scalarNode('image_path')->isRequired()->cannotBeEmpty()->end()
                ->scalarNode('image_cache_path')->isRequired()->cannotBeEmpty()->end()
                ->variableNode('nginx_cache_clear_command')->isRequired()->end()
            ->end()
        ;
    }

    public function loadExtension(array $config, ContainerConfigurator $container, ContainerBuilder $builder): void
    {
        $container->import('../config/services.yaml');

        $services = $container->services();
        $services->get(ContentParser::class)->arg('$path', $config['content_path']);
        $services->get(ImageServer::class)
            ->arg('$source', $config['image_path'])
            ->arg('$cache', $config['image_cache_path'])
        ;
        $services->get(NginxCacheClear::class)->arg('$clearCacheCommand', $config['nginx_cache_clear_command']);
        $services->get(PhunkBuild::class)->arg('$clearCacheCommand', $config['nginx_cache_clear_command']);
    }
}
