<?php

declare(strict_types=1);

namespace Tests\Gtt\SyliusRbacPlugin\Behat\Pages\Admin;

use Sylius\Behat\Page\Admin\Crud\UpdatePage;

class RoleUpdatingPage extends UpdatePage implements RoleUpdatingPageInterface
{
    public function getRouteName(): string
    {
        return 'gtt_sylius_rbac_admin_access_groups_update';
    }

    /**
     * @inheritDoc
     */
    public function updatePermissionList(array $routePermissionIdentifierList): void
    {
        foreach ($this->getElement('routes')->findAll('css', '*') as $checkboxDown) {
            $checkboxDown->uncheck();
        }

        foreach ($routePermissionIdentifierList as $id) {
            $checkboxUp = $this->getElement('routes')->find('css', sprintf('[value="%s"]', $id));
            $checkboxUp->check();
        }

        $this->saveChanges();
    }

    protected function getDefinedElements(): array
    {
        return array_merge(parent::getDefinedElements(), [
            'routes' => 'input[name="gtt_sylius_rbac[children][]"]',
        ]);
    }
}
