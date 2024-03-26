<?php

declare(strict_types=1);

namespace Gtt\SyliusRbacPlugin\Form\Type;

use Gtt\SyliusRbacPlugin\Entity\AccessItem;
use Gtt\SyliusRbacPlugin\Infrastructure\Factory\RoutePermissionFactory;
use Symfony\Bridge\Doctrine\Form\DataTransformer\CollectionToArrayTransformer;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

final class RoutePermissionChoiceType extends AbstractType
{
    public function __construct(private RoutePermissionFactory $routePermissionFactory)
    {
    }

    /**
     * @inheritDoc
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        if (($options['multiple'] ?? false) === true) {
            $builder->addModelTransformer(new CollectionToArrayTransformer());
        }
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver
            ->setDefaults([
                'choices' => $this->routePermissionFactory->buildRoutePermissionCollection(),
                'choice_value' => 'id',
                'choice_label' => 'code',
                'choice_translation_domain' => false,
            ])
            ->setDefined(['subject'])
            ->setAllowedTypes('subject', AccessItem::class);
    }

    public function getParent(): string
    {
        return ChoiceType::class;
    }

    public function getBlockPrefix(): string
    {
        return 'gtt_sylius_rbac_route_permission';
    }
}
