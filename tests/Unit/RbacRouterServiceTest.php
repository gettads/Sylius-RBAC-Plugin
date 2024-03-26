<?php

declare(strict_types=1);

namespace Tests\Gtt\SyliusRbacPlugin\Unit;

use Knp\Menu\ItemInterface;
use Knp\Menu\MenuFactory;
use Knp\Menu\MenuItem;
use PHPUnit\Framework\TestCase;
use Gtt\SyliusRbacPlugin\Infrastructure\DTO\RouteInfo;
use Gtt\SyliusRbacPlugin\Infrastructure\Environment\Configurator;
use Gtt\SyliusRbacPlugin\Infrastructure\Service\RbacRouterService;
use Sylius\Bundle\AdminBundle\Menu\MainMenuBuilder;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class RbacRouterServiceTest extends TestCase
{
    /**
     * @test
     */
    public function I_check_get_admin_all_routes(): void
    {
        $routes = [
            'shop' => ['shop_test_route' => new Route('/test/path')],
            'admin' => ['admin_test_route' => new Route('/admin/test/path')],
        ];

        $menuData = [
            'shop' => [
                'name' => 'shop_test_menu',
                'uri' => '/test/path',
                'routes' => ['shop_test_route'],
                'label' => 'Test_Shop',
            ],
            'admin' => [
                'name' => 'admin_test_menu',
                'uri' => '/admin/test/path',
                'routes' => ['admin_test_route'],
                'label' => 'Test_Admin',
            ],
        ];

        $this->assertEquals(
            [],
            $this->createRouterService($routes['shop'], [$menuData['shop']])->getAdminAllRoutesCollection()
        );


        $routeInfos = $this->createRouterService(
            [...$routes['shop'], ...$routes['admin']],
            [$menuData['shop'], $menuData['admin']]
        )->getAdminAllRoutesCollection();
        $this->assertEquals(1, count($routeInfos), 'We have to get only one admin route');
        $this->assertEquals('admin_test_route', current($routeInfos)->getAlias());
    }

    /**
     * @test
     */
    public function I_check_get_admin_menu_routes_for_api(): void
    {
        $routes = [
            'api' => [
                'admin_test_api_route' => new Route('api/admin/test/path'),
                'api_admin_test_configurable_route' => new Route('api/admin/test/another-path'),
            ],
        ];

        $menuData = [
            'test' => [
                'name' => 'admin_test_menu',
                'uri' => '/admin/test/path',
                'routes' => ['admin_test_route'],
                'label' => 'Test_Admin',
            ],
        ];

        $routeInfoGroups = $this->createRouterService($routes['api'], [$menuData['test']])->getAdminMenuRoutesGroups();
        $apiGroup = $routeInfoGroups->getApiGroup();
        $this->assertInstanceOf(RouteInfo::class, current($apiGroup['admin_test_api_route']));
        $this->assertInstanceOf(RouteInfo::class, current($apiGroup['api_admin_test_configurable_route']));
    }

    /**
     * @test
     */
    public function I_check_get_admin_menu_routes(): void
    {
        $routes = [
            'shop' => ['shop_test_route' => new Route('/test/path')],
            'admin' => [
                'admin_test_route' => new Route('/admin/test/path'),
                'admin_test_configurable_route' => new Route('/admin/test/another-path'),
            ],
        ];

        $menuData = [
            'shop' => [
                'name' => 'shop_test_menu',
                'uri' => '/test/path',
                'routes' => ['shop_test_route'],
                'label' => 'Test_Shop',
            ],
            'admin' => [
                'name' => 'admin_test_menu',
                'uri' => '/admin/test/path',
                'routes' => ['admin_test_route'],
                'label' => 'Test_Admin',
            ],
            'admin_configurable' => [
                'name' => 'admin_test_configurable_menu',
                'uri' => '/admin/test/another-path',
                'routes' => ['admin_test_configurable_menu_route'],
                'label' => 'Test_Admin_Configurable',
            ],
        ];

        $routeInfoGroups = $this->createRouterService(
            $routes['shop'],
            [$menuData['shop']]
        )->getAdminMenuRoutesGroups();
        $this->assertEquals(
            0,
            count($routeInfoGroups->getUnrecognizedGroup())
                + count($routeInfoGroups->getApiGroup())
                + count($routeInfoGroups->getRecognizedGroup())
        );

        $routeInfoGroups = $this->createRouterService(
            $routes['admin'],
            [$menuData['admin']]
        )->getAdminMenuRoutesGroups();
        $group = $routeInfoGroups->getRecognizedGroup();
        $this->assertNotNull($group['Test_Admin'] ?? null);

        $routeInfo = current($group['Test_Admin']);
        assert($routeInfo instanceof RouteInfo);
        $this->assertEquals('admin_test_route', $routeInfo->getAlias());
        $this->assertEquals('/admin/test/path', $routeInfo->getUrl());
        $this->assertEquals(['GET'], $routeInfo->getMethods());

        $routeInfoGroups = $this
            ->createRouterService($routes['admin'], [$menuData['admin_configurable']])
            ->getAdminMenuRoutesGroups();

        $this->assertNotNull($routeInfoGroups->getUnrecognizedGroup()['admin_test_route'] ?? null);
        $this->assertNotNull($routeInfoGroups->getUnrecognizedGroup()['admin_test_configurable_route'] ?? null);
        $this->assertEquals(0, count($routeInfoGroups->getRecognizedGroup()));

        // After adding of config: unrecognized will be mapped. Let's create 3 types of configs below.
        $config = [
            [
                'label' => 'Label_in_own_group_prefixed_by_parent',
                'route' => 'admin_test_configurable_*',
                'match' => 'admin_test_route',
            ],
        ];
        $routeInfoGroups = $this
            ->createRouterService($routes['admin'], [$menuData['admin'], $menuData['admin_configurable']], $config)
            ->getAdminMenuRoutesGroups();

        $this->assertNotNull(
            $routeInfoGroups->getRecognizedGroup()['Test_Admin / Label_in_own_group_prefixed_by_parent'] ?? null,
            'By config, we must get route, situated in own group, but prefixed by parent Test_Admin'
        );

        $config = [
            [
                'label' => 'Label_in_own_group',
                'route' => 'admin_test_configurable_*',
                'match' => null,
            ],
        ];
        $routeInfoGroups = $this
            ->createRouterService($routes['admin'], [$menuData['admin'], $menuData['admin_configurable']], $config)
            ->getAdminMenuRoutesGroups();

        $this->assertNotNull(
            $routeInfoGroups->getRecognizedGroup()['Label_in_own_group'] ?? null,
            'By config, we must get route, situated in own group.'
        );

        $config = [
            [
                'label' => null,
                'route' => 'admin_test_configurable_*',
                'match' => 'admin_test_route',
            ],
        ];
        $routeInfoGroups = $this
            ->createRouterService($routes['admin'], [$menuData['admin'], $menuData['admin_configurable']], $config)
            ->getAdminMenuRoutesGroups();

        $parentGroup = $routeInfoGroups->getRecognizedGroup()['Test_Admin'] ?? null;
        $this->assertNotNull(
            $parentGroup,
            'By config, we must get route, attached to parent Test_Admin group'
        );
        $this->assertEquals('admin_test_route', $parentGroup[0]->getAlias());
        $this->assertEquals('admin_test_configurable_route', $parentGroup[1]->getAlias());
    }

    /**
     * @test
     */
    public function I_check_get_admin_menu_routes_by_different_partial_match_cases(): void
    {
        $routes = [
            'admin' => [
                'admin_test_route' => new Route('/admin/test/path'),
            ],
        ];

        $menuData = [
            'admin' => [
                'name' => 'admin_test_menu',
                'uri' => '/admin/test/path',
                'routes' => [],
                'label' => 'Test_Admin',
            ],
        ];

        $routeInfoGroups = $this
            ->createRouterService($routes['admin'], [$menuData['admin']])
            ->getAdminMenuRoutesGroups();

        $this->assertNotNull($routeInfoGroups->getUnrecognizedGroup()['admin_test_route'] ?? null);

        $routes = [
            'admin' => [
                'admin_test_route_show' => new Route('/admin/test/path/show'),
                'admin_test_route_index' => new Route('/admin/test/path/index'),
                'admin_test_route_create' => new Route('/admin/test/path/create'),
                'admin_test_route_update' => new Route('/admin/test/path/update'),
                'admin_test_route_delete' => new Route('/admin/test/path/delete'),
            ],
        ];

        $menuData = [
            'admin' => [
                'name' => 'admin_test_menu',
                'uri' => '/admin/test/path/unknown-group',
                'routes' => ['admin_test_route_unknown_group'],
                'label' => 'Test_Admin',
            ],
        ];

        $routeInfoGroups = $this
            ->createRouterService($routes['admin'], [$menuData['admin']])
            ->getAdminMenuRoutesGroups();

        foreach (array_keys($routes['admin']) as $key) {
            $this->assertNotNull(
                $routeInfoGroups->getUnrecognizedGroup()[$key] ?? null,
                'We got unrecognized route ' . $key . '. Menu has item with route "admin_test_route_unknown_group"'
            );
        }

        $menuData = [
             [
                'name' => 'admin_test_menu_show',
                'uri' => '/admin/test/path/show',
                'routes' => ['admin_test_route_show'],
                'label' => 'Test_Admin_Show',
             ],
             [
                'name' => 'admin_test_menu_index',
                'uri' => '/admin/test/path/index',
                'routes' => ['admin_test_route_index'],
                'label' => 'Test_Admin_Index',
             ],
             [
                'name' => 'admin_test_menu_create',
                'uri' => '/admin/test/path/create',
                'routes' => ['admin_test_route_create'],
                'label' => 'Test_Admin_Create',
             ],
             [
                'name' => 'admin_test_menu_update',
                'uri' => '/admin/test/path/update',
                'routes' => ['admin_test_route_update'],
                'label' => 'Test_Admin_Update',
             ],
             [
                'name' => 'admin_test_menu_delete',
                'uri' => '/admin/test/path/delete',
                'routes' => ['admin_test_route_delete'],
                'label' => 'Test_Admin_Delete',
             ],
        ];

        $routeInfoGroups = $this
            ->createRouterService($routes['admin'], $menuData)
            ->getAdminMenuRoutesGroups();

        foreach ($routeInfoGroups as $key => $infoGroup) {
            $this->assertNotNull(
                $routeInfoGroups->getRecognizedGroup()[$key] ?? null,
                'We can recognize route ' . $key . '. by auto-naming in ResourceBundle'
            );
        }

        $menuData = [
            'parent' => [
                'name' => 'admin_test_menu_index',
                'uri' => '/admin/test/path/index',
                'routes' => ['admin_test_route_index'],
                'label' => 'Test_Admin_Index',
                'children' => [
                    [
                        'name' => 'admin_test_menu_show',
                        'uri' => '/admin/test/path/show',
                        'routes' => ['admin_test_route_show'],
                        'label' => 'Test_Admin_Show',
                    ],
                    [
                        'name' => 'admin_test_menu_create',
                        'uri' => '/admin/test/path/create',
                        'routes' => ['admin_test_route_create'],
                        'label' => 'Test_Admin_Create',
                    ],
                    [
                        'name' => 'admin_test_menu_update',
                        'uri' => '/admin/test/path/update',
                        'routes' => ['admin_test_route_update'],
                        'label' => 'Test_Admin_Update',
                    ],
                    [
                        'name' => 'admin_test_menu_delete',
                        'uri' => '/admin/test/path/delete',
                        'routes' => ['admin_test_route_delete'],
                        'label' => 'Test_Admin_Delete',
                    ],
                ],
            ],
        ];

        $routeInfoGroups = $this
            ->createRouterService($routes['admin'], $menuData)
            ->getAdminMenuRoutesGroups();

        $parentIndexGroup = $routeInfoGroups->getRecognizedGroup();
        $parentIndexGroup = $parentIndexGroup['Test_Admin_Index'] ?? null;

        $this->assertNotNull($parentIndexGroup);
        $this->assertEquals(count($routes['admin']), count($parentIndexGroup));

        foreach ($parentIndexGroup as $childInfo) {
            $this->assertInstanceOf(RouteInfo::class, $childInfo);
            $this->assertTrue(
                in_array($childInfo->getAlias(), array_keys($routes['admin']), true),
                'Each route must be situated inside the parent group by menu\'s hierarchy.'
            );
        }
    }

    private function createRouterService(
        array $routes,
        array $menuData,
        ?array $configFromYaml = []
    ): RbacRouterService
    {
        $menuBuilder = new MainMenuBuilder(
            new class ($menuData) extends MenuFactory {
                private array $menuData;

                public function __construct($menuData)
                {
                    $this->menuData = $menuData;
                }

                public function createItem(string $name, array $options = []): ItemInterface
                {
                    $rootItem = new MenuItem('root', $this);
                    $factory = clone $this;

                    foreach ($this->menuData as $itemData) {
                        $routes = [];

                        foreach ($itemData['routes'] ?? [] as $route) {
                            $routes[] = ['route' => $route];
                        }

                        $data = [
                            'name' => $itemData['name'] ?? 'test_name',
                            'label' => $itemData['label'] ?? 'test_label',
                            'uri' => $itemData['uri'] ?? 'test_uri',
                            'extras' => ['routes' => $routes],
                        ];

                        $childItem = new MenuItem('test', $this);
                        $childItem->setLabel($data['label']);
                        $childItem->setName($data['name']);
                        $childItem->setUri($data['uri']);
                        $childItem->setExtras($data['extras']);
                        $rootItem->addChild($childItem);
                        $childItem->setParent($rootItem);

                        foreach ($itemData['children'] ?? [] as $subChildData) {
                            $subChild = new MenuItem($subChildData['name'], $this);
                            $childItem->addChild($subChild);
                            $subChild->setParent($childItem);
                            $subChild->setUri($subChildData['uri'] ?? 'test_uri');
                            $subChild->setLabel($subChildData['label'] ?? 'test_label');
                            $subChildRoutes = [];

                            foreach ($subChildData['routes'] ?? [] as $route) {
                                $subChildRoutes[] = ['route' => $route];
                            };

                            $subChild->setExtras(['routes' => $subChildRoutes]);
                        }
                    }

                    return new class ($rootItem, $factory) extends MenuItem {
                        public function __construct(MenuItem $rootItem, MenuFactory $factory)
                        {
                            parent::__construct($rootItem->getName(), $factory);

                            foreach ($rootItem->getChildren() as $child) {
                                $child->setParent($this);
                                $this->children[$child->getName()] = $child;
                            }
                        }

                        public function addChild($child, array $options = []): ItemInterface
                        {
                            // ignoring of standard behavior
                            return $this;
                        }

                        public function setParent(?ItemInterface $parent = null): ItemInterface
                        {
                            // ignoring of standard behavior
                            return $this;
                        }
                    };
                }
            },
            $this->createMock(EventDispatcherInterface::class)
        );

        $router = $this->createConfiguredMock(RouterInterface::class, ['getRouteCollection' => $routes]);
        $translator = $this->createMock(TranslatorInterface::class);
        $translator->method('trans')->will($this->returnCallback(fn (string $arg): string => $arg));
        $configurator = new Configurator($configFromYaml);

        return new RbacRouterService($router, $menuBuilder, $translator, $configurator);
    }
}
