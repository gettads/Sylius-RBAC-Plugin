<?php

declare(strict_types=1);

namespace Gtt\SyliusRbacPlugin\Entity;

use Doctrine\Common\Collections\Collection;

interface RbacUserInterface
{
    public function getAccessCollection(): Collection;

    public function setAccessCollection(Collection $accessCollection): void;
}
