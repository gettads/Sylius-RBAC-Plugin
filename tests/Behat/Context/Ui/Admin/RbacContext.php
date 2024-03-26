<?php

declare(strict_types=1);

namespace Tests\Gtt\SyliusRbacPlugin\Behat\Context\Ui\Admin;

use Behat\Behat\Context\Context;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManagerInterface;
use FriendsOfBehat\PageObjectExtension\Page\PageInterface;
use FriendsOfBehat\PageObjectExtension\Page\UnexpectedPageException;
use InvalidArgumentException;
use LogicException;
use Gtt\SyliusRbacPlugin\Entity\AccessItem;
use Gtt\SyliusRbacPlugin\Entity\RbacUserInterface;
use Gtt\SyliusRbacPlugin\Service\RbacInitializationService;
use Sylius\Behat\Service\SharedStorageInterface;
use Sylius\Component\Core\Model\AdminUserInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Tests\Gtt\SyliusRbacPlugin\Behat\Pages\Admin\RoleCreatingPageInterface;
use Tests\Gtt\SyliusRbacPlugin\Behat\Pages\Admin\RoleDeletingPageInterface;
use Tests\Gtt\SyliusRbacPlugin\Behat\Pages\Admin\RoleIndexPageInterface;
use Tests\Gtt\SyliusRbacPlugin\Behat\Pages\Admin\RoleUpdatingPageInterface;

final class RbacContext implements Context
{
    public const PAGES_PREFIX = 'gtt_sylius_rbac_admin_access_groups_';

    public const OPERATIONS = ['index', 'create', 'update', 'delete'];

    public function __construct(
        private readonly RbacInitializationService $rbacInitializationService,
        private readonly EntityManagerInterface $entityManager,
        private readonly SharedStorageInterface $sharedStorage,
        private readonly RoleIndexPageInterface $indexPage,
        private readonly RoleCreatingPageInterface $createPage,
        private readonly RoleUpdatingPageInterface $updatePage,
        private readonly RoleDeletingPageInterface $deletePage,
    )
    {
    }

    /**
     * @Given Admin :email has a role :roleCode
     */
    public function adminHasARole(string $email, string $roleCode): void
    {
        $admin = $this->entityManager->getRepository(AdminUserInterface::class)->findOneBy(['email' => $email]);
        assert($admin instanceof RbacUserInterface);

        $generator = $this->rbacInitializationService->initialize(false);

        if (!$generator->valid()) {
            throw new LogicException('Cannot iterate through RBAC initialization\'s results.');
        }

        $role = $this->entityManager->getRepository(AccessItem::class)->findOneBy([
            'code' => $roleCode,
            'type' => AccessItem::TYPE_ROLE,
        ]);
        assert($role instanceof AccessItem);

        $admin->setAccessCollection(new ArrayCollection([$role]));

        $this->entityManager->persist($admin);
        $this->entityManager->flush();
        $this->sharedStorage->set('role_' . $roleCode, $role);
    }

    /**
     * @Given role :roleCode has access to all RBAC routes
     */
    public function hasAccessToAllRbacRoutesOnly(string $roleCode): void
    {
        $accessItems = [];

        foreach (self::OPERATIONS as $operation) {
            $routePermission = new AccessItem();
            $routePermission->setType(AccessItem::TYPE_PERMISSION_ROUTE);
            $routePermission->setCode(self::PAGES_PREFIX . $operation);

            $this->entityManager->persist($routePermission);
            $accessItems[] = $routePermission;
        }

        $role = $this->sharedStorage->get('role_' . $roleCode);
        assert($role instanceof AccessItem);
        $role->setChildren(new ArrayCollection($accessItems));
        $this->entityManager->persist($role);

        $this->entityManager->flush();
    }

    /**
     * @Then I can open pages :operationList for role :roleCode
     * @Then I can open pages :operationList only for role :roleCode
     */
    public function iCanOpenPages(string $operationList, string $roleCode): void
    {
        $role = $this->sharedStorage->get('role_' . $roleCode);

        assert($role instanceof AccessItem);

        foreach (explode(',', $operationList) as $operation) {

            if (!in_array($operation, self::OPERATIONS, true)) {
                throw new InvalidArgumentException('Unexpected operation: ' . $operation);
            }

            $page = $this->{$operation . 'Page'};
            assert($page instanceof PageInterface);

            $page->open(['id' => $role->getId()]);
        }
    }

    /**
     * @Then I can not open :operationList pages for role :roleCode
     */
    public function iCanNotOpenPage(string $operationList, string $roleCode): void
    {
        $expectedException = null;

        try {
            $this->iCanOpenPages($operationList, $roleCode);
        } catch (UnexpectedPageException|AccessDeniedHttpException $exception) {
            $expectedException = $exception;
        }

        $messagePath = 'Received an error status code: 403';

        if (
            $expectedException === null
            || (
                $expectedException instanceof UnexpectedPageException
                && !str_ends_with($expectedException->getMessage(), $messagePath)
            ) || (
                $expectedException instanceof AccessDeniedHttpException
                && $expectedException->getCode() !== Response::HTTP_FORBIDDEN
            )
        ) {
            throw new LogicException('403 (Access denied) error was expected.');
        }
    }


    /**
     * @Given I set permissions :operationList only for :roleCode
     * @Given I set permissions :operationList for :roleCode
     */
    public function iSetPermissionsFor(string $operationList, string $roleCode): void
    {
        $role = $this->sharedStorage->get('role_' . $roleCode);
        assert($role instanceof AccessItem);

        $this->updatePage->open(['id' => $role->getId()]);

        $routePermissionList = [];

        foreach (explode(',', $operationList) as $operation) {
            if (!in_array($operation, self::OPERATIONS, true)) {
                throw new InvalidArgumentException('Unexpected operation: ' . $operation);
            }

            $routePermissionList[] = self::PAGES_PREFIX . $operation;
        }

        $this->updatePage->updatePermissionList(array_map(
            fn (AccessItem $accessItem) => $accessItem->getId(),
            $this->entityManager->getRepository(AccessItem::class)->findBy([
                'code' => $routePermissionList,
                'type' => AccessItem::TYPE_PERMISSION_ROUTE,
            ])
        ));
    }

    /**
     * @When :operation permission was removed from :roleCode role
     */
    public function permissionWasRemoved(string $operation, string $roleCode)
    {
        if (!in_array($operation, self::OPERATIONS, true)) {
            throw new InvalidArgumentException('Unexpected operation: ' . $operation);
        }

        $routePermissionCode = self::PAGES_PREFIX . $operation;

        $role = $this->sharedStorage->get('role_' . $roleCode);
        assert($role instanceof AccessItem);

        foreach ($role->getChildren() as $child) {
            assert($child instanceof AccessItem);

            if (
                $child->getType() === AccessItem::TYPE_PERMISSION_ROUTE
                && $child->getCode() === $routePermissionCode
            ) {
                $child->getParents()->removeElement($role);
            }
        }

        $this->entityManager->flush();
    }

    /**
     * @Then I can not execute deleting of :roleCode role
     */
    public function iCanNotExecuteDeleting(string $roleCode)
    {
        $role = $this->sharedStorage->get('role_' . $roleCode);
        assert($role instanceof AccessItem);

        try {
            $this->deletePage->delete($role->getId());
        } catch (AccessDeniedHttpException $exception) {
            if ($exception->getStatusCode() === Response::HTTP_FORBIDDEN) {
                return;
            }
        }

        throw new LogicException('403 (Access denied) error was expected.');
    }


    /**
     * @Then I can create new :roleCode role with :parentRoleCode role as a parent
     */
    public function iCanCreateNewRole(string $roleCode, string $parentRoleCode): void
    {
        $adminRole = $this->sharedStorage->get('role_' . $parentRoleCode);
        assert($adminRole instanceof AccessItem);

        $this->createPage->open();
        $this->createPage->createRole($roleCode, $adminRole->getId());

        $role = $this->entityManager->getRepository(AccessItem::class)->findOneBy([
            'code' => $roleCode,
            'type' => AccessItem::TYPE_ROLE,
        ]);
        assert($role instanceof AccessItem);
        $this->sharedStorage->set('role_' . $roleCode, $role);
    }

    /**
     * @Then I can add all permissions for :roleCode role
     */
    public function iCanAddAllPermissionsForRole(string $roleCode): void
    {
        $this->iSetPermissionsFor('index,create,update,delete', $roleCode);
    }

    /**
     * @Then I can delete :roleCode role
     */
    public function iCanDeleteRole(string $roleCode): void
    {
        $role = $this->sharedStorage->get('role_' . $roleCode);
        assert($role instanceof AccessItem);

        $this->deletePage->open(['id' => $role->getId()]);
        $this->deletePage->delete($role->getId());
    }
}
