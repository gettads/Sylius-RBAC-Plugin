<?php

declare(strict_types=1);

namespace Tests\Gtt\SyliusRbacPlugin\Unit;

use Doctrine\Common\Collections\ArrayCollection;
use PHPUnit\Framework\TestCase;
use Gtt\SyliusRbacPlugin\Entity\AccessItem;
use Gtt\SyliusRbacPlugin\Entity\RbacAdminAwareTrait;
use Gtt\SyliusRbacPlugin\Entity\RbacUserInterface;
use Gtt\SyliusRbacPlugin\Service\RbacUserAccessService;
use Sylius\Component\Core\Model\AdminUser;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Security\Core\User\UserInterface;

class RbacUserAccessServiceTest extends TestCase
{
    /**
     * @test
     */
    public function I_check_can_route_positive(): void
    {
        $routeName = 'test';

        $user = $this->createUser();

        $this->assertFalse($this->createUserAccessService($routeName, $user)->canRoute($routeName));

        $this->setToUserRoleWithRoutePermission('test_admin', $routeName, $user);

        $this->assertTrue($this->createUserAccessService($routeName, $user)->canRoute($routeName));
    }

    /**
     * @test
     */
    public function I_check_can_route_negative_by_empty_route(): void
    {
        $this->expectException('Symfony\Component\Routing\Exception\InvalidArgumentException');
        $this->createUserAccessService(null, $this->createUser())->canRoute(null);
    }

    /**
     * @test
     */
    public function I_check_can_route_positive_if_route_is_no_content_placeholder(): void
    {
        $this->assertTrue(
            $this->createUserAccessService(RbacUserAccessService::REDIRECT_ROUTE, $this->createUser())->canRoute(null),
            'This route is open for everyone.'
        );
    }

    /**
     * @test
     */
    public function I_check_can_route_positive_if_user_is_not_under_rbac(): void
    {
        $this->assertTrue(
            $this->createUserAccessService('test_route', $this->createUser(false))->canRoute(null),
            'Security switches off if user is not under RBAC.'
        );
    }

    /**
     * @test
     */
    public function I_check_can_route_positive_if_user_has_no_route_permission(): void
    {
        $user = $this->createUser();

        $roleParent = $this->createAccessItem('role_parent', AccessItem::TYPE_ROLE);
        $roleChild = $this->createAccessItem('role_child', AccessItem::TYPE_ROLE);
        $roleChild->setParents(new ArrayCollection([$roleParent]));
        $roleParent->setChildren(new ArrayCollection([$roleChild]));

        assert($user instanceof RbacUserInterface);
        $user->setAccessCollection(new ArrayCollection([$roleParent]));

        $this->assertFalse(
            $this->createUserAccessService('test_route', $user)->canRoute(null),
            'User has access permissions without route permissions'
        );
    }

    /**
     * @test
     */
    public function I_check_can_route_positive_if_user_has_roles_hierarchy(): void
    {
        $user = $this->createUser();

        $roleParent = $this->createAccessItem('role_parent', AccessItem::TYPE_ROLE);
        $roleChild = $this->createAccessItem('role_child', AccessItem::TYPE_ROLE);
        $routeChild = $this->createAccessItem('route_child', AccessItem::TYPE_PERMISSION_ROUTE);
        $roleSecondChild = $this->createAccessItem('role_second_child', AccessItem::TYPE_ROLE);
        $routeGrandChild = $this->createAccessItem('route_grand_child', AccessItem::TYPE_PERMISSION_ROUTE);
        $routeSecondGrandChild = $this->createAccessItem(
            'route_second_grand_child',
            AccessItem::TYPE_PERMISSION_ROUTE
        );

        $roleChild->setChildren(new ArrayCollection([$routeSecondGrandChild, $routeGrandChild]));
        $roleSecondChild->setChildren(new ArrayCollection([$routeSecondGrandChild]));
        $roleParent->setChildren(new ArrayCollection([$roleChild, $routeChild, $roleSecondChild]));

        assert($user instanceof RbacUserInterface);

        $user->setAccessCollection(new ArrayCollection([$roleParent]));
        $this->assertFalse(
            $this->createUserAccessService('role_parent', $user)->canRoute('route_grand_child'),
            'Parent role is not allowed by grand child role.'
        );
        $this->assertTrue(
            $this->createUserAccessService('role_parent', $user)->can('role_child'),
            'Parent role has child.'
        );
        $this->assertTrue(
            $this->createUserAccessService('role_parent', $user)->canRoute('route_child'),
            'Parent role has route child.'
        );

        $user->setAccessCollection(new ArrayCollection([$roleChild]));
        $this->assertTrue(
            $this->createUserAccessService('role_child', $user)->can('role_parent'),
            'Role has 1 child (route) and 1 parent (role). Check parent.'
        );
        $this->assertTrue(
            $this->createUserAccessService('role_child', $user)->canRoute('route_grand_child'),
            'Role has 1 child (route) and 1 parent (role). Check child.'
        );
        $this->assertTrue(
            $this->createUserAccessService('role_child', $user)->canRoute('route_child'),
            'Role has parent role with route-permission. Check route inheritance from parent.'
        );

        $user->setAccessCollection(new ArrayCollection([$routeGrandChild]));
        $this->assertTrue(
            $this->createUserAccessService('route_grand_child', $user)->can('role_child'),
            'Route permission has 1 parent and 1 grand parent. Check inheritance from parent.'
        );
        $this->assertTrue(
            $this->createUserAccessService('route_grand_child', $user)->can('role_parent'),
            'Route permission has 1 parent and 1 grand parent. Check inheritance from grand parent.'
        );
        $this->assertTrue(
            $this->createUserAccessService('route_grand_child', $user)->can('route_child'),
            'Route permission has grand parent with route permission. Check route inheritance from grand parent.'
        );

        $user->setAccessCollection(new ArrayCollection([$routeSecondGrandChild]));
        $this->assertTrue(
            $this->createUserAccessService('route_second_grand_child', $user)->canRoute('route_second_grand_child'),
            'Route permission has 1 parent, 1 grand parent, 1 sibling. Check itself: has permission.'
        );
        $this->assertFalse(
            $this->createUserAccessService('route_second_grand_child', $user)->canRoute('route_grand_child'),
            'Route permission has 1 parent, 1 grand parent, 1 sibling. Check sibling: does not have sibling`s permission'
        );
        $this->assertTrue(
            $this->createUserAccessService('route_second_grand_child', $user)->can('route_child'),
            'Route permission has grand parent with route permission. Check route inheritance from grand parent.'
        );

        $user->setAccessCollection(new ArrayCollection([$roleSecondChild]));
        $this->assertTrue(
            $this->createUserAccessService('role_second_child', $user)->canRoute('route_second_grand_child'),
            'Role has 1 parent, 1 child, 1 sibling. Check child route: has permission.'
        );
        $this->assertFalse(
            $this->createUserAccessService('role_second_child', $user)->canRoute('route_grand_child'),
            'Role has 1 parent, 1 child, 1 sibling. Check child of sibling: permission denied.'
        );
        $this->assertTrue(
            $this->createUserAccessService('role_second_child', $user)->canRoute('route_child'),
            'Role has 1 parent, 1 child, 1 sibling. Check parent route: has permission.'
        );
    }

    /**
     * @test
     */
    public function I_check_can_route_for_super_admin(): void
    {
        $user = $this->createUser();

        $this->setToUserRoleWithRoutePermission(AccessItem::ROLE_SUPERADMIN, 'route_1', $user);

        $this->assertTrue($this->createUserAccessService('route_2', $user)->canRoute('route_2'));
    }

    /**
     * @test
     */
    public function I_check_can_route_without_route_param(): void
    {
        $routeName = 'test_route';

        $user = $this->createUser();

        $this->assertFalse($this->createUserAccessService($routeName, $user)->canRoute(null));

        $this->setToUserRoleWithRoutePermission(AccessItem::ROLE_ADMIN, $routeName, $user);

        $this->assertTrue($this->createUserAccessService($routeName, $user)->canRoute(null));
    }

    private function setToUserRoleWithRoutePermission(string $roleName, string $routeName, UserInterface $user): void
    {
        $roleItem = $this->createAccessItem($roleName, AccessItem::TYPE_ROLE);
        $routeItem = $this->createAccessItem($routeName, AccessItem::TYPE_PERMISSION_ROUTE);
        $roleItem->setChildren(new ArrayCollection([$routeItem]));
        $routeItem->addParent($roleItem);

        if ($user instanceof RbacUserInterface) {
            $user->setAccessCollection(new ArrayCollection([$roleItem]));
        }
    }

    private function createAccessItem(string $code, string $type): AccessItem
    {
        $accessItem = new AccessItem();
        $accessItem->setCode($code);
        $accessItem->setType($type);

        return $accessItem;
    }

    private function createUserAccessService(?string $route, ?UserInterface $user): RbacUserAccessService
    {
        $request = new Request([], [], ['_route' => $route]);
        $requestStack = new RequestStack();
        $requestStack->push($request);

        $security = $this->createConfiguredMock(Security::class, [
            'getUser' => $user,
        ]);

        return new RbacUserAccessService($requestStack, $security);
    }

    private function createUser(bool $isImplementsRbacUserInterface = true): UserInterface
    {
        if (!$isImplementsRbacUserInterface) {
            return new class extends AdminUser {
            };
        }

        return new class extends AdminUser implements RbacUserInterface {
            use RbacAdminAwareTrait {
                __construct as private initializeAccessCollection;
            }

            public function __construct()
            {
                parent::__construct();

                $this->initializeAccessCollection();
            }
        };
    }
}
