<?php

declare(strict_types=1);

namespace Tests\Gtt\SyliusRbacPlugin\Behat\Pages\Admin;

use Sylius\Behat\Page\Admin\Crud\CreatePage;

class RoleCreatingPage extends CreatePage implements RoleCreatingPageInterface
{
    public function getRouteName(): string
    {
        return 'gtt_sylius_rbac_admin_access_groups_create';
    }

    public function createRole(string $roleCode, ?int $parentId): void
    {
        $this->getElement('code')->setValue($roleCode);

        if ($parentId !== null) {
            $this->getElement('parents')->setValue($parentId);
        }

        $this->create();
    }

    protected function getDefinedElements(): array
    {
        return array_merge(parent::getDefinedElements(), [
            'create' => 'button[type="submit"]',
            'code' => '#gtt_sylius_rbac_code',
            'parents' => '#gtt_sylius_rbac_parents'
        ]);
    }
}
