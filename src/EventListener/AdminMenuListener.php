<?php

declare(strict_types=1);

namespace Gtt\SyliusRbacPlugin\EventListener;

use Sylius\Bundle\UiBundle\Menu\Event\MenuBuilderEvent;
use Symfony\Contracts\Translation\TranslatorInterface;

final class AdminMenuListener
{
    public const METHOD = 'addMenuItem';

    public function __construct(private TranslatorInterface $translator)
    {
    }

    public function addMenuItem(MenuBuilderEvent $event): void
    {
        $menu = $event->getMenu();

        $configurationMenu = $menu->getChild('configuration');

        $configurationMenu
            ->addChild('gtt_sylius_rbac', ['route' => 'gtt_sylius_rbac_admin_access_groups_index'])
            ->setLabel($this->translator->trans('gtt_sylius_rbac.ui.menu.access_groups'))
            ->setLabelAttribute('icon', 'users')
        ;
    }
}
