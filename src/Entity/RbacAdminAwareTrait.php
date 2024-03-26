<?php

declare(strict_types=1);

namespace Gtt\SyliusRbacPlugin\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

trait RbacAdminAwareTrait
{
    /**
     * @ORM\ManyToMany(targetEntity=AccessItem::class)
     * @ORM\JoinTable(name="sylius_rbac_items_admin_users",
     *      joinColumns={@ORM\JoinColumn(name="user_id", referencedColumnName="id", onDelete="CASCADE")},
     *      inverseJoinColumns={@ORM\JoinColumn(name="item_id", referencedColumnName="id", onDelete="CASCADE")}
     *  )
     */
    #[ORM\ManyToMany(targetEntity: AccessItem::class)]
    #[ORM\JoinTable(name: 'sylius_rbac_items_admin_users')]
    #[ORM\JoinColumn(name: 'user_id', referencedColumnName: 'id', onDelete: 'CASCADE')]
    #[ORM\InverseJoinColumn(name: 'item_id', referencedColumnName: 'id', onDelete: 'CASCADE')]
    private Collection $accessCollection;

    public function __construct()
    {
        $this->accessCollection = new ArrayCollection();
    }

    public function getAccessCollection(): Collection
    {
        return $this->accessCollection;
    }

    public function setAccessCollection(Collection $accessCollection): void
    {
        $this->accessCollection = $accessCollection;
    }
}
