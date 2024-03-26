<?php

declare(strict_types=1);

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

use Gtt\SyliusRbacPlugin\Controller\EmptyPageController;
use Gtt\SyliusRbacPlugin\DependencyInjection\Configuration;
use Gtt\SyliusRbacPlugin\Entity\AccessItem;
use Gtt\SyliusRbacPlugin\EventListener\AdminMenuListener;
use Gtt\SyliusRbacPlugin\EventListener\AdminMenuSecurityListener;
use Gtt\SyliusRbacPlugin\EventListener\AdminRequestSecurityListener;
use Gtt\SyliusRbacPlugin\Form\EventSubscriber\RbacFormSubscriber;
use Gtt\SyliusRbacPlugin\Form\Extension\AdminUserTypeExtension;
use Gtt\SyliusRbacPlugin\Form\Type\RbacType;
use Gtt\SyliusRbacPlugin\Form\Type\RoutePermissionChoiceType;
use Gtt\SyliusRbacPlugin\Grid\RbacGrid;
use Gtt\SyliusRbacPlugin\Infrastructure\Environment\Configurator;
use Gtt\SyliusRbacPlugin\Infrastructure\Factory\RoutePermissionFactory;
use Gtt\SyliusRbacPlugin\Twig\UrlDecoratorExtension;
use Sylius\Bundle\AdminBundle\Menu\MainMenuBuilder;
use Sylius\Bundle\CoreBundle\Form\Type\User\AdminUserType;

return function (ContainerConfigurator $configurator): void {
    $services = $configurator->services();
    $services->defaults()->autowire(true);

    $services->set(AdminMenuListener::class)
        ->public()->autowire(true)
        ->tag(
            'kernel.event_listener',
            ['event' => MainMenuBuilder::EVENT_NAME, 'method' => AdminMenuListener::METHOD]
        );
    $services->set(AdminMenuSecurityListener::class)
        ->public()->autowire(true)
        ->tag('kernel.event_listener', [
            'event' => MainMenuBuilder::EVENT_NAME,
            'method' => AdminMenuSecurityListener::METHOD,
            'priority' => -999,
        ]);
    $services->set(AdminRequestSecurityListener::class)->public()->autowire(true)->tag('kernel.event_subscriber');

    $services->set(RbacGrid::class)->public()->autowire(true)->tag('sylius.grid');

    $services->set(UrlDecoratorExtension::class)
        ->public()->autowire(true)
        ->arg('$decoratedExtension', service('twig.extension.routing'))
        ->tag('twig.extension', ['priority' => 999]);

    $services->set(AdminUserTypeExtension::class)
        ->public()->autowire(true)
        ->tag('form.type_extension', ['extended-type' => AdminUserType::class]);

    $services->set(RbacType::class)->public()->tag('form.type')->arg('$dataClass', AccessItem::class);
    $services->set(RoutePermissionChoiceType::class)->public()->tag('form.type')->autowire(true);

    $services->alias(MainMenuBuilder::class, 'sylius.admin.menu_builder.main');
    $services->set(RoutePermissionFactory::class)->public()->autowire(true);
    $services->set(RbacFormSubscriber::class)->public()->autowire(true);
    $services->set(Configurator::class)->public()->arg(
        '$config',
        param(Configuration::NODE_CUSTOM_ROUTES),
    );

    $services->set(EmptyPageController::class)
        ->public()
        ->tag('controller.service_arguments');

    $services
        ->load('Gtt\SyliusRbacPlugin\Service\\', __DIR__ . '/../../Service')
        ->load('Gtt\SyliusRbacPlugin\Infrastructure\Factory\\', __DIR__ . '/../../Infrastructure/Factory')
        ->load('Gtt\SyliusRbacPlugin\Infrastructure\Service\\', __DIR__ . '/../../Infrastructure/Service')
        ->public();

    $services
        ->load('Gtt\SyliusRbacPlugin\Command\\', __DIR__ . '/../../Command')
        ->autowire()
        ->public()
        ->tag('console.command');
};
