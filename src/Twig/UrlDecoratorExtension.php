<?php

declare(strict_types=1);

namespace Gtt\SyliusRbacPlugin\Twig;

use Gtt\SyliusRbacPlugin\Service\RbacUserAccessService;
use Symfony\Bridge\Twig\Extension\RoutingExtension;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Twig\Extension\AbstractExtension;
use Twig\Node\Node;
use Twig\TwigFunction;

/**
 * Decorator for class: \Symfony\Bridge\Twig\Extension\RoutingExtension
 */
class UrlDecoratorExtension extends AbstractExtension
{
    public function __construct(
        private UrlGeneratorInterface $generator,
        private RbacUserAccessService $accessService,
        private RoutingExtension $decoratedExtension,
    )
    {
    }

    /**
     * {@inheritdoc}
     */
    public function getFunctions(): array
    {
        return [
            new TwigFunction('url', [$this, 'getUrl'], ['is_safe_callback' => [$this, 'isUrlGenerationSafe']]),
            new TwigFunction('path', [$this, 'getPath'], ['is_safe_callback' => [$this, 'isUrlGenerationSafe']]),
        ];
    }

    /**
     * @param array<string|int|bool|array|null> $parameters
     */
    public function getPath(string $name, array $parameters = [], bool $relative = false): string
    {
        if (!$this->accessService->canRoute($name)) {
            return $this->generator->generate(RbacUserAccessService::REDIRECT_ROUTE);
        }

        return $this->decoratedExtension->getPath($name, $parameters, $relative);
    }

    /**
     * @param array<string|int|bool|array|null> $parameters
     */
    public function getUrl(string $name, array $parameters = [], bool $schemeRelative = false): string
    {
        if (!$this->accessService->canRoute($name)) {
            return $this->generator->generate(RbacUserAccessService::REDIRECT_ROUTE);
        }

        return $this->decoratedExtension->getUrl($name, $parameters, $schemeRelative);
    }

    /**
     * @return array<string>
     */
    public function isUrlGenerationSafe(Node $argsNode): array
    {
        return $this->decoratedExtension->isUrlGenerationSafe($argsNode);
    }
}
