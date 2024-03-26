<?php

declare(strict_types=1);

namespace Gtt\SyliusRbacPlugin\DependencyInjection;

use Gtt\SyliusRbacPlugin\Entity\AccessItem;
use Gtt\SyliusRbacPlugin\Form\Type\RbacType;
use Gtt\SyliusRbacPlugin\Infrastructure\Repository\AccessItemRepository;
use Symfony\Component\Config\Definition\ConfigurationInterface;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Extension\PrependExtensionInterface;
use Symfony\Component\DependencyInjection\Loader;

class GttSyliusRbacExtension extends Extension implements PrependExtensionInterface
{
    public const RESOURCE_ALIAS = 'gtt_sylius_rbac.access_groups';

    /**
     * @inheritDoc
     */
    public function load(array $configs, ContainerBuilder $container): void
    {
        $config = $this->processConfiguration($this->getConfiguration([], $container), $configs);

        $container->setParameter(Configuration::NODE_CUSTOM_ROUTES, $config[Configuration::NODE_CUSTOM_ROUTES] ?? []);

        $loader = new Loader\PhpFileLoader($container, new FileLocator(__DIR__ . '/../Resources/config'));
        $loader->load('services.php');
    }

    public function prepend(ContainerBuilder $container): void
    {
        $this->processConfiguration(new Configuration(), $container->getExtensionConfig($this->getAlias()));

        if ($container->hasExtension('sylius_resource')) {
            $container->prependExtensionConfig('sylius_resource', [
                'resources' => [
                    self::RESOURCE_ALIAS => [
                        'classes' => [
                            'model' => AccessItem::class,
                            'repository' => AccessItemRepository::class,
                            'form' => RbacType::class,
                        ],
                    ],
                ],
            ]);
        }

        if ($container->hasExtension('twig')) {
            $container->prependExtensionConfig(
                'twig',
                ['paths' => [Configuration::TEMPLATES_DIR => 'GttSyliusRbacPlugin']],
            );
        }

        if ($container->hasExtension('sylius_ui')) {
            $container->prependExtensionConfig('sylius_ui', [
                'events' => [
                    'sylius.admin.admin_user.create.form' => [
                        'blocks' => [
                            'accessCollection' => [
                                'template' => '@GttSyliusRbacPlugin/extension/_accessCollection.html.twig',
                            ],
                        ],
                    ],
                    'sylius.admin.admin_user.update.form' => [
                        'blocks' => [
                            'accessCollection' => [
                                'template' => '@GttSyliusRbacPlugin/extension/_accessCollection.html.twig',
                            ],
                        ],
                    ],
                    'sylius.admin.layout.javascripts' => [
                        'blocks' => [
                            'linksProcessor' => [
                                'template' => '@GttSyliusRbacPlugin/extension/_linksProcessor.html.twig',
                            ],
                        ],
                    ],
                ],
            ]);
        }
    }

    /**
     * @inheritDoc
     */
    // phpcs:ignore SlevomatCodingStandard.Functions.UnusedParameter.UnusedParameter
    public function getConfiguration(array $config, ContainerBuilder $container): ConfigurationInterface
    {
        return new Configuration();
    }
}
