<?php

declare(strict_types=1);

namespace Gtt\SyliusRbacPlugin\Grid;

use Gtt\SyliusRbacPlugin\Entity\AccessItem;
use Sylius\Bundle\GridBundle\Builder\Action\CreateAction;
use Sylius\Bundle\GridBundle\Builder\Action\DeleteAction;
use Sylius\Bundle\GridBundle\Builder\Action\UpdateAction;
use Sylius\Bundle\GridBundle\Builder\ActionGroup\ItemActionGroup;
use Sylius\Bundle\GridBundle\Builder\ActionGroup\MainActionGroup;
use Sylius\Bundle\GridBundle\Builder\Field\TwigField;
use Sylius\Bundle\GridBundle\Builder\Filter\StringFilter;
use Sylius\Bundle\GridBundle\Builder\GridBuilderInterface;
use Sylius\Bundle\GridBundle\Grid\AbstractGrid;
use Sylius\Bundle\GridBundle\Grid\ResourceAwareGridInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

final class RbacGrid extends AbstractGrid implements ResourceAwareGridInterface
{
    public function __construct(
        private TranslatorInterface $translator
    ) {
    }

    public function buildGrid(GridBuilderInterface $gridBuilder): void
    {
        $gridBuilder->setRepositoryMethod('createListQueryBuilder');

        $gridBuilder->addField(
            TwigField::create('code', '@GttSyliusRbacPlugin/grid/code.html.twig')
                ->setLabel($this->translator->trans('gtt_sylius_rbac.form.code'))
        );

        $gridBuilder
            ->addActionGroup(
                MainActionGroup::create(
                    CreateAction::create(),
                )
            )
            ->addActionGroup(
                ItemActionGroup::create(
                    // ShowAction::create(),
                    UpdateAction::create(),
                    DeleteAction::create()
                )
            )
        ;
        $gridBuilder
            ->addFilter(
                StringFilter::create('code', null, 'contains')
                    ->setLabel($this->translator->trans('gtt_sylius_rbac.form.code'))
            )
        ;
    }

    public function getResourceClass(): string
    {
        return AccessItem::class;
    }

    public static function getName(): string
    {
        return 'gtt_sylius_rbac';
    }
}
