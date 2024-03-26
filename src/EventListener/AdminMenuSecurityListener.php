<?php

declare(strict_types=1);

namespace Gtt\SyliusRbacPlugin\EventListener;

use Knp\Menu\ItemInterface;
use Gtt\SyliusRbacPlugin\Infrastructure\Service\RbacRouterService;
use Gtt\SyliusRbacPlugin\Service\RbacUserAccessService;
use Sylius\Bundle\UiBundle\Menu\Event\MenuBuilderEvent;

final class AdminMenuSecurityListener
{
    public const METHOD = 'securityUp';

    public function __construct(
        private RbacUserAccessService $accessService,
        private RbacRouterService $routerService,
    )
    {
    }

    public function securityUp(MenuBuilderEvent $event): void
    {
        $this->filter($event);

        $this->afterFilter($event);
    }

    private function filter(MenuBuilderEvent $event, ?ItemInterface $item = null): void
    {
        $parent = $item instanceof ItemInterface ? $item : $event->getMenu();

        foreach ($parent->getChildren() as $child) {
            assert($child instanceof ItemInterface);

            $routesAliases = $this->routerService->extractItemRoutesAliases($child);

            $closedRoutes = 0;

            foreach ($routesAliases as $routesAlias) {
                if (!$this->accessService->canRoute($routesAlias) && $child->getUri() !== null) {
                    $closedRoutes++;
                }
            }

            if (count($routesAliases) > 0 && (count($routesAliases) === $closedRoutes)) {
                $parent->removeChild($child);
            }

            if ($child->getChildren() !== []) {
                $this->filter($event, $child);
            }
        }
    }

    private function afterFilter(MenuBuilderEvent $event): void
    {
        $parent = $event->getMenu();

        foreach ($parent->getChildren() as $mainCategory) {
            if ($mainCategory->getChildren() === []) {
                $parent->removeChild($mainCategory);
            }
        }
    }
}
