<?php

declare(strict_types=1);

namespace Gtt\SyliusRbacPlugin\Service;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityNotFoundException;
use LogicException;
use Gtt\SyliusRbacPlugin\Entity\AccessItem;
use Gtt\SyliusRbacPlugin\Entity\RbacUserInterface;
use Gtt\SyliusRbacPlugin\Infrastructure\Factory\RoutePermissionFactory;
use Gtt\SyliusRbacPlugin\Infrastructure\Repository\AccessItemRepository;
use Sylius\Component\Core\Model\AdminUserInterface;

class RbacInitializationService
{
    private ?AccessItem $superAdminRole = null;

    /**
     * @var array<int, string> $roles
     */
    private static array $roles = [
        AccessItem::ROLE_SUPERADMIN,
        AccessItem::ROLE_ADMIN,
        AccessItem::ROLE_MANAGER,
        AccessItem::ROLE_CASHIER,
        AccessItem::ROLE_AUDIT,
        AccessItem::ROLE_STOREKEEPER,
        AccessItem::ROLE_CONTENT_MANAGER,
    ];

    public function __construct(
        private readonly AccessItemRepository $accessItemRepository,
        private readonly EntityManagerInterface $entityManager,
        private readonly RoutePermissionFactory $routePermissionFactory,
    )
    {
    }

    /**
     * @return iterable<string, int>
     */
    public function initialize(bool $isFullInit): iterable
    {
        if ($isFullInit === true) {
            $this->checkIfInitialized(true);
        }

        yield 'roles__created' => $this->initBuiltinRoles();
        yield 'routes__permissions__rechecked' => $this->initRoutesAsPermissions();
        yield 'role__super_admin__granted' => $this->makeCurrentAdminUsersSuperadmin();
    }

    public function checkIfInitialized(bool $throwException = false): bool
    {
        if ($this->entityManager->getRepository(AccessItem::class)->findOneBy([]) !== null) {
            if ($throwException) {
                throw new LogicException('RBAC was initialized before. Exiting.');
            }

            return true;
        }

        return false;
    }

    public function makeCurrentAdminUsersSuperadmin(): int
    {
        if (!is_int($this->superAdminRole?->getId())) {
            throw new EntityNotFoundException(AccessItem::ROLE_SUPERADMIN . ' role was not found');
        }

        $adminUserRepository = $this->entityManager->getRepository(AdminUserInterface::class);
        $updatedUsersCount = 0;

        foreach ($adminUserRepository->findAll() as $adminUser) {
            if (!$adminUser instanceof RbacUserInterface) {
                return 0;
            }

            $isGrantNeeded = $adminUser->getAccessCollection()
                ->filter(fn (AccessItem $item) => $item->getCode() === AccessItem::ROLE_SUPERADMIN)
                ->isEmpty();

            if (!$isGrantNeeded) {
                continue;
            }

            $adminUser->getAccessCollection()->add($this->superAdminRole);
            $this->entityManager->persist($adminUser);
            $updatedUsersCount++;
        }

        if ($updatedUsersCount > 0) {
            $this->entityManager->flush();
        }

        return $updatedUsersCount;
    }

    protected function initRoutesAsPermissions(): int
    {
        return $this->routePermissionFactory->buildRoutePermissionCollection()->count();
    }

    private function initBuiltinRoles(): int
    {
        $existingRoles = $this->accessItemRepository->findBy(['code' => self::$roles]);

        $insertedRolesCount = 0;

        foreach (self::$roles as $code) {
            $accessItem = null;

            foreach ($existingRoles as $existingRole) {
                assert($existingRole instanceof AccessItem);

                if ($existingRole->getCode() === $code) {
                    $accessItem = $existingRole;

                    break;
                }
            }

            if ($accessItem === null) {
                $accessItem = new AccessItem();
                $accessItem->setCode($code);
                $accessItem->setType(AccessItem::TYPE_ROLE);

                $this->entityManager->persist($accessItem);
                $insertedRolesCount++;
            }

            if ($code === AccessItem::ROLE_SUPERADMIN) {
                $this->superAdminRole = $accessItem;
            }
        }

        if ($insertedRolesCount > 0) {
            $this->entityManager->flush();
        }

        return $insertedRolesCount;
    }
}
