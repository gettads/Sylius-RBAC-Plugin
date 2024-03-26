<?php

declare(strict_types=1);

namespace Tests\Gtt\SyliusRbacPlugin\Behat\Pages\Admin;

use FriendsOfBehat\PageObjectExtension\Page\SymfonyPageInterface;

interface RoleCreatingPageInterface extends SymfonyPageInterface
{
    public function createRole(string $roleCode, ?int $parentId): void;
}
