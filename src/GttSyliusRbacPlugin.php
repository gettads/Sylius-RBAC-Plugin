<?php

declare(strict_types=1);

namespace Gtt\SyliusRbacPlugin;

use Gtt\SyliusRbacPlugin\DependencyInjection\GttSyliusRbacExtension;
use Sylius\Bundle\CoreBundle\Application\SyliusPluginTrait;
use Symfony\Component\DependencyInjection\Extension\ExtensionInterface;
use Symfony\Component\HttpKernel\Bundle\Bundle;

/**
 * Each of a plugins should be created with Plugin instead of Bundle suffix for the root class.
 * @see SyliusPluginTrait
 */
class GttSyliusRbacPlugin extends Bundle
{
    use SyliusPluginTrait;

    public function getContainerExtension(): ExtensionInterface
    {
        $this->extension = new GttSyliusRbacExtension();

        return $this->extension;
    }
}
