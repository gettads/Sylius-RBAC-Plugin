<?php

declare(strict_types=1);

namespace App\Service;

use Gtt\SyliusRbacPlugin\Infrastructure\DTO\RouteInfo;
use Gtt\SyliusRbacPlugin\Infrastructure\Service\RbacRouterService as BaseRbacRouterService;
use Tests\Gtt\SyliusRbacPlugin\Behat\Context\Ui\Admin\RbacContext;

class RbacRouterService extends BaseRbacRouterService
{
    /**
     * @return array<RouteInfo>
     */
    public function getAdminAllRoutesCollection(): array
    {
        $collection = [];

        foreach (RbacContext::OPERATIONS as $operation) {
            $code = RbacContext::PAGES_PREFIX . $operation;

            $routeInfo = new RouteInfo();
            $routeInfo->setAlias($code);
            $routeInfo->setLocalizedLabel($code);
            $collection[] = $routeInfo;
        }

        return $collection;
    }
}
