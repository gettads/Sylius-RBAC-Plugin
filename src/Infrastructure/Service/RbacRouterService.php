<?php

declare(strict_types=1);

namespace Gtt\SyliusRbacPlugin\Infrastructure\Service;

use Knp\Menu\ItemInterface;
use Gtt\SyliusRbacPlugin\DependencyInjection\Configuration;
use Gtt\SyliusRbacPlugin\Infrastructure\DTO\RouteInfo;
use Gtt\SyliusRbacPlugin\Infrastructure\DTO\RouteInfoGroups;
use Gtt\SyliusRbacPlugin\Infrastructure\Environment\Configurator;
use Gtt\SyliusRbacPlugin\Service\RbacUserAccessService;
use Sylius\Bundle\AdminBundle\Menu\MainMenuBuilder;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class RbacRouterService
{
    public const UNRECOGNIZED = '_unrecognized';
    public const API = '_api';

    private const ADMIN_PATH = 'admin';

    private const SUBSTITUTE_ON_BLANK = [
        '_show',
        '_index',
        '_create',
        '_update',
        '_bulk_delete',
        '_delete',
        // '_partial', '_ajax',
    ];

    public function __construct(
        private RouterInterface $router,
        private MainMenuBuilder $menuBuilder,
        private TranslatorInterface $translator,
        private Configurator $configurator,
    )
    {
    }

    /**
     * @return array<RouteInfo>
     */
    public function getAdminAllRoutesCollection(): array
    {
        $collection = [];

        foreach ($this->router->getRouteCollection() as $aliasRoute => $route) {
            assert($route instanceof Route);

            if (!$this->isAdminRoute($route)) {
                continue;
            }

            $routeInfo = new RouteInfo();
            $routeInfo->setAlias($aliasRoute);
            $routeInfo->setLocalizedLabel($this->translator->trans($aliasRoute));
            $collection[] = $routeInfo;
        }

        return $collection;
    }

    /**
     * @return array<int, string>
     */
    public function extractItemRoutesAliases(ItemInterface $item): array
    {
        $result = [];
        $extras = $item->getExtras();
        $routes = $extras['routes'] ?? [];

        foreach ($routes as $data) {
            if (isset($data['route'])) {
                $result[] = $data['route'];
            }
        }

        return $result;
    }

    public function getAdminMenuRoutesGroups(): RouteInfoGroups
    {
        $collection = new RouteInfoGroups();
        $flatAdminMenu = $this->getFlatAdminMenuItemsCollection();

        foreach ($this->router->getRouteCollection() as $aliasRoute => $route) {
            assert($route instanceof Route);

            if (
                $aliasRoute === RbacUserAccessService::REDIRECT_ROUTE
                || !$this->isAdminRoute($route)
            ) {
                continue;
            }

            $isMatched = false;

            $routeInfo = new RouteInfo();
            $routeInfo->setAlias($aliasRoute);
            $routeInfo->setLocalizedLabel($aliasRoute);
            $routeInfo->setMethods($route->getMethods());
            $routeInfo->setUrl($route->getPath());

            foreach ($flatAdminMenu as $child) {
                assert($child instanceof ItemInterface);

                if (
                    $this->isFullMatch($child, $aliasRoute)
                    || $this->isPartialMatch($child, $aliasRoute)
                ) {
                    $isMatched = true;

                    $routeInfo->setLocalizedLabel(implode(' / ', array_reverse($this->getLabelsFlow($child))));
                    $collection->addToRecognizedGroup($routeInfo->getLocalizedLabel(), $routeInfo);

                    continue;
                }

                $label = $this->getLabelViaConfig($child, $aliasRoute, $collection, $flatAdminMenu);

                if ($label === false) {
                    continue;
                }

                if ($label !== null) {
                    $isMatched = true;

                    $routeInfo->setLocalizedLabel($label);
                    $collection->addToRecognizedGroup($routeInfo->getLocalizedLabel(), $routeInfo);
                }
            }

            if (!$isMatched && $this->isApi($aliasRoute)) {
                $isMatched = true;
                $collection->addToApiGroup($routeInfo->getLocalizedLabel(), $routeInfo);
            }

            if (!$isMatched) {
                $collection->addToUnrecognizedGroup($routeInfo->getLocalizedLabel(), $routeInfo);
            }
        }

        return $collection;
    }

    /**
     * @return array<ItemInterface>
     */
    private function getFlatAdminMenuItemsCollection(?array $data = [], ?array $children = []): array
    {
        $childArray = $data === [] ? [] : $data;

        $menu = $children === [] ? $this->menuBuilder->createMenu([])->getChildren() : $children;

        foreach ($menu as $child) {
            if ($child->getUri() !== null && trim($child->getUri()) !== '') {
                $childArray[] = $child;
            }

            if ($child->hasChildren()) {
                $childArray = $this->getFlatAdminMenuItemsCollection($childArray, $child->getChildren());
            }
        }

        return $childArray;
    }

    private function isApi(string $aliasRoute): bool
    {
        if ((str_starts_with($aliasRoute, 'api_'))) {
            return true;
        }

        // phpcs:ignore
        if ((str_contains($aliasRoute, '_api_'))) {
            return true;
        }

        return false;
    }

    /**
     * @param array<ItemInterface> $flatAdminMenu
     */
    private function canGetLabelViaConfig(
        ItemInterface $item,
        string $aliasRoute,
        RouteInfoGroups $groups,
        array $flatAdminMenu
    ): bool
    {
        if ($this->isFullMatch($item, $aliasRoute) || $this->isPartialMatch($item, $aliasRoute)) {
            return false;
        }

        foreach ($flatAdminMenu as $child) {
            assert($child instanceof ItemInterface);

            if (
                $this->isFullMatch($child, $aliasRoute)
                || $this->isPartialMatch($child, $aliasRoute)
            ) {
                return false;
            }
        }

        foreach ($groups->getRecognizedGroup() as $collection) {
            foreach ($collection as $routeInfo) {
                assert($routeInfo instanceof RouteInfo);

                if ($routeInfo->getAlias() === $aliasRoute) {
                    return false;
                }
            }
        }

        return true;
    }

    /**
     * @param array<ItemInterface> $flatAdminMenu
     */
    private function getLabelViaConfig(
        ItemInterface $item,
        string $aliasRoute,
        RouteInfoGroups $groups,
        array $flatAdminMenu,
    ): string|false|null
    {
        if (!$this->canGetLabelViaConfig($item, $aliasRoute, $groups, $flatAdminMenu)) {
            return false;
        }

        $label = '';

        foreach ($this->configurator->getConfig() as $configItem) {
            $configRoutePath = '/' . str_replace(
                Configurator::MASK_CHAR,
                '.' . Configurator::MASK_CHAR,
                $configItem[Configuration::NODE_CUSTOM_ROUTE] ?? '-'
            ) . '/';

            $menuItemRoutes = $this->extractItemRoutesAliases($item);
            $configLabel = $configItem[Configuration::NODE_CUSTOM_LABEL];
            $configMatch = $configItem[Configuration::NODE_CUSTOM_MATCH];

            $isParentLabelOnly = $configLabel === null && $configMatch !== null;
            $isParentLabelAsPrefix = $configLabel !== null && $configMatch !== null;
            $isOwnLabelOnly = $configLabel !== null && $configMatch === null;

            if (preg_match($configRoutePath, $aliasRoute) === 1) {
                if ($isOwnLabelOnly) {
                    $label = $this->translator->trans($configItem[Configuration::NODE_CUSTOM_LABEL]);
                } elseif ($isParentLabelOnly && in_array($configMatch, $menuItemRoutes, true)) {
                    $label = implode(' / ', array_reverse($this->getLabelsFlow($item)));
                } elseif (
                    $isParentLabelAsPrefix
                    && in_array($configMatch, $menuItemRoutes, true)
                ) {
                    $label = implode(' / ', array_reverse($this->getLabelsFlow($item)))
                        . ' / '
                        . $this->translator->trans($configItem[Configuration::NODE_CUSTOM_LABEL]);
                }

                return (trim($label) === '') ? null : $label;
            }
        }

        return null;
    }

    private function isFullMatch(ItemInterface $item, string $alias): bool
    {
        return in_array($alias, $this->extractItemRoutesAliases($item), true);
    }

    private function isPartialMatch(ItemInterface $item, string $alias): bool
    {
        $itemRouteAliases = $this->extractItemRoutesAliases($item);

        if ($itemRouteAliases === []) {
            return false;
        }

        foreach ($itemRouteAliases as $itemRouteAlias) {
            foreach (self::SUBSTITUTE_ON_BLANK as $substitution) {
                $itemRouteAlias = str_replace($substitution, '', $itemRouteAlias);
                $alias = str_replace($substitution, '', $alias);

                if ($itemRouteAlias === $alias) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * @param array<string>|null $data
     * @return array<string>
     */
    private function getLabelsFlow(ItemInterface $item, ?array $data = []): array
    {
        $data[] = $this->translator->trans($item->getLabel());

        if (
            $item->getParent() !== null &&
            ($item->getParent()->getUri() !== null || $item->getParent()->getParent() !== null)
        ) {
            $data = $this->getLabelsFlow($item->getParent(), $data);
        }

        return $data;
    }

    private function isAdminRoute(Route $route): bool
    {
        if (strpos($route->getPath(), '/' . self::ADMIN_PATH . '/') !== false) {
            return true;
        }

        $options = $route->getOptions();

        return
            isset($options['_sylius']['section'])
            && trim($options['_sylius']['section'], '/') === self::ADMIN_PATH
        ;
    }
}
