<?php

declare(strict_types=1);

namespace Tests\Gtt\SyliusRbacPlugin\Behat\Pages\Admin;

use FriendsOfBehat\PageObjectExtension\Page\SymfonyPageInterface;

interface RoleDeletingPageInterface extends SymfonyPageInterface
{
    public function delete(int $id): void;
}
