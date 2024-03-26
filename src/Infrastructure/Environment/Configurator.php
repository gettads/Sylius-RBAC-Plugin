<?php

declare(strict_types=1);

namespace Gtt\SyliusRbacPlugin\Infrastructure\Environment;

class Configurator
{
    public const MASK_CHAR = '*';

    /**
     * @param array<array<string, string|null>> $config
     */
    public function __construct(private readonly array $config)
    {
    }

    /**
     * @return array<array<string, string|null>>
     */
    public function getConfig(): array
    {
        return $this->config;
    }
}
