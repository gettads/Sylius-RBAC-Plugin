<?php

declare(strict_types=1);

namespace Gtt\SyliusRbacPlugin\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

final class Configuration implements ConfigurationInterface
{
    public const TEMPLATES_DIR = __DIR__ . '/../Resources/templates';
    public const NODE_ROOT = 'gtt_sylius_rbac';
    public const NODE_CUSTOM_ROUTES = 'custom_routes';
    public const NODE_CUSTOM_LABEL = 'label';
    public const NODE_CUSTOM_ROUTE = 'route';
    public const NODE_CUSTOM_MATCH = 'match';

    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder(self::NODE_ROOT);

        // @phpstan-ignore-next-line
        $treeBuilder->getRootNode()
            ->children()
                ->arrayNode(self::NODE_CUSTOM_ROUTES)
                    ->performNoDeepMerging()
                    ->prototype('array')
                        // @phpstan-ignore-next-line
                        ->children()
                            ->scalarNode(self::NODE_CUSTOM_LABEL)->end()
                            ->scalarNode(self::NODE_CUSTOM_ROUTE)->cannotBeEmpty()->end()
                            ->scalarNode(self::NODE_CUSTOM_MATCH)->end()
                        ->end()
                    ->end()
                ->end()
            ->end();

        return $treeBuilder;
    }
}
