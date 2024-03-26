<?php

declare(strict_types=1);

namespace Gtt\SyliusRbacPlugin\Service;

use Doctrine\ORM\PersistentCollection;
use Gtt\SyliusRbacPlugin\Entity\AccessItem;
use Gtt\SyliusRbacPlugin\Entity\RbacUserInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\Exception\InvalidArgumentException;

class RbacUserAccessService
{
    public const REDIRECT_ROUTE = 'gtt_sylius_rbac_admin_no_content';

    private const GRAPH_INIT_MARK = '::init::';

    private ?string $routeName;

    public function __construct(RequestStack $requestStack, private Security $security)
    {
        $this->routeName = $requestStack->getCurrentRequest()?->get('_route');
    }

    public function can(string $accessName): bool
    {
        //Can have own logic
        return $this->canRoute($accessName);
    }

    public function canRoute(?string $routeName): bool
    {
        if ($routeName === null) {
            $routeName = $this->routeName;
        }

        if ($routeName === null) {
            throw new InvalidArgumentException('Route is invalid: ' . $routeName);
        }

        if (!($this->security->getUser() instanceof RbacUserInterface)) {
            return true;
        }

        if ($routeName === self::REDIRECT_ROUTE) {
            return true;
        }

        return $this->canByChildren($routeName) || $this->canByParents($routeName);
    }

    private function canByChildren(string $routeName): bool
    {
        assert($this->security->getUser() instanceof RbacUserInterface);

        foreach ($this->security->getUser()->getAccessCollection() as $accessItem) {
            if ($this->hasChild($accessItem, $routeName)) {
                return true;
            }
        }

        return false;
    }

    private function canByParents(string $routeName): bool
    {
        $markedAsInitialCodes = [];
        $accessItemsWithParents = $this->createUserAccessListWithParents();

        foreach ($accessItemsWithParents as $code => $accessItem) {
            if (str_starts_with($code, self::GRAPH_INIT_MARK)) {
                $markedAsInitialCodes[] = $accessItem->getCode();
            }
        }

        foreach ($accessItemsWithParents as $accessItem) {
            assert($accessItem instanceof AccessItem);

            if (
                $this->isSuperAdmin($accessItem)
                || $accessItem->getCode() === $routeName
            ) {
                return true;
            }

            if (in_array($routeName, $this->getFirstChildrenCodes($accessItem, $markedAsInitialCodes), true)) {
                return true;
            }
        }

        return false;
    }

    private function isSuperAdmin(AccessItem $accessItem): bool
    {
        return $accessItem->getType() === AccessItem::TYPE_ROLE
            && $accessItem->getCode() === AccessItem::ROLE_SUPERADMIN;
    }

    /**
     * @param array<string> $markedAsInitialCodes
     *
     * @return array<string>
     */
    private function getFirstChildrenCodes(AccessItem $item, array $markedAsInitialCodes): array
    {
        /**
         * @var array<AccessItem> $initialItems
         */
        $initialItems = [];
        $result = [$item->getCode()];

        $item->getChildren() instanceof PersistentCollection && $item->getChildren()->initialize();

        foreach ($item->getChildren() as $child) {
            assert($child instanceof AccessItem);

            if (in_array($child->getCode(), $markedAsInitialCodes, true)) {
                $initialItems[] = $child;
            }
        }

        /**
         * @var array<AccessItem> $source
         */
        $source = [];

        if ($initialItems === []) {
            $source = $item->getChildren()->toArray();
        }

        foreach ($initialItems as $initialItem) {
            if ($initialItem->getType() === AccessItem::TYPE_ROLE) {
                $source = array_merge(
                    $source,
                    $item->getChildren()->filter(
                        fn(AccessItem $accessItem) => $accessItem->getType() === AccessItem::TYPE_PERMISSION_ROUTE,
                    )->toArray(),
                );
            }

            if ($initialItem->getType() === AccessItem::TYPE_PERMISSION_ROUTE) {
                $source = [$initialItem];
            }
        }

        foreach ($source as $child) {
            $result[] = $child->getCode();
        }

        return $result;
    }

    private function hasChild(AccessItem $item, string $childCode): bool
    {
        $item->getChildren() instanceof PersistentCollection && $item->getChildren()->initialize();

        foreach ($item->getChildren() as $child) {
            assert($child instanceof AccessItem);

            if ($childCode === $child->getCode()) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return array<string, AccessItem>
     */
    private function createUserAccessListWithParents(?AccessItem $item = null, ?array $data = []): array
    {
        $isFirstStep = $item === null && $data === [];

        assert($this->security->getUser() instanceof RbacUserInterface);

        if ($isFirstStep) {
            $data = [];

            foreach ($this->security->getUser()->getAccessCollection() as $accessItem) {
                assert($accessItem instanceof AccessItem);
                $data[self::GRAPH_INIT_MARK . $accessItem->getCode()] = $accessItem;
            }
        } elseif (!array_key_exists(self::GRAPH_INIT_MARK . $item->getCode(), $data)) {
            $data[$item->getCode()] = $item;
        }

        if ($isFirstStep) {
            foreach ($data as $accessItem) {
                $data = array_merge($data, $this->createUserAccessListWithParents($accessItem, $data));
            }
        } else {
            $item->getParents() instanceof PersistentCollection && $item->getParents()->initialize();

            foreach ($item->getParents() as $parent) {
                $data = array_merge($data, $this->createUserAccessListWithParents($parent, $data));
            }
        }

        return $data;
    }
}
