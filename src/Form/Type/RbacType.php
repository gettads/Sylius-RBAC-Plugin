<?php

declare(strict_types=1);

namespace Gtt\SyliusRbacPlugin\Form\Type;

use Gtt\SyliusRbacPlugin\Entity\AccessItem;
use Gtt\SyliusRbacPlugin\Form\EventSubscriber\RbacFormSubscriber;
use Gtt\SyliusRbacPlugin\Form\Transformer\CollectionToAccessItemTransformer;
use Gtt\SyliusRbacPlugin\Infrastructure\DTO\RouteInfo;
use Gtt\SyliusRbacPlugin\Infrastructure\DTO\RouteInfoGroups;
use Gtt\SyliusRbacPlugin\Infrastructure\Factory\RoutePermissionFactory;
use Gtt\SyliusRbacPlugin\Infrastructure\Repository\AccessItemRepository;
use Sylius\Bundle\ResourceBundle\Form\Type\AbstractResourceType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Contracts\Translation\TranslatorInterface;

class RbacType extends AbstractResourceType
{
    /**
     * @param array<string> $validationGroups
     */
    public function __construct(
        private TranslatorInterface $translator,
        private RbacFormSubscriber $rbacFormSubscriber,
        private RoutePermissionFactory $routePermissionFactory,
        string $dataClass,
        array $validationGroups = [],
    )
    {
        parent::__construct($dataClass, $validationGroups);
    }

    /**
     * @inheritDoc
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('code', TextType::class, [
                'constraints' => [new NotBlank()],
                'empty_data' => '',
                'required' => true,
                'label' => $this->translator->trans('gtt_sylius_rbac.form.code'),
            ])
            ->add('type', TextType::class, [
                'required' => false,
                'empty_data' => AccessItem::TYPE_ROLE,
            ])
            ->add('parents', EntityType::class, [
                'required' => false,
                'query_builder' => fn (AccessItemRepository $repository) => $repository->createQueryBuilder('ai')
                    ->andWhere('ai.id != :currentId')
                    ->andWhere('ai.type = :type')
                    ->setParameter('currentId', $options['data']->isNew() ? 0 : $options['data']?->getId())
                    ->setParameter('type', AccessItem::TYPE_ROLE)
                ,
                'class' => AccessItem::class,
                'choice_label' => 'code',
                'label' => $this->translator->trans('gtt_sylius_rbac.form.parent'),
            ])
        ;

        $builder->get('parents')->addModelTransformer(new CollectionToAccessItemTransformer());
        $builder->addEventSubscriber($this->rbacFormSubscriber);
    }

    /**
     * @inheritDoc
     */
    public function buildView(FormView $view, FormInterface $form, array $options): void
    {
        $choicesData = [];
        $routesGroups = $this->routePermissionFactory->buildAdminRoutesGroups();

        $this->addChoicesData($choicesData, $routesGroups, 'recognized');
        $this->addChoicesData($choicesData, $routesGroups, 'unrecognized');
        $this->addChoicesData($choicesData, $routesGroups, 'api');

        $view->vars['choices_data'] = $choicesData;

        $this->allocateTwigTimeLimit();
    }

    public function getBlockPrefix(): string
    {
        return 'gtt_sylius_rbac';
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        parent::configureOptions($resolver);

        $resolver->setDefaults(['data_class' => AccessItem::class]);
    }

    /**
     * @param array<string, array<string, array<string>>> $choicesData
     */
    private function addChoicesData(array &$choicesData, RouteInfoGroups $routesGroups, string $key): void
    {
        $accessorName = 'get' . ucfirst($key) . 'Group';
        $routes = $routesGroups->{$accessorName}();
        $choicesData[$key] = [];

        ksort($routes);

        foreach ($routes as $label => $routeInfos) {
            foreach ($routeInfos as $index => $routeInfo) {
                assert($routeInfo instanceof RouteInfo);
                $choicesData[$key][$label][$index]['alias'] = $routeInfo->getAlias();
                $choicesData[$key][$label][$index]['human_method'] = $this->humanizeMethod(
                    $routeInfo->getAlias(),
                    $routeInfo->getMethods(),
                );
                $choicesData[$key][$label][$index]['human_name'] = $this->humanizeName($routeInfo->getAlias());
                $choicesData[$key][$label][$index]['url'] = $routeInfo->getUrl();
            }
        }
    }

    private function humanizeName(string $alias): string
    {
        $array = array_unique(explode('_', strtolower($alias)));

        foreach ($array as $index => $value) {
            if (in_array($value, ['sylius', 'admin', 'api', 'app'], true)) {
                unset($array[$index]);
            }
        }

        foreach ($array as $index => $value) {
            if ($value === 'index') {
                $array[$index] = '(list)';
            }
        }

        return ucfirst(implode(' ', $array));
    }

    /**
     * @param array<string> $methods
     */
    private function humanizeMethod(string $alias, array $methods): string
    {
        if (
            str_contains($alias, 'delete')
            || str_contains($alias, 'bulk_delete')
            || in_array('DELETE', $methods, true)
        ) {
            return 'delete';
        }

        if (
            str_contains($alias, 'update')
            || in_array('PUT', $methods, true)
            || in_array('PATCH', $methods, true)
        ) {
            return 'update';
        }

        if (str_contains($alias, 'create') || in_array('POST', $methods, true)) {
            return 'create';
        }

        return 'read';
    }

    private function allocateTwigTimeLimit(): void
    {
        set_time_limit(60);
    }
}
