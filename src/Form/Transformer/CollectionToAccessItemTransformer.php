<?php

declare(strict_types=1);

namespace Gtt\SyliusRbacPlugin\Form\Transformer;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\PersistentCollection;
use Gtt\SyliusRbacPlugin\Entity\AccessItem;
use Symfony\Component\Form\DataTransformerInterface;

class CollectionToAccessItemTransformer implements DataTransformerInterface
{
    /**
     * @inheritDoc
     */
    public function transform($value)
    {
        if (!($value instanceof PersistentCollection) && !($value instanceof ArrayCollection)) {
            return $value;
        }

        return ($value->first() !== false) ? $value->first() : null;
    }

    /**
     * @inheritDoc
     */
    public function reverseTransform($value)
    {
        if ($value === null) {
            return new ArrayCollection();
        }

        if (!($value instanceof AccessItem)) {
            return $value;
        }

        return new ArrayCollection([$value]);
    }
}
