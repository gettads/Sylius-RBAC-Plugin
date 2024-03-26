<?php

declare(strict_types=1);

namespace Gtt\SyliusRbacPlugin\Form\EventSubscriber;

use Doctrine\Common\Collections\ArrayCollection;
use Gtt\SyliusRbacPlugin\Entity\AccessItem;
use Gtt\SyliusRbacPlugin\Form\Type\RoutePermissionChoiceType;
use Gtt\SyliusRbacPlugin\Infrastructure\Repository\AccessItemRepository;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Contracts\Translation\TranslatorInterface;

final class RbacFormSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private TranslatorInterface $translator,
        private AccessItemRepository $accessItemRepository,
    )
    {
    }

    public function onSubmit(FormEvent $event): void
    {
        $access = $event->getData();
        $form = $event->getForm();
        assert($access instanceof AccessItem);

        if ($access->getCode() === AccessItem::ROLE_SUPERADMIN) {
            $form->addError(new FormError('Action is forbidden: super admin role is uneditable.'));

            return;
        }

        $children = $form->get('children')->getData();

        assert($children instanceof ArrayCollection);

        if ($children->isEmpty() && $access->getParents()->isEmpty()) {
            $form->addError(new FormError('Action is forbidden: Parent role or child routes are required.'));

            return;
        }

        if (!$access->isNew()) {
            $keys = [];

            foreach ($form->get('children')->getData() as $child) {
                assert($child instanceof AccessItem);
                $keys[] = $child->getId();
            }

            $this->accessItemRepository->clearAccessItemPermissions($access->getId(), $keys);
        }

        $event->setData($access);
    }

    public function preSubmit(FormEvent $event): void
    {
        $accessAsArray = $event->getData();
        $data = [];

        if (!array_key_exists('parents', $accessAsArray) || $accessAsArray['parents'] === null) {
            $data['parents'] = new ArrayCollection();
        }

        if (!array_key_exists('type', $accessAsArray) || $accessAsArray['type'] === null) {
            $data['type'] = AccessItem::TYPE_ROLE;
        }

        $event->setData($accessAsArray + $data);
    }

    public function preSetData(FormEvent $event): void
    {
        $access = $event->getData();
        $form = $event->getForm();
        assert($access instanceof AccessItem);

        if ($access->getCode() === AccessItem::ROLE_SUPERADMIN) {
            $form->remove('code');
            $form->remove('type');
            $form->addError(new FormError('Action is forbidden'));
        }

        $form->add('children', RoutePermissionChoiceType::class, [
            'multiple' => true,
            'label' => $this->translator->trans('gtt_sylius_rbac.form.permissions'),
            'subject' => $access,
            'expanded' => true,
        ]);
    }

    public function postSubmit(FormEvent $event): void
    {
        $access = $event->getData();

        assert($access instanceof AccessItem);
    }

    /**
     * @inheritDoc
     */
    public static function getSubscribedEvents(): array
    {
        return [
            FormEvents::PRE_SET_DATA => 'preSetData',
            FormEvents::PRE_SUBMIT => 'preSubmit',
            FormEvents::SUBMIT => 'onSubmit',
            FormEvents::POST_SUBMIT => 'postSubmit',
        ];
    }
}
