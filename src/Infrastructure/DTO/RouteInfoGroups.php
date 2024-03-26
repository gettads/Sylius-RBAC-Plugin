<?php

declare(strict_types=1);

namespace Gtt\SyliusRbacPlugin\Infrastructure\DTO;

/**
 * @codeCoverageIgnore
 */
class RouteInfoGroups
{
    /**
     * @var array<string, array<RouteInfo>>
     */
    private array $recognizedGroup = [];

    /**
     * @var array<string, array<RouteInfo>>
     */
    private array $unrecognizedGroup = [];

    /**
     * @var array<string, array<RouteInfo>>
     */
    private array $apiGroup = [];

    /**
     * @return array<string, array<RouteInfo>>
     */
    public function getRecognizedGroup(): array
    {
        return $this->recognizedGroup;
    }

    /**
     * @param array<string, array<RouteInfo>> $recognizedGroup
     */
    public function setRecognizedGroup(array $recognizedGroup): void
    {
        $this->recognizedGroup = $recognizedGroup;
    }

    public function addToRecognizedGroup(string $name, RouteInfo $routeInfo): void
    {
        $this->recognizedGroup[$name][] = $routeInfo;
    }

    /**
     * @return array<string, array<RouteInfo>>
     */
    public function getUnrecognizedGroup(): array
    {
        return $this->unrecognizedGroup;
    }

    /**
     * @param array<string, array<RouteInfo>> $unrecognizedGroup
     */
    public function setUnrecognizedGroup(array $unrecognizedGroup): void
    {
        $this->unrecognizedGroup = $unrecognizedGroup;
    }

    public function addToUnrecognizedGroup(string $name, RouteInfo $routeInfo): void
    {
        $this->unrecognizedGroup[$name][] = $routeInfo;
    }

    /**
     * @return array<string, array<RouteInfo>>
     */
    public function getApiGroup(): array
    {
        return $this->apiGroup;
    }

    /**
     * @param array<string, array<RouteInfo>> $apiGroup
     */
    public function setApiGroup(array $apiGroup): void
    {
        $this->apiGroup = $apiGroup;
    }

    public function addToApiGroup(string $name, RouteInfo $routeInfo): void
    {
        $this->apiGroup[$name][] = $routeInfo;
    }
}
