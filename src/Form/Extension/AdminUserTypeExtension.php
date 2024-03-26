<?php

declare(strict_types=1);

namespace Gtt\SyliusRbacPlugin\Form\Extension;

use Gtt\SyliusRbacPlugin\Entity\AccessItem;
use Gtt\SyliusRbacPlugin\Infrastructure\Repository\AccessItemRepository;
use Sylius\Bundle\CoreBundle\Form\Type\User\AdminUserType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\FormBuilderInterface;

class AdminUserTypeExtension extends AbstractTypeExtension
{
    /**
     * @inheritDoc
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('accessCollection', EntityType::class, [
            'class' => AccessItem::class,
            'choice_label' => 'code',
            'label' => 'gtt_sylius_rbac.form.code',
            'query_builder' => fn (AccessItemRepository $repository) => $repository->createQueryBuilder('ai')
                ->andWhere('ai.type = :type')
                ->setParameter('type', AccessItem::TYPE_ROLE),
            'multiple' => true,
        ]);
    }

    public function getExtendedType(): string
    {
        return AdminUserType::class;
    }

    /**
     * @return iterable<string>
     */
    public static function getExtendedTypes(): iterable
    {
        return [AdminUserType::class];
    }
}
