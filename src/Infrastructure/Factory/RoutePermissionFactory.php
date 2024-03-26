<?php

declare(strict_types=1);

namespace Gtt\SyliusRbacPlugin\Infrastructure\Factory;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Gtt\SyliusRbacPlugin\Entity\AccessItem;
use Gtt\SyliusRbacPlugin\Infrastructure\DTO\RouteInfo;
use Gtt\SyliusRbacPlugin\Infrastructure\DTO\RouteInfoGroups;
use Gtt\SyliusRbacPlugin\Infrastructure\Repository\AccessItemRepository;
use Gtt\SyliusRbacPlugin\Infrastructure\Service\RbacRouterService;

class RoutePermissionFactory
{
    public function __construct(
        private RbacRouterService $rbacRouterService,
        private AccessItemRepository $accessItemRepository,
    )
    {
    }

    public function buildAdminRoutesGroups(): RouteInfoGroups
    {
        return $this
            ->rbacRouterService
            ->getAdminMenuRoutesGroups();
    }

    public function buildRoutePermissionCollection(): Collection
    {
        $collection = $codes = [];

        foreach ($this->rbacRouterService->getAdminAllRoutesCollection() as $routeInfo) {
            $codes[] = $routeInfo->getAlias();
        }

        foreach (
            $this->accessItemRepository->findBy([
                'type' => AccessItem::TYPE_PERMISSION_ROUTE,
                'code' => $codes,
            ]) as $val
        ) {
            assert($val instanceof AccessItem);
            $collection[$val->getCode()] = $val;
        }

        foreach ($this->rbacRouterService->getAdminAllRoutesCollection() as $routeInfo) {
            assert($routeInfo instanceof RouteInfo);

            if (!isset($collection[$routeInfo->getAlias()])) {
                $routePermission = new AccessItem();
                $routePermission->setType(AccessItem::TYPE_PERMISSION_ROUTE);
                $routePermission->setCode($routeInfo->getAlias());
                $routePermission->setId(
                    $this
                        ->accessItemRepository
                        ->getSynchronizedId($routePermission),
                );

                $collection[] = $routePermission;
            }
        }

        return new ArrayCollection($collection);
    }
}
