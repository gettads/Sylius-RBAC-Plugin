<?php

declare(strict_types=1);

namespace Tests\Gtt\SyliusRbacPlugin\Behat\Pages\Admin;

use FriendsOfBehat\PageObjectExtension\Page\SymfonyPageInterface;

interface RoleUpdatingPageInterface extends SymfonyPageInterface
{
    /**
     * @param array<int> $routePermissionIdentifierList
     */
    public function updatePermissionList(array $routePermissionIdentifierList): void;
}
