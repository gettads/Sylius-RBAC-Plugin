<?php

declare(strict_types=1);

namespace Gtt\SyliusRbacPlugin\EventListener;

use Gtt\SyliusRbacPlugin\Service\RbacUserAccessService;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\RequestEvent;

final class AdminRequestSecurityListener implements EventSubscriberInterface
{
    public const METHOD = 'requestSecurity';
    public const ERROR_HEADER = 'x-rbac-forbidden';

    public function __construct(
        private RbacUserAccessService $accessService,
    )
    {
    }

    public function requestSecurity(RequestEvent $event): void
    {
        $routeAlias = $event->getRequest()->get('_route');

        if ($routeAlias === null) {
            return;
        }

        if (!$this->accessService->canRoute($routeAlias)) {
            $response = !$event->isMainRequest()
                ? new Response(null, Response::HTTP_OK)
                : new Response('ACCESS DENIED', Response::HTTP_FORBIDDEN, [self::ERROR_HEADER => true]);

            $event->setResponse($response);
        }
    }

    /**
     * @inheritDoc
     */
    public static function getSubscribedEvents()
    {
        return [
            RequestEvent::class => self::METHOD,
        ];
    }
}
