<?php

declare(strict_types=1);

namespace Gtt\SyliusRbacPlugin\Infrastructure\Repository;

use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;
use Gtt\SyliusRbacPlugin\Entity\AccessItem;
use Sylius\Bundle\ResourceBundle\Doctrine\ORM\EntityRepository;
use Sylius\Component\Resource\Model\ResourceInterface;
use Sylius\Component\Resource\Repository\RepositoryInterface;

class AccessItemRepository extends EntityRepository implements RepositoryInterface
{
    public function getEntityManager(): EntityManagerInterface
    {
        return parent::getEntityManager();
    }

    public function createListQueryBuilder(): QueryBuilder
    {
        return $this->createQueryBuilder('i')
            ->andWhere('i.type = :type')
            ->setParameter('type', AccessItem::TYPE_ROLE);
    }

    /**
     * @param array<int> $exclusionKeys
     */
    public function clearAccessItemPermissions(int $parentId, array $exclusionKeys): void
    {
        $exclusionQuery = $exclusionKeys === [] ? '' : ' AND item_id NOT IN (' . implode(',', $exclusionKeys) . ')';

        $this->getEntityManager()
            ->getConnection()
            ->prepare('
                DELETE sylius_rbac_items_inheritance
                    FROM sylius_rbac_items_inheritance
                    INNER JOIN sylius_rbac_item
                        ON sylius_rbac_item.id=sylius_rbac_items_inheritance.item_id
                            AND sylius_rbac_item.type = :type
                WHERE parent_id = :parentId' . $exclusionQuery)
            ->executeQuery([
                'parentId' => $parentId,
                'type' => AccessItem::TYPE_PERMISSION_ROUTE,
            ])
        ;
    }

    public function getSynchronizedId(AccessItem $item): int
    {
        $checkQuery = $this->createQueryBuilder('i')
            ->select('i.id')
            ->andWhere('i.code = :code')
            ->andWhere('i.type = :type')
            ->setParameter('type', $item->getType())
            ->setParameter('code', $item->getCode())
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult()
        ;

        if ($checkQuery !== null && ($checkQuery['id'] ?? 0) !== 0) {
            return $checkQuery['id'];
        }

        $this->getEntityManager()->persist($item);
        $this->getEntityManager()->flush();

        return $item->getId();
    }

    public function add(ResourceInterface $resource): void
    {
        assert($resource instanceof AccessItem);

        if ($resource->getCreatedAt() === null) {
            $resource->setCreatedAt(new DateTime());
        }

        $resource->setUpdatedAt(new DateTime());

        parent::add($resource);
    }
}
