<?php

declare(strict_types=1);

namespace Tests\Gtt\SyliusRbacPlugin\Behat\Pages\Admin;

use Sylius\Behat\Page\Admin\Crud\IndexPage;

class RoleIndexPage extends IndexPage implements RoleIndexPageInterface
{
    public function getRouteName(): string
    {
        return 'gtt_sylius_rbac_admin_access_groups_index';
    }
}
