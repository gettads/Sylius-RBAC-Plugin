<?php

declare(strict_types=1);

namespace Gtt\SyliusRbacPlugin\Infrastructure\DTO;

class RouteInfo
{
    private ?string $alias = null;

    private ?string $localizedLabel = null;

    private ?string $url = null;

    /**
     * @var array<string> $methods
     */
    private array $methods = [];

    public function getAlias(): ?string
    {
        return $this->alias;
    }

    public function setAlias(?string $alias): void
    {
        $this->alias = $alias;
    }

    public function getLocalizedLabel(): ?string
    {
        return $this->localizedLabel;
    }

    public function setLocalizedLabel(?string $localizedLabel): void
    {
        $this->localizedLabel = $localizedLabel;
    }

    /**
     * @return array<string>
     */
    public function getMethods(): array
    {
        return $this->methods;
    }

    /**
     * @param array<string> $methods
     */
    public function setMethods(array $methods): void
    {
        $this->methods = $methods === [] ? ['GET'] : $methods;
    }

    public function getUrl(): ?string
    {
        return $this->url;
    }

    public function setUrl(?string $url): void
    {
        $this->url = $url;
    }
}
