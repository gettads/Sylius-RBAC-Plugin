<?php

declare(strict_types=1);

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Gtt\SyliusRbacPlugin\Entity\RbacAdminAwareTrait;
use Gtt\SyliusRbacPlugin\Entity\RbacUserInterface;
use Sylius\Component\Core\Model\AdminUser as BaseAdminUser;
use Sylius\Component\Resource\Model\ResourceInterface;

#[ORM\Entity]
#[ORM\Table(name: 'sylius_admin_user')]
class AdminUser extends BaseAdminUser implements RbacUserInterface
{
    use RbacAdminAwareTrait {
        __construct as private initializeAccessCollection;
    }

    public function __construct()
    {
        parent::__construct();

        $this->initializeAccessCollection();
    }
}
