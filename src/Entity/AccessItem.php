<?php

declare(strict_types=1);

namespace Gtt\SyliusRbacPlugin\Entity;

use DateTime;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Exception\InvalidArgumentException;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Gtt\SyliusRbacPlugin\Infrastructure\Repository\AccessItemRepository;
use Sylius\Component\Resource\Model\ResourceInterface;
use Sylius\Component\Resource\Model\TimestampableInterface;
use Sylius\Component\Resource\Model\TimestampableTrait;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;

/**
 * @codeCoverageIgnore
 */
#[ORM\Entity(repositoryClass: AccessItemRepository::class)]
#[ORM\HasLifecycleCallbacks]
#[ORM\Table(name: 'sylius_rbac_item')]
#[UniqueEntity('code')]
class AccessItem implements ResourceInterface, TimestampableInterface
{
    use TimestampableTrait;

    public const ROLE_SUPERADMIN = 'super_admin';
    public const ROLE_ADMIN = 'admin';
    public const ROLE_MANAGER = 'manager';
    public const ROLE_CASHIER = 'cashier';
    public const ROLE_AUDIT = 'audit';
    public const ROLE_STOREKEEPER = 'storekeeper';
    public const ROLE_CONTENT_MANAGER = 'content_manager';

    public const TYPE_ROLE = 'role';
    public const TYPE_PERMISSION_LOGIC = 'permission_logic';
    public const TYPE_PERMISSION_ROUTE = 'permission_route';

    public const TYPE_RESTRICTION_GRID_COLUMN = 'restriction_grid_column';
    public const TYPE_RESTRICTION_FORM_FIELD = 'restriction_form_field';

    // @codingStandardsIgnoreStart

    /**
     * @var DateTime|null $createdAt
     */
    #[ORM\Column(name: 'created_at', type: Types::DATETIME_MUTABLE, nullable: true)]
    protected $createdAt;

    /**
     * @var DateTime|null $updatedAt
     */
    #[ORM\Column(name: 'updated_at', type: Types::DATETIME_MUTABLE, nullable: true)]
    protected $updatedAt;

    // @codingStandardsIgnoreEnd

    #[ORM\Id()]
    #[ORM\Column(type: Types::INTEGER, nullable: false)]
    #[ORM\GeneratedValue()]
    private ?int $id;

    #[ORM\ManyToMany(targetEntity: AccessItem::class, mappedBy: 'parents')]
    #[ORM\JoinTable(name: 'sylius_rbac_items_inheritance')]
    #[ORM\JoinColumn(name: 'parent_id', referencedColumnName: 'id')]
    #[ORM\InverseJoinColumn(name: 'item_id', referencedColumnName: 'id')]
    private Collection $children;

    #[ORM\ManyToMany(targetEntity: AccessItem::class, inversedBy: 'children')]
    #[ORM\JoinTable(name: 'sylius_rbac_items_inheritance')]
    #[ORM\JoinColumn(name: 'item_id', referencedColumnName: 'id')]
    #[ORM\InverseJoinColumn(name: 'parent_id', referencedColumnName: 'id')]
    private Collection $parents;

    #[ORM\Column(name: 'type', type: Types::STRING, length: 255)]
    private string $type = self::TYPE_ROLE;

    #[ORM\Column(name: 'code', type: Types::STRING, length: 255, unique: true)]
    private string $code = '';

    public function __construct()
    {
        $this->children = new ArrayCollection();
        $this->parents = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setId(int $id): void
    {
        $this->id = $id;
    }

    public function isNew(): bool
    {
        return isset($this->id) === false;
    }

    public function getParents(): Collection
    {
        return $this->parents;
    }

    public function setParents(Collection $parents): void
    {
        $this->parents = $parents;

        foreach ($this->parents as $parent) {
            assert($parent instanceof AccessItem);

            if (!$parent->getChildren()->contains($this)) {
                $parent->getChildren()->add($this);
            }
        }
    }

    public function addParent(AccessItem $accessItem): void
    {
        if (!$this->getParents()->contains($accessItem)) {
            $this->getParents()->add($accessItem);
        }

        if (!$accessItem->getChildren()->contains($this)) {
            $accessItem->getChildren()->add($this);
        }
    }

    public function removeParent(AccessItem $accessItem): void
    {
        if ($this->getParents()->contains($accessItem)) {
            $this->getParents()->removeElement($accessItem);
        }
    }

    public function getChildren(): Collection
    {
        return $this->children;
    }

    public function setChildren(Collection $children): void
    {
        $this->children = $children;

        foreach ($children as $child) {
            assert($child instanceof AccessItem);
            $child->addParent($this);
        }
    }

    public function addChild(AccessItem $accessItem): void
    {
        if (!$this->getChildren()->contains($accessItem)) {
            $this->getChildren()->add($accessItem);
        }
    }

    public function unsetChildren(): void
    {
        foreach ($this->getChildren() as $child) {
            assert($child instanceof AccessItem);
            $child->removeParent($this);
        }

        $this->setChildren(new ArrayCollection());
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function setType(string $type): void
    {
        $this->type = $type;
    }

    public function getCode(): string
    {
        return $this->code;
    }

    public function setCode(string $code): void
    {
        $this->code = $code;
    }

    #[ORM\PreFlush]
    public function preFlush(): void
    {
        if ($this->getCreatedAt() === null) {
            $this->setCreatedAt(new DateTime());
        }

        $this->setUpdatedAt(new DateTime());
    }

    #[ORM\PreRemove]
    public function preRemove(): void
    {
        if ($this->code === self::ROLE_SUPERADMIN) {
            throw new InvalidArgumentException('Can not remove "super_admin" role.');
        }
    }

    public function __toString(): string
    {
        return $this->getCode();
    }
}
